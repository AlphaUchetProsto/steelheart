<?php

namespace app\models\BitrixCrm\EntityService\Crm;

use app\models\BitrixCrm\Collection\BaseCollection;
use app\models\BitrixCrm\Collection\CollectionItems;
use app\models\BitrixCrm\Models\BaseModel;
use Tightenco\Collect\Support\Collection;
use app\models\BitrixCrm\EntityService\BaseService;

class Items extends BaseService
{
    protected $collectionClass = BaseCollection::class;
    protected $itemClass = BaseModel::class;

    public function getOne(int $entityTypeId, int $id)
    {
        $data = $this->request->request('crm.item.get', ['entityTypeId' => $entityTypeId, 'id' => $id])->getResponse();

        return (new $this->itemClass)->fromArray($data);
    }

    public function getFields(int $entityTypeId)
    {
        return $this->request->request('crm.item.fields', ['entityTypeId' => $entityTypeId])->getResponse();
    }

    public function list(array $params = [])
    {
        $data = $this->request->request('crm.item.list', $params)->getResponse();

        return $this->createCollection($data['items']);
    }

    public function create(BaseModel $item)
    {
        return $this->request->request('crm.item.add', ['entityTypeId' => $item->entityTypeId, 'fields' => $item->collectFieldValue()]);
    }

    public function update(BaseModel $item)
    {
        return $this->request->request('crm.item.update', ['entityTypeId' => $item->entityTypeId, 'id' => $item->id, 'fields' => $item->collectFieldValue()]);
    }

    public function multipleUpdate(CollectionItems $collectionItems)
    {
        $commands = new Collection();

        foreach ($collectionItems as $item){
            $commands->push($this->request->buildCommand('crm.item.update', ['entityTypeId' => $item->entityTypeId, 'id' => $item->id, 'fields' => $item->collectFieldValue()]));
        }

        return $this->request->batchRequest($commands->toArray());
    }
}
