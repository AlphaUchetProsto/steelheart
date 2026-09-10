<?php

namespace app\modules\vendor_order\models\db;

use yii\db\ActiveRecord;

class BrandTable extends ActiveRecord
{
    public static function tableName()
    {
        return '{{brands}}';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['name'], 'unique'],
        ];
    }

    public static function findOrCreateId($name)
    {
        if (empty($name)) {
            return null;
        }

        $name = trim($name);

        $brand = self::findOne(['name' => $name]);

        if ($brand) {
            return $brand->id;
        }

        $brand = new self();
        $brand->name = $name;

        if ($brand->save()) {
            return $brand->id;
        }

        \Yii::error("Ошибка создания бренда '{$name}': " . print_r($brand->getErrors(), true));
        return null;
    }

    public static function getIdByName($name, $createIfNotExists = true)
    {
        if (empty($name)) {
            return null;
        }

        $brand = self::findOne(['name' => trim($name)]);

        if ($brand) {
            return $brand->id;
        }

        if ($createIfNotExists) {
            $brand = new self();
            $brand->name = trim($name);
            if ($brand->save()) {
                return $brand->id;
            }
        }

        return null;
    }

    public static function getNameById($id)
    {
        if (empty($id)) {
            return null;
        }

        $brand = self::findOne($id);
        return $brand ? $brand->name : null;
    }

    public static function getAllAsMap()
    {
        return self::find()
            ->select(['id', 'name'])
            ->indexBy('id')
            ->column();
    }

    public static function getAllForSelect()
    {
        return self::find()
            ->select(['name', 'id'])
            ->orderBy('name')
            ->indexBy('id')
            ->column();
    }
}