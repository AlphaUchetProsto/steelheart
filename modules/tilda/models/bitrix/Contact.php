<?php

namespace app\modules\tilda\models\bitrix;


use app\components\bitrix\CrmInterface;
use app\components\bitrix\GeneralBitrixInterface;
use app\models\bitrix\Bitrix;

class Contact extends Bitrix implements GeneralBitrixInterface, CrmInterface
{
    public $id;
    public $name;
    public $last_name;
    public $second_name;

    use \app\components\bitrix\Contact;

    const MAP_FIELDS = [
        "ID" => "id",
        "NAME" => "name",
        "LAST_NAME" => "last_name",
        "SECOND_NAME" => "second_name",
    ];

    public function __construct($fields = [])
    {
        parent::__construct($fields, static::MAP_FIELDS);
    }

    public static function hasDealInProgress($id)
    {
        $webhook = Bitrix::BX24init();
        $webhook->request("crm.deal.list", ["filter" => ["CONTACT_ID" => $id, "STAGE_SEMANTIC_ID" => "P"]]);

        return $webhook->getLastResponse()["total"] > 0;
    }
}