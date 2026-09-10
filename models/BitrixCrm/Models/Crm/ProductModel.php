<?php

namespace app\models\BitrixCrm\Models\Crm;

use app\models\BitrixCrm\Models\BaseModel;
use Tightenco\Collect\Support\Collection;

class ProductModel extends BaseModel
{
    protected $variations = [];
    protected $price;

    public static function mapFields(): Collection
    {
        return new Collection([
            'ID' => 'id',
            'NAME' => 'name',
            'DATE_CREATE' => 'dateCreate',
            'DESCRIPTION' => 'description',
        ]);
    }

    public function setProductId(int $value): self
    {
        $this->fields->put('ID', $value);

        return $this;
    }

    public function addVariation(BaseModel $variation) :self
    {
        $this->variations[] = $variation;

        return $this;
    }

    public function setPrice(BaseModel $price):self
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice()
    {
        if ($this->isExistVariation()) {
            return $this->variations[0]->getPrice();
        }

        return $this->price ? $this->price->getFieldValue('price') : 0;
    }

    public function getVariations()
    {
        return $this->variations;
    }
    
    public function hasManyVariation()
    {
        return $this->isExistVariation() && count($this->variations) > 1;
    }

    public function isExistVariation():bool
    {
        return !empty($this->variations);
    }
}
