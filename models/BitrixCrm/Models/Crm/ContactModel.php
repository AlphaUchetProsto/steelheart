<?php

namespace app\models\BitrixCrm\Models\Crm;

use app\models\BitrixCrm\Models\BaseModel;
use Tightenco\Collect\Support\Collection;

class ContactModel extends BaseModel
{
    public static function mapFields(): Collection
    {
        return new Collection([
            'ID' => 'id',
            'NAME' => 'name',
            'SECOND_NAME' => 'secondName',
            'LAST_NAME' => 'lastName',
            'PHONE' => 'phone',
            'EMAIL' => 'email',
            'COMPANY_ID' => 'companyId',
            'SOURCE_ID' => 'sourceId'
        ]);
    }

    public function setEmail(string $value, ?string $type = 'WORK', int $id = null): self
    {
        $currentEmail = new Collection($this->fields->get('EMAIL') ?? []);
        $currentEmail->push(['VALUE' => $value, 'VALUE_TYPE' => $type, 'ID' => $id]);

        $this->fields->put('EMAIL', $currentEmail->toArray());

        return $this;
    }

    public function setPhone(string $value, ?string $type = 'WORK', int $id = null): self
    {
        $currentEmail = new Collection($this->fields->get('PHONE') ?? []);
        $currentEmail->push(['VALUE' => $value, 'VALUE_TYPE' => $type, 'ID' => $id]);

        $this->fields->put('PHONE', $currentEmail->toArray());

        return $this;
    }

    public function getPhones()
    {
        if (!empty($this->fields->get('PHONE'))) {
            return collect($this->fields->get('PHONE'))->map(fn($item) => preg_replace('/[^0-9]/', '', $item['VALUE']))->toArray();
        }

        return [];
    }

    public function getEmails()
    {
        if (!empty($this->fields->get('EMAIL'))) {
            return collect($this->fields->get('EMAIL'))->map(fn($item) => preg_replace('/[^0-9]/', '', $item['VALUE']))->toArray();
        }

        return [];
    }
}
