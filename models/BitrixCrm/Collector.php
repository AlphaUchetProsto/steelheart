<?php

namespace app\models\BitrixCrm;

use app\models\BitrixCrm\Collection\BaseCollection;
use app\models\BitrixCrm\Models\BaseModel;

class Collector
{
    public static function createCollection($entities, $modelClass = BaseModel::class, $collectionClass = BaseCollection::class)
    {
        $collectionModel = new $collectionClass;

        foreach ($entities as $entity) {
            $collectionModel->push(new $modelClass($entity));
        }

        return $collectionModel;
    }
}