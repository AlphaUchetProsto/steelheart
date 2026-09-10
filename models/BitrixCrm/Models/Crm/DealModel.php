<?php

namespace app\models\BitrixCrm\Models\Crm;

use app\models\BitrixCrm\Models\BaseModel;
use Tightenco\Collect\Support\Collection;

class DealModel extends BaseModel
{
    protected $company;
    protected $contact;
    protected $product;

    public static function mapFields(): Collection
    {
        return new Collection([
            'ID' => 'id',
            'TITLE' => 'title',
            'CONTACT_ID' => 'contactId',
            'DATE_CREATE' => 'dateCreate',
        ]);
    }

    public function setContact(BaseModel $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    public function getContact()
    {
        return $this->contact;
    }

    public function setContactId(int $value): self
    {
        $this->fields->put('CONTACT_ID', $value);

        return $this;
    }

    public function setCategoryId(int $value): self
    {
        $this->fields->put('CATEGORY_ID', $value);

        return $this;
    }

    public function setSourceId(string $value): self
    {
        $this->fields->put('SOURCE_ID', $value);

        return $this;
    }

    public function setProducts(array $products)
    {
        $this->product = $products;

        return $this;
    }

    public function getProducts()
    {
        return $this->product;
    }

    public function getStageSemanticName()
    {
        $stageSemanticId = $this->getFieldValue('STAGE_SEMANTIC_ID');

        if($stageSemanticId == 'S') {
            return 'Завершена';
        } elseif ($stageSemanticId == 'F') {
            return 'Отклонена';
        }

        return "В работе";
    }
}
