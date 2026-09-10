<?php

namespace app\modules\webhook\models\telegram;
use GuzzleHttp\Client;

class Telegram
{
    private $telegram;
    private $token = '5662663416:AAHpwlPGReFjEkJbBULeqdy9HtCKO3iQ1PE';

    public function __construct()
    {
        $this->telegram = new Client(['base_uri' => "https://api.telegram.org/bot{$this->token}/"]);
    }

    public function sendMessage($message, $id)
    {
        try {
            $response = $this->telegram->request('POST', 'sendMessage',
                [
                    "json" => [
                        "chat_id" => $id,
                        'parse_mode' => 'HTML',
                        "text" => $message,
                        "disable_web_page_preview" => true,
                    ],
                ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return 200;
        }

    }
}