<?php

namespace app\modules\hh\controllers;

use Yii;
use yii\web\Controller;
use app\models\logger\DebugLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use app\modules\hh\models\Client_hh;
use app\modules\hh\models\Contact;
use app\modules\hh\models\Deal;
use app\models\bitrix\Bitrix;
use Zxing\QrReader;

class MainController extends Controller
{
    public function actionGetResponse()
    {
        $client = new Client_hh();
        $time = $client->getTime();

        /** Для первого запуска */
//        $client->generateApplicationTokens('');

        try {
            $managers = $client->getManagers(4620917);

            $id_managers = [];
            foreach ($managers['items'] as $manager) {
                $id_managers[] = $manager['id'];
            }

            if (empty($id_managers)) {
                return false;
            }

            $vacancies = [];
            foreach ($id_managers as $key => $id_manager) {
                $raw_vacancies = $client->getVacancies(4620917, $id_manager);
                foreach ($raw_vacancies['items'] as $raw_vacancy) {
                    $vacancies[] = $raw_vacancy;
                }
            }

            $logger = DebugLogger::instance("get-response");

            $resumes = collect($vacancies)->map(function ($vacancy) use ($client) {
                return $client->getResponse($vacancy['id'])['items'];
            })->flatten(1)->filter(function ($response) use ($time) {
                return !is_null($response['resume']) && strtotime($response['created_at']) > $time;
            })->map(function ($response) use ($client) {
                return $client->getResume($response['resume']['id']);
            });

            if ($resumes->isNotEmpty()) {
                foreach ($resumes as $resume) {
//                dd($resume);
                    $contact = Contact::create($resume);
                    Deal::create($resume, $contact);
                }
            }

        } catch (\Exception $e) {
            file_put_contents(Yii::getAlias("@modules") . "/hh/config/error.php", $time);
            throw $e;
        }

        if (file_exists(Yii::getAlias("@modules") . "/hh/config/error.php")) {
            unlink(Yii::getAlias("@modules") . "/hh/config/error.php");
        }
    }
}
