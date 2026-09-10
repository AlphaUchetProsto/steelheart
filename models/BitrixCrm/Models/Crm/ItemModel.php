<?php

namespace app\models\BitrixCrm\Models\Crm;

use app\models\BitrixCrm\Collection\CollectionCompanies;
use app\models\BitrixCrm\Collection\CollectionContacts;

class ItemModel extends CrmBaseModel
{
    public int $id;
    public $entityTypeId;
    public $title;
    public $contactId;
    public $companyId;
    public $stageId;

    public static function mapField()
    {
        return [
            'id' => 'id',
            'title' => 'title',
            'contactId' => 'contactId',
            'companyId' => 'companyId',
            'entityTypeId' => 'entityTypeId',
            'stageId' => 'stageId',
        ];
    }

    public function collectFieldValue(): array
    {
        $data = collect(parent::collectFieldValue());
        $data = $data->filter(function ($item, $key){
           return $key != 'id' && $key != 'ID';
        });

        return $data->toArray();
    }
}
