<?php

namespace app\modules\tilda\controllers;

use app\models\bitrix\Bitrix;
use app\models\logger\CleanerLogger;
use app\models\logger\DebugLogger;
use app\modules\tilda\models\bitrix\Deal;
use app\modules\tilda\models\bitrix\Lead;
use app\modules\tilda\models\bitrix\Contact;
use Yii;
use yii\web\Controller;

class MainController extends Controller
{
    private static array $map_actions = [
        //"form440140347" => "technical-consultation",
    ];

   
    private static function StartParser($action, $post_array = [], $check_ssl = true) :void
    {
        $url = "https://sekvid.ru/for_del/baumeh/web/tilda/{$action}";

        $cmd = "curl -X POST -H 'Content-Type: application/x-www-form-urlencoded'";
        $cmd.= " -d '" . http_build_query($post_array) . "' '" . $url . "'";

        if (!$check_ssl){
            $cmd.= "'  --insecure";
        }

        $cmd .= " > /dev/null 2>&1 &";

        exec($cmd, $output, $exit);
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        CleanerLogger::clear();;

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $data_request = Yii::$app->request->post();

        $logger = DebugLogger::instance("data_request");
        $logger->save($data_request, Yii::$app->request, "Данные запроса");

        if(isset($data_request["Phone"]))
        {
            $data_request["Phone"] = preg_replace('/[^0-9]/', '', $data_request["Phone"]);
        }

        if(isset($data_request["formid"]) && array_key_exists($data_request["formid"], static::$map_actions))
        {
            static::StartParser(static::$map_actions[$data_request["formid"]], $data_request);
        }

        return $this->response->statusCode;
    }
	
	/*
    public function actionCallBack()
    {
        $data_form = Yii::$app->request->post();

        $logger = DebugLogger::instance("call-back");
        $logger->save($data_form, Yii::$app->request, "Данные формы");

        $phone = $data_form["Phone"];
        $phone = trim($phone);

        $duplicatesContact = Contact::findDuplicate($phone);

        if($duplicatesContact)
        {
            $contact = Contact::findById($duplicatesContact[0]);

            $deal = new Deal();
            $deal->title = Contact::hasDealInProgress($contact->id) ? "{$data_form['formname']}(повторная сделка)" : $data_form["formname"];
            $deal->contact_id = $contact->id;
            $deal->utm_campaign = $data_form['utm_campaign'] ?? null;
            $deal->utm_content = $data_form['utm_content'] ?? null;
            $deal->utm_medium = $data_form['utm_medium'] ?? null;
            $deal->utm_source = $data_form['utm_source'] ?? null;
            $deal->utm_term = $data_form['utm_term'] ?? null;

            Deal::create($deal);
        }
        else
        {
            $lead = new Lead();
            $lead->title = Lead::findDuplicate($phone) ? "{$data_form['formname']}(повторный лид)" : "{$data_form['formname']}";
            $lead->phone = [["VALUE" => mb_substr($phone, 0, 1) === "7" ? "+{$phone}" : $phone]];
            $lead->utm_campaign = $data_form['utm_campaign'] ?? null;
            $lead->utm_content = $data_form['utm_content'] ?? null;
            $lead->utm_medium = $data_form['utm_medium'] ?? null;
            $lead->utm_source = $data_form['utm_source'] ?? null;
            $lead->utm_term = $data_form['utm_term'] ?? null;
            
            Lead::create($lead);
        }
    }


    public function actionTechnicalConsultation()
    {
        $data_form = Yii::$app->request->post();

        $logger = DebugLogger::instance("technical-consultation");
        $logger->save($data_form, Yii::$app->request, "Данные формы");

        $phone = $data_form["Phone"];
        $phone = trim($phone);

        $duplicatesContact = Contact::findDuplicate($phone);

        if($duplicatesContact)
        {
            $contact = Contact::findById($duplicatesContact[0]);

            $deal = new Deal();
            $deal->title = Contact::hasDealInProgress($contact->id) ? "{$data_form['formname']}(повторная сделка)" : $data_form["formname"];
            $deal->contact_id = $contact->id;
            $deal->utm_campaign = $data_form['utm_campaign'] ?? null;
            $deal->utm_content = $data_form['utm_content'] ?? null;
            $deal->utm_medium = $data_form['utm_medium'] ?? null;
            $deal->utm_source = $data_form['utm_source'] ?? null;
            $deal->utm_term = $data_form['utm_term'] ?? null;

            Deal::create($deal);
        }
        else
        {
            $lead = new Lead();
            $lead->title = Lead::findDuplicate($phone) ? "{$data_form['formname']}(повторный лид)" : "{$data_form['formname']}";
            $lead->name = $data_form["Name"];
            $lead->phone = [["VALUE" => mb_substr($phone, 0, 1) === "7" ? "+{$phone}" : $phone]];
            $lead->utm_campaign = $data_form['utm_campaign'] ?? null;
            $lead->utm_content = $data_form['utm_content'] ?? null;
            $lead->utm_medium = $data_form['utm_medium'] ?? null;
            $lead->utm_source = $data_form['utm_source'] ?? null;
            $lead->utm_term = $data_form['utm_term'] ?? null;

            Lead::create($lead);
        }
    }
	*/
}
