<?php

namespace app\modules\parsing_mail\controllers;

use Yii;
use yii\web\Controller;
use app\models\logger\CleanerLogger;
use app\models\logger\DebugLogger;
use app\modules\parsing_mail\models\client\Client;
use app\modules\parsing_mail\models\mail\Mail;
use app\models\bitrix\Bitrix;

class MailController extends Controller
{
    public $layout = 'main';

    public function beforeAction($action)
    {
//        $get_data = \Yii::$app->request->get();
//        if(!isset($get_data['token']) || $get_data['token'] != Yii::$app->params['token'])
//        {
//            echo 'Ошибка подключения';
//            die;
//        }
        $this->enableCsrfValidation = false;
        CleanerLogger::clear();

        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        return parent::afterAction($action, $result);
    }

    public function actionGetMail()
    {
//        die;
        $mail = new Mail(
            'imap.hostinger.com',
            '993',
            'info@dsf.com',
            'sdf@',
            'INBOX',
        );

        $mail->getMailbox();
        dd($mail);

        $mail->getLastId();
        $answer = $mail->readMail();

        dd(1);
    }
	
	public function actionTest()
		{
//            $webhook = Bitrix::BX24init();
//
//            $contact = $webhook->request("crm.duplicate.findbycomm", [
//                "entity_type" => "CONTACT",
//                "type" => "PHONE",
//                "values" => [79961636274],
//            ]);
//            $contact = $contact['CONTACT'][0];
//            $deal = $webhook->request("crm.deal.list", ['filter' =>[
////                            'CATEGORY_ID' => 20,
//                'CLOSED' => 'N',
//                'CONTACT_ID' => $contact,
//            ]]);
//            dd($deal);
            //die;
			$mail = new Mail(
				'imap.yandex.ru',
				'993',
				'lidysts@yandex.ru',
				'plrkxyahamcfytrp',
				'INBOX',
			);

			$mail->getMailboxTest();
			$mail->getLastId();

			$answer = $mail->readMailTest();

			dd(1);
    }

}