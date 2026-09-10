<?php


namespace app\models\BitrixCrm\EntityService;

use app\models\BitrixCrm\Client\Request\Request;
use app\models\BitrixCrm\Collection\BaseCollection;
use app\models\BitrixCrm\Collection\CollectionItems;
use app\models\BitrixCrm\Models\ContactModel;
use app\models\BitrixCrm\Models\BaseModel;

abstract class BaseService
{
    protected Request $request;
    protected $collectionClass = BaseCollection::class;
    protected $itemClass = BaseModel::class;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function setItemModel(BaseModel $class) :self
    {
        $this->itemClass = get_class($class);

        return $this;
    }

    public function setCollectionModel($class) :self
    {
        $this->collectionClass = get_class($class);

        return $this;
    }

    public function createCollection(array $entities)
    {
        return \app\models\BitrixCrm\Collector::createCollection($entities, $this->itemClass, $this->collectionClass);
    }
}
