<?php

namespace app\models\BitrixCrm\Collection;

use Tightenco\Collect\Support\Collection;

class BaseCollection extends Collection
{
    protected $amount;
    protected $page;

    public function __construct($items = [])
    {
        parent::__construct($items);
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount(int $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getLastId()
    {
        $id = $this->get($this->count() - 1)->getFieldValue('ID');

        if (!$id) {
            $id = $this->get($this->count() - 1)->getFieldValue('id');
        }

        return $id;
    }

    public function setFieldValue(string $fieldName, $value):self
    {
        $this->items = $this->map(function ($item) use($fieldName, $value) {
            return $item->setFieldValue($fieldName, $value);
        })->toArray();

        return $this;
    }

    public function getItemByFieldValue(string $fieldId, $value)
    {
        $itemIndex = $this->search(function ($item) use($fieldId, $value){
            return $item->getFieldValue($fieldId) == $value;
        });

        if ($itemIndex !== false) {
            return $this->get($itemIndex);
        }

        return false;
    }

    public function setPage(int $page)
    {
        $this->page = $page;

        return $this;
    }
}
