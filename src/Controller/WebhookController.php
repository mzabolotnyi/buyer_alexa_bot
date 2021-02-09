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
            $messageId = $message->messageId;
            $chatId = $message->chat->id;
            $params = ['chat_id' => $chatId];

            if ($type === 'message') {

                $text = $message->text;

                if ($text == '/start') {

                    $params['text'] = 'Send me a link';
                    $this->conversationManager->finish($chatId);

                } elseif ($this->trackingManager->hasParser($text)) {

                    $conversation = $this->conversationManager->start($chatId, Conversation::TYPE_AVAILABILITY_TRACKING);
                    $conversation->setParam('link', $text);

                    $colors = $this->trackingManager->getColors($conversation);

                    if (count($colors) === 1) {
                        //skip color choosing step and go to size choosing step
                        $conversation->setParam('color', $colors[0]);
                        $conversation->setStep(2);
                        $sizes = $this->trackingManager->getSizes($conversation);
                        $this->chooseSizeReply($params, $sizes);
                    } else {
                        $this->chooseColorReply($params, $colors);
                    }

                } else {
                    $params['text'] = 'I don\'t know this command yet =(';
                }

            } elseif ($type === 'callback_query') {

                $conversation = $this->conversationManager->current($chatId);

                if ($conversation !== null) {

                    $callbackData = $update->callbackQuery->data;

                    if ($conversation->checkType(Conversation::TYPE_AVAILABILITY_TRACKING)) {
                        switch ($conversation->getStep()) {
                            case 1:

                                $conversation->setParam('color', $callbackData);
                                $conversation->setStep(2);

                                $sizes = $this->trackingManager->getSizes($conversation);
                                $this->chooseSizeReply($params, $sizes);

                                $this->bot->deleteMessage(array_merge($params, ['message_id' => $messageId]));
                                break;

                            case 2:

                                $conversation->setParam('size', $callbackData);
                                $conversation->setStep(3);

                                $this->trackingManager->startTracking($conversation);

                                $params['text'] = sprintf(
                                    "Availability tracking started for\n\nLink: %s\nColor:  %s\nSize:  %s",
                                    $conversation->getParam('link'),
                                    $conversation->getParam('color'),
                                    $conversation->getParam('size')
                                );

                                $this->conversationManager->finish($chatId);
                                $this->bot->deleteMessage(array_merge($params, ['message_id' => $messageId]));
                                break;
                        }
                    }
                }
            }

            $this->bot->sendMessage($params);
            $this->em->flush();

        } catch (\Throwable $e) {
            $params['text'] = "Error: {$e->getMessage()}";
            $this->bot->sendMessage($params);
        }

        return new Response();
    }

    private function chooseColorReply(&$params, $colors): void
    {
        $keyboard = Keyboard::make()->inline();

        foreach ($colors as $color) {
            $keyboard->row(Keyboard::inlineButton(['text' => $color, 'callback_data' => $color]));
        }

        $params['text'] = 'Choose a color';
        $params['reply_markup'] = $keyboard;
    }

    private function chooseSizeReply(&$params, $sizes): void
    {
        $keyboard = Keyboard::make()->inline();

        foreach ($sizes as $size) {
            $keyboard->row(Keyboard::inlineButton(['text' => $size, 'callback_data' => $size]));
        }

        $params['text'] = 'Choose a size';
        $params['reply_markup'] = $keyboard;
    }
}