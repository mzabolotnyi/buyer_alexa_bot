<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Service\AvailabilityTracking\Parser\ZaraParser;
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

    public function __construct()
    {
        $this->bot = new Api(getenv('TELEGRAM_TOKEN'));
    }

    /**
     * @Route("")
     *
     * @param EntityManagerInterface $em
     * @param ConversationManager $conversationManager
     * @param TrackingManager $trackingManager
     * @return Response
     */
    public function index(EntityManagerInterface $em, ConversationManager $conversationManager, TrackingManager $trackingManager): Response
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
                    $conversationManager->finish($chatId);

                } elseif (strpos($text, ZaraParser::DOMAIN) !== false) {

                    $conversation = $conversationManager->start($chatId, Conversation::TYPE_AVAILABILITY_TRACKER);
                    $conversation->setParam('link', $text);

                    $colors = $trackingManager->getColors($conversation);
                    $keyboard = Keyboard::make()->inline();

                    foreach ($colors as $color) {
                        $keyboard->row(Keyboard::inlineButton(['text' => $color, 'callback_data' => $color]));
                    }

                    $params['text'] = 'Choose a color';
                    $params['reply_markup'] = $keyboard;

                } else {
                    $params['text'] = 'I don\'t know this command yet =(';
                }

            } elseif ($type === 'callback_query') {

                $conversation = $conversationManager->current($chatId);

                if ($conversation !== null) {

                    $callbackData = $update->callbackQuery->data;

                    if ($conversation->checkType(Conversation::TYPE_AVAILABILITY_TRACKER)) {
                        switch ($conversation->getStep()) {
                            case 1:

                                $conversation->setParam('color', $callbackData);
                                $conversation->setStep(2);

                                $sizes = $trackingManager->getSizes($conversation);
                                $keyboard = Keyboard::make()->inline();

                                foreach ($sizes as $size) {
                                    $keyboard->row(Keyboard::inlineButton(['text' => $size, 'callback_data' => $size]));
                                }

                                $params['text'] = 'Choose a size';
                                $params['reply_markup'] = $keyboard;

                                $this->bot->deleteMessage(array_merge($params, ['message_id' => $messageId]));
                                break;

                            case 2:

                                $conversation->setParam('size', $callbackData);
                                $conversation->setStep(3);

                                $trackingManager->startTracking($conversation);

                                $params['text'] = sprintf(
                                    "Availability tracker started for\n\nLink: %s\nColor:  %s\nSize:  %s",
                                    $conversation->getParam('link'),
                                    $conversation->getParam('color'),
                                    $conversation->getParam('size')
                                );

                                $conversationManager->finish($chatId);
                                $this->bot->deleteMessage(array_merge($params, ['message_id' => $messageId]));
                                break;
                        }
                    }
                }
            }

            $this->bot->sendMessage($params);
            $em->flush();

        } catch (\Throwable $e) {
            //$params['text'] = 'Something went wrong. Please try again';
            $params['text'] = "Error: {$e->getMessage()}";
            $this->bot->sendMessage($params);
        }

        return new Response();
    }
}