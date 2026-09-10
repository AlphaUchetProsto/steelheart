<?php

namespace app\modules\tilda\models\bitrix;

use app\components\bitrix\GeneralBitrixInterface;
use app\models\bitrix\Bitrix;

class Deal extends Bitrix implements GeneralBitrixInterface
{
    public $id;
    public $title;
    public $stage_id;
    public $company_id;
    public $contact_id;
    public $assigned_by_id = 1;
    public $source_id = "UC_KZXHGG";
    public $utm_campaign;
    public $utm_content;
    public $utm_medium;
    public $utm_source;
    public $utm_term;

    use \app\components\bitrix\Deal;

    const MAP_FIELDS = [
        "ID" => "id",
        "TITLE" => "title",
        "CONTACT_ID" => "contact_id",
        "COMPANY_ID" => "company_id",
        "STAGE_ID" => "stage_id",
        "ASSIGNED_BY_ID" => "assigned_by_id",
        "SOURCE_ID" => "source_id"
        "UTM_CAMPAIGN" => "utm_campaign",
        "UTM_CONTENT" => "utm_content",
        "UTM_MEDIUM" => "utm_medium",
        "UTM_SOURCE" => "utm_source",
        "UTM_TERM" => "utm_term",
    ];

    public function __construct($fields = [])
    {
        parent::__construct($fields, self::MAP_FIELDS);
    }
}