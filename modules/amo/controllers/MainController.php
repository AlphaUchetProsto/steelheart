<?php

namespace app\modules\amo\controllers;

use app\models\logger\DebugLogger;
use Yii;
use yii\web\Controller;
use app\modules\amo\models\AmoTable;
use app\modules\amo\models\CSVExport;

/**
 * Default controller for the `amo` module
 */
class MainController extends Controller
{
    public $enableCsrfValidation = false;

    public function runAction($id, $params = [])
    {
        $token = \Yii::$app->params['modules']['amo']['token'] ?? null;
        $requestToken = \Yii::$app->request->get('token');

        if(empty($requestToken) || $token !== $requestToken)
        {
            return self::actionAccessDenied();
        }

        return parent::runAction($id, $params);
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return 200;
    }

    public function actionExportNotes()
    {
        try {
            $logger = DebugLogger::instance("notes_export");
            $logger->save(getmypid(), null, "PID");

            $apiClient = new \AmoCRM\Client\AmoCRMApiClient();
            $token = new \AmoCRM\Client\LongLivedAccessToken(\Yii::$app->params['modules']['amo']['app_token']);

            $apiClient->setAccessToken($token)
                ->setAccountBaseDomain('steelheart.amocrm.ru');

            // Получаем только текстовые примечания
            $filter = new \AmoCRM\Filters\NotesFilter();
            $filter->setNoteTypes(["common"]);

            $csv = new CSVExport();

            // Проходимся по всем выгруженным сделкам
            foreach (AmoTable::find()->batch(200) as $deals) {
                foreach ($deals as $deal) {
                    $logger->save($deal->id, null, "Deal ID");

                    $notesText = "";

                    try {
                        $notes = $apiClient->notes(\AmoCRM\Helpers\EntityTypesInterface::LEADS)->getByParentId($deal->id, $filter);
                    } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                        $logger->save($e->getMessage());
                        sleep(5);
                        $notes = $apiClient->notes(\AmoCRM\Helpers\EntityTypesInterface::LEADS)->getByParentId($deal->id, $filter);
                    } catch (\Throwable $t) {
                        if ($t->getMessage() == "No content") {
                            continue;
                        } else {
                            throw $t;
                        }
                    }

                    foreach ($notes as $note) {
                        if (!empty($notesText)) {
                            $notesText .= "\n";
                        }

                        $notesText .= $note->text;
                        $csv->put([$deal->id, $note->text]);
                    }

                    if (!empty($notesText)) {
                        $deal->notes = $notesText;
                        $deal->save();
                        $logger->save("Сохранили в БД");
                    }

                    $logger->save("Выгрузили " . $notes->count() . " примечаний");
                }

                sleep(1);
            }

            $csv->close();
        } catch (\Throwable $t) {
            dump($t->getMessage());
            dd($t->getTraceAsString());
        }

        return 200;
    }

    public function actionAccessDenied()
    {
        return $this->render("access_denied");
    }
}
