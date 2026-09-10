<?php

namespace app\models\BitrixCrm\EntityService\Crm;

use app\models\BitrixCrm\Client\Client;
use app\models\BitrixCrm\Collection\CollectionCompanies;
use app\models\BitrixCrm\Collection\CollectionDeals;
use app\models\BitrixCrm\Models\Crm\CompanyModel;
use app\models\BitrixCrm\Models\Crm\ContactModel;
use app\models\BitrixCrm\Models\BaseModel;
use app\models\BitrixCrm\Models\Crm\DealModel;
use Tightenco\Collect\Support\Collection;
use app\models\BitrixCrm\EntityService\BaseService;

class Deals extends BaseService
{
    protected $collectionClass = CollectionDeals::class;
    protected $itemClass = BaseModel::class;

    public function get(int $int)
    {
        $data = $this->request->request('crm.deal.get', ['ID' => $int])->getResponse();

        return new $this->itemClass($data);
    }

    public function getFields()
    {
        return $this->request->request('crm.deal.fields');
    }

    public function list(array $params = [])
    {
        $data = $this->request->request('crm.deal.list', $params)->getResponse();

        return $this->createCollection($data);
    }

    public function add(BaseModel $deal)
    {
        $deal->setFieldValue('ID', $this->request->request('crm.deal.add', ['fields' => $deal->getFields()])->getResponse());

        return $deal;
    }

    public function addWithProducts(BaseModel $deal)
    {
        $productRows = collect($deal->getProducts())->map(function ($item){
            return ['PRODUCT_ID' => $item->getFieldValue('ID')];
        })->toArray();

        $commands['create_deal'] = $this->request->buildCommand('crm.deal.add', ['fields' => $deal->getFields()]);
        $commands['set_productrows'] = $this->request->buildCommand('crm.deal.productrows.set', ['id' => '$result[create_deal]','rows' => $productRows]);

        return $this->request->batchRequest($commands);
    }

    public function update(BaseModel $deal)
    {
        return $this->request->request('crm.deal.update', ['ID' => $deal->getId(), 'fields' => $deal->getFields()]);
    }

    public function delete(BaseModel $deal)
    {
        return $this->request->request('crm.deal.delete', ['ID' => $deal->getId()]);
    }

    public function updateWithContact(BaseModel $deal)
    {
        $commands['update_deal'] = $this->request->buildCommand('crm.deal.update', ['ID' => $deal->getId(), 'fields' => $deal->getFields()]);
        $commands['update_contact'] = $this->request->buildCommand('crm.contact.update', ['ID' => $deal->getContact()->getId(), 'fields' => $deal->getContact()->getFields()]);

        return $this->request->batchRequest($commands);
    }

    public function multipleUpdate(CollectionDeals $collectionDeals)
    {
        $commands = new Collection();

        foreach ($collectionDeals as $deal){
            $commands->push($this->buildCommand('crm.company.update', ['ID' => $deal->id, 'fields' => $deal->collectFieldValue()]));
        }

        return $this->batchRequest($commands->toArray());
    }
}
