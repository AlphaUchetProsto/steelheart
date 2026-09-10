<?php

namespace app\models\BitrixCrm\Models;

use Tightenco\Collect\Support\Collection;
use function Symfony\Component\String\u;

class BaseModel
{
    protected Collection $fields;

    public function __construct(array $fields = [])
    {
        $this->fields = new Collection($fields);
    }
    
    public function __set($name, $value) {
        throw new \Exception('Свойства ' . $name . ' не существует в ' . get_class($this));
    }

    /*public function __call($name, $params)
    {
        if (u($name)->containsAny('get')) {
            $variable = u($name)->after('get')->camel()->toString();

            if (static::mapFields()->contains($variable)) {
                return $this->fields->get(static::mapFields()->search($variable));
            }

            throw new \Exception('Свойства ' . $variable . ' не существует в ' . get_class($this));
        }

        throw new \Exception('Метод ' . $name . ' не существует в ' . get_class($this));
    }*/

    public static function mapFields():Collection
    {
        return new Collection();
    }

    public function getFieldValue(string $path)
    {
        $path = explode('.', $path);

        $value = $this->fields->get($path[0]);

        if (count($path) > 0) {
            $path = array_slice($path, 1);

            foreach ($path as $point)
            {
                if (!is_array($value) || !array_key_exists($point, $value)) {
                    return null;
                }

                $value = $value[$point];
            }
        }

        return $value;
    }
    
    public function getFields():array
    {
        return $this->fields->toArray();
    }

    public function isExist():bool
    {
        return $this->fields->isNotEmpty();
    }

    public function setFieldValue(string $fieldName, $value):self
    {
        $this->fields->put($fieldName, $value);

        return $this;
    }
    
    public function toJson()
    {
        return $this->fields->toJson();
    }

    public function merge(BaseModel $model)
    {
        $compareFields = $model->getFields();

        foreach ($this->getFields() as $fieldId => $value)
        {
            if (array_key_exists($fieldId, $compareFields) && !is_array($compareFields[$fieldId])) {
                $this->fields->put($fieldId, $compareFields[$fieldId]);
                continue;
            }

            if (array_key_exists($fieldId, $compareFields) && is_array($compareFields[$fieldId])) {
                $currentDataField = collect($value)->map(fn($item) => collect($item)->mapWithKeys(fn($item, $key) => [u($key)->lower()->toString() => $item])->toArray());

                foreach ($compareFields[$fieldId] as $index => $data)
                {
                    $rowData = collect($data)->mapWithKeys(fn($item, $key) => [u($key)->lower()->toString() => $item]);

                    $isExistValue = $currentDataField->search(function ($item) use ($rowData) {
                        return $item['value'] == $rowData->get('value');
                    }) !== false;

                    if ($fieldId == 'PHONE') {
                        $isExistValue = $currentDataField->search(function ($item) use ($rowData) {
                            return preg_replace('/[^0-9]/', '', $item['value']) == preg_replace('/[^0-9]/', '', $rowData->get('value'));
                        }) !== false;
                    }

                    if (!$isExistValue) {
                        $value = collect($value)->push($data)->toArray();
                    }
                }

                $this->fields->put($fieldId, $value);
            }
        }
        
        return $this;
    }
}