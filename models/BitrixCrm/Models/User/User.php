<?php

namespace app\models\BitrixCrm\Models\User;

use app\models\BitrixCrm\Models\BaseModel;

class User extends BaseModel
{
    public function getShortName()
    {
        return $this->fields->get('LAST_NAME') . ' ' . $this->fields->get('NAME');
    }
}
