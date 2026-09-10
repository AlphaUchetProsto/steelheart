<?php

namespace app\modules\vendor_order\models;

use app\models\BitrixCrm\Models\BaseModel;

class ProductModel extends BaseModel
{
    public function getPropertyValue(string $fieldId)
    {
        $fieldData = $this->fields->get($fieldId);

        if (is_array($fieldData)) {
            return $fieldData['value'];
        }

        return null;
    }

    public function getArticle()
    {
        return $this->getPropertyValue('PROPERTY_107');
    }
}