<?php

namespace app\modules\hh\models;

use app\models\bitrix\Bitrix;
use app\components\bitrix\CrmInterface;
use app\components\bitrix\GeneralBitrixInterface;
use Tightenco\Collect\Support\Collection;

class Contact extends Bitrix implements GeneralBitrixInterface, CrmInterface
{
    public $id;
    public $contact_type;
    public $name;
    public $last_name;
    public $second_name;
    public $phone = [];
    public $email = [];
    public $password;
    public $companyID;

    use \app\components\bitrix\Contact;

    const MAP_FIELDS = [
        "ID" => "id",
        "NAME" => "name",
        "LAST_NAME" => "last_name",
        "SECOND_NAME" => "second_name",
        "PHONE" => "phone",
        "EMAIL" => "email",
        "COMPANY_ID" => "companyID",
        "TYPE_ID" => "contact_type",
    ];

    public function __construct($fields = [])
    {
        parent::__construct($fields, self::MAP_FIELDS);
    }

    public function getDeals()
    {
        return \app\models\bitrix\Deal::getList([
            "CONTACT_ID" => $this->id,
            ">=DATE_CREATE" => date('Y-m-d', strtotime('first day of this month')),
            "<=END_DATE_PLAN" => date('Y-m-d', strtotime('last day of this month')),
        ]);
    }

    public function getPhone($number = false)
    {
        return $number !== false ? $this->phone[$number] : $this->phone;
    }

    public function addPhone($phone, $type):void
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        $this->phone[] = ["VALUE" => $phone, "TYPE" => $type];
    }

    public static function create($data)
    {
        $bitrix = Bitrix::Bx24init();

        foreach ($data['contact'] as $contact) {
            if ($contact['type']['id'] == 'cell' && !is_null($contact['value'])) {
                $originalPhones[] = $contact['value']['formatted'];
            } elseif ($contact['type']['id'] == 'email' && !is_null($contact['value'])) {
                $originalEmails[] = $contact['value'];
            }
        }

        if (isset($originalPhones)) {
            $phones = new Collection();
            foreach ($originalPhones as $originalPhone) {
                $phone = preg_replace('/[^0-9]/', '', $originalPhone);

                $phones->push($phone);
                $phones->push(mb_substr($phone, 1));
            }

            $commandRow['phone'] = $bitrix->buildCommand('crm.duplicate.findbycomm', [
                'entity_type' => "CONTACT",
                'type' => "PHONE",
                'values' => $phones->toArray(),
            ]);
        }

        if (isset($originalEmails)) {
            $commandRow['email'] = $bitrix->buildCommand('crm.duplicate.findbycomm', [
                'entity_type' => "CONTACT",
                'type' => "EMAIL",
                'values' => $originalEmails,
            ]);
        }

        if (isset($commandRow)) {
            $contact = collect($bitrix->batchRequest($commandRow))->flatten(2);
        } else {
            return false;
        }

//        dd($contact);

        if ($contact->isNotEmpty()) {
            $contactData = $bitrix->request('crm.contact.get', [
                'id' => $contact[0]
            ]);

//            dd($contactData);

            $emails = [];
            $phones = [];

            if (isset($originalEmails)) {
                if (isset($contactData['EMAIL'])) {
                    foreach ($contactData['EMAIL'] as $email) {
                        $emails[] = $email['VALUE'];
                    }

                    $emailsToAdd = array_diff($originalEmails, $emails);

                    if (!empty($emailsToAdd)) {
                        foreach ($emailsToAdd as $emailToAdd) {
                            $fields['EMAIL'][] = ['VALUE' => $emailToAdd, 'VALUE_TYPE' => 'WORK'];
                        }
                    }
                } else {
                    foreach ($originalEmails as $emailToAdd) {
                        $fields['EMAIL'][] = ['VALUE' => $emailToAdd, 'VALUE_TYPE' => 'WORK'];
                    }
                }
            }

            if (isset($originalPhones)) {
                if (isset($contactData['PHONE'])) {
                    foreach ($contactData['PHONE'] as $phone) {
                        $phones[] = $phone['VALUE'];
                    }

                    $phonesToAdd = array_diff($originalPhones, $phones);

                    if (!empty($phonesToAdd)) {
                        foreach ($phonesToAdd as $phoneToAdd) {
                            $fields['PHONE'][] = ['VALUE' => $phoneToAdd, 'VALUE_TYPE' => 'WORK'];
                        }
                    }
                } else {
                    foreach ($originalEmails as $emailToAdd) {
                        $fields['PHONE'][] = ['VALUE' => $emailToAdd, 'VALUE_TYPE' => 'WORK'];
                    }
                }
            }

            if (isset($fields)) {
                $bitrix->request('crm.contact.update', [
                    'id' => $contact[0],
                    'fields' => $fields
                ]);
            }

            return $contact[0];
        } else {
            $fields = [
                'NAME' => $data['first_name'] ?? 'скрыто',
                'LAST_NAME' => $data['last_name'] ?? 'ФИО',
                'SECOND_NAME' => $data['middle_name'] ?? 'соискателем',
                'ASSIGNED_BY_ID' => 535,
            ];

            if (isset($originalPhones)) {
                foreach ($originalPhones as $originalPhone) {
                    $fields['PHONE'][] = ['VALUE' => $originalPhone, 'VALUE_TYPE' => 'WORK'];
                }
            }
            if (isset($originalEmails)) {
                foreach ($originalEmails as $originalEmail) {
                    $fields['EMAIL'][] = ['VALUE' => $originalEmail, 'VALUE_TYPE' => 'WORK'];
                }
            }

            return $bitrix->request('crm.contact.add', [
                    'fields' => $fields
            ]);
        }
    }
}
