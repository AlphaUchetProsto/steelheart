<?php

namespace app\models\BitrixCrm\Models\Crm;

use app\models\BitrixCrm\Collection\CollectionContacts;
use app\models\BitrixCrm\Models\BaseModel;

class CompanyModel extends BaseModel
{
    public int $id;
    public $title;
    public $phone;
    public $email;
    public $contactId;

    protected $contacts;

    public static function mapField()
    {
        return [
            'ID' => 'id',
            'TITLE' => 'title',
            'PHONE' => 'phone',
            'EMAIL' => 'email',
            'CONTACT_ID' => 'contactId',
        ];
    }

    public function setContacts(CollectionContacts $collectionContacts)
    {
        $this->contacts = $collectionContacts;

        return $this;
    }

    public function getContacts()
    {
        return $this->contacts;
    }
}
