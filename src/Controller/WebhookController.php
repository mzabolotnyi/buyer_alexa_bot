<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Poll;
use Telegram\Bot\Objects\PollOption;

/**
 * @Route("")
 */
class WebhookController extends AbstractController
{
    /**
     * @Route("")
     */
    public function index()
    {
        $bot = new Api(getenv('TELEGRAM_TOKEN'));
        $result = $bot->getWebhookUpdate();

        $text = $result->getMessage()->text;
        $chatId = $result->getMessage()->chat->id;
//        $callback = $result-

        $params = [
            'chat_id' => $chatId
        ];

        if ($text == '/start') {

            $params['text'] = 'Send me a link';
            $bot->sendMessage($params);

        } elseif (strpos($text, 'zara.com') !== false) {

            $colors = ['Black', 'Ecru'];
            $keyboard = Keyboard::make()->inline();
            $callbackBase = [
                'url' => $text,
                'step' => 'color'
            ];

            foreach ($colors as $color) {
                $callbackData = json_encode(array_merge($callbackBase, ['color' => $color]));
                $keyboard->row(Keyboard::inlineButton(['text' => $color, 'callback_data' => $callbackData]));
            }

            $params['text'] = 'Choose a color';
            $params['reply_markup'] = $keyboard;

            $bot->sendMessage($params);
        }

        return new Response();
    }
}