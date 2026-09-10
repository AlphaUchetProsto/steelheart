<?php


namespace app\modules\tilda\models\bitrix;

use app\components\bitrix\GeneralBitrixInterface;
use app\models\bitrix\Bitrix;

class Lead extends Bitrix implements GeneralBitrixInterface
{
    public $id;
    public $title;
    public $name;
    public $phone;
    public $source_id = "UC_KZXHGG";
    public $assigned_by_id = 1;
    public $utm_campaign;
    public $utm_content;
    public $utm_medium;
    public $utm_source;
    public $utm_term;

    const MAP_FIELDS = [
        "ID" => "id",
        "TITLE" => "title",
        "NAME" => "name",
        "PHONE" => "phone",
        "ASSIGNED_BY_ID" => "assigned_by_id",
        "SOURCE_ID" => "source_id",
        "UTM_CAMPAIGN" => "utm_campaign",
        "UTM_CONTENT" => "utm_content",
        "UTM_MEDIUM" => "utm_medium",
        "UTM_SOURCE" => "utm_source",
        "UTM_TERM" => "utm_term",
    ];

    use \app\components\bitrix\Lead;

    public function __construct($fields = [])
    {
        parent::__construct($fields, self::MAP_FIELDS);
    }

    public static function findDuplicate($phone)
    {
        $webhook = static::BX24init();

        $phone = preg_replace('/[^0-9]/', '', $phone);

        $commands = $webhook->buildCommands("crm.lead.list", [
            ["filter" => ["PHONE" => "8" . mb_substr($phone, 1), "!STATUS_SEMANTIC_ID" => "S"]],
            ["filter" => ["PHONE" => "7" . mb_substr($phone, 1), "!STATUS_SEMANTIC_ID" => "S"]],
            ["filter" => ["PHONE" => "+7" . mb_substr($phone, 1), "!STATUS_SEMANTIC_ID" => "S"]],
        ]);

        $webhook->batchRequest($commands);

        return array_sum($webhook->getLastResponse()["result"]["result_total"]) > 0;
    }
}