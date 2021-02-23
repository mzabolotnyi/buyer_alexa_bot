<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Service\AvailabilityTracking\TrackingManager;
use App\Service\ConversationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

/**
 * @Route("")
 */
class WebhookController extends AbstractController
{
    const CALLBACK_ACTION_FINISH_TRACKING = 1;
    const CALLBACK_ACTION_TRACKING_COLOR = 2;
    const CALLBACK_ACTION_TRACKING_SIZE = 3;

    /** @var Api */
    private $bot;

    /** @var EntityManagerInterface */
    private $em;

    /** @var TrackingManager */
    private $trackingManager;

    /** @var ConversationManager */
    private $conversationManager;

    public function __construct(EntityManagerInterface $em, TrackingManager $trackingManager, ConversationManager $conversationManager)
    {
        $this->em = $em;
        $this->trackingManager = $trackingManager;
        $this->conversationManager = $conversationManager;
        $this->bot = new Api(getenv('TELEGRAM_TOKEN'));
    }

    /**
     * @Route("")
     *
     * @return Response
     */
    public function index(): Response
    {
        try {

            $update = $this->bot->getWebhookUpdate();
            $type = $update->detectType();
            $message = $update->getMessage();
            $chat = $message->chat;
            $chatId = $chat->id;
            $from = "{$chat->firstName} {$chat->lastName} {$chat->username}";
            $params = ['chat_id' => $chatId];

            if ($type === 'message') {

                $text = $message->text;

                if ($text == '/start') {

                    $params['text'] = sprintf(
                        "Send me a link and I will let you know when the item is in stock\n\nSupported sites:\n%s\n%s\n%s",
                        'zara.com',
                        'shop.mango.com',
                        'mangooutlet.com'
                    );

                } elseif ($this->trackingManager->hasParser($text)) {

                    $conversation = $this->conversationManager->start($chatId, Conversation::TYPE_AVAILABILITY_TRACKING);
                    $conversation->setFrom($from);
                    $conversation->setParam('link', $text);

                    $colors = $this->trackingManager->getColors($conversation);
                    $conversation->setParam('colors', $colors);

                    if (count($colors) === 1) {
                        //skip color choosing step and go to size choosing step
                        $conversation->setParam('color', array_values($colors)[0]);
                        $conversation->setStep(2);
                        $sizes = $this->trackingManager->getSizes($conversation);
                        $this->chooseSizeReply($params, $sizes, $conversation);
                    } else {
                        $this->chooseColorReply($params, $colors, $conversation);
                    }

                } else {
                    $params['text'] = 'I don\'t know this command yet =(';
                }

            } elseif ($type === 'callback_query') {
                $callbackData = json_decode($update->callbackQuery->data, true);
                $this->processCallbackAction($params, $callbackData, $message);
            }

            if (isset($params['text'])) {
                $this->bot->sendMessage($params);
            }

            $this->em->flush();

        } catch (\Throwable $e) {
            $params['text'] = "Error: {$e->getMessage()}";
            unset($params['reply_markup']);
            $this->bot->sendMessage($params);
        }

        return new Response();
    }

    private function chooseColorReply(array &$params, array $colors, Conversation $conversation): void
    {
        $keyboard = Keyboard::make()->inline();

        foreach ($colors as $colorId => $colorName) {
            $keyboard->row(Keyboard::inlineButton([
                'text' => $colorName,
                'callback_data' => json_encode([
                    'action' => WebhookController::CALLBACK_ACTION_TRACKING_COLOR,
                    'id' => $conversation->getId(),
                    'color' => $colorId
                ])
            ]));
        }

        $params['text'] = 'Choose a color';
        $params['reply_markup'] = $keyboard;
    }

    private function chooseSizeReply(array &$params, array $sizes, Conversation $conversation): void
    {
        $keyboard = Keyboard::make()->inline();

        foreach ($sizes as $size) {
            $keyboard->row(Keyboard::inlineButton([
                'text' => $size,
                'callback_data' => json_encode([
                    'action' => WebhookController::CALLBACK_ACTION_TRACKING_SIZE,
                    'id' => $conversation->getId(),
                    'size' => $size
                ])
            ]));
        }

        $params['text'] = 'Choose a size';
        $params['reply_markup'] = $keyboard;
    }

    private function processCallbackAction(array &$params, array $callbackData, $message): void
    {
        $messageId = $message->messageId;
        $chat = $message->chat;
        $from = "{$chat->firstName} {$chat->lastName} {$chat->username}";

        switch ($callbackData['action']) {
            case self::CALLBACK_ACTION_FINISH_TRACKING:

                $this->trackingManager->finishTracking($callbackData['id']);
                $params['text'] = 'Tracking was stopped';
                break;

            case self::CALLBACK_ACTION_TRACKING_COLOR:

                $conversation = $this->conversationManager->find($callbackData['id']);
                $colors = $conversation->getParam('colors');
                $conversation->setParam('color', $colors[$callbackData['color']]);
                $conversation->setStep(2);

                $sizes = $this->trackingManager->getSizes($conversation);
                $this->chooseSizeReply($params, $sizes, $conversation);

                $this->bot->deleteMessage(array_merge($params, ['message_id' => $messageId]));
                break;

            case self::CALLBACK_ACTION_TRACKING_SIZE:

                $conversation = $this->conversationManager->find($callbackData['id']);
                $conversation->setParam('size', $callbackData['size']);
                $conversation->setStep(3);

                $tracking = $this->trackingManager->startTracking($conversation);
                $tracking->setFrom($from);

                $params['text'] = sprintf(
                    "Tracking was started\n\nLink: %s\nColor:  %s\nSize:  %s",
                    $conversation->getParam('link'),
                    $conversation->getParam('color'),
                    $conversation->getParam('size')
                );

                $this->conversationManager->finish($callbackData['id']);
                $this->bot->deleteMessage(array_merge($params, ['message_id' => $messageId]));
                break;
        }
    }
}