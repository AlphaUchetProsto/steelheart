<?php

namespace app\models\BitrixCrm\Collection;

class CollectionItems extends BaseCollection
{
    public function __construct($items = [])
    {
        parent::__construct($items);
    }

    public function getById($id = null)
    {
        if(is_null($id))
        {
            return false;
        }

        $user = collect($this->items)->filter(function ($user) use($id){
            return $user['ID'] == $id;
        })->values();

        return $user->isNotEmpty() ? $user->get(0) : false;
    }

    public function getValueField($id = null, $fieldId)
    {
        if(is_null($id))
        {
            return " ";
        }

        $dataField = collect($this->items[$fieldId]['items'])->filter(function ($items) use($id){
            return $items['ID'] == $id;
        })->values();

        return $dataField->get(0)['VALUE'];
    }
}
