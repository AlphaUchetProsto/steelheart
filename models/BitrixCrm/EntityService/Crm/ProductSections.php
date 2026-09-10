<?php

namespace app\models\BitrixCrm\EntityService\Crm;

use app\models\BitrixCrm\Collection\BaseCollection;
use app\models\BitrixCrm\Models\BaseModel;

class ProductSections extends \app\models\BitrixCrm\EntityService\BaseService
{
    protected $collectionClass = BaseCollection::class;
    protected $itemClass = BaseModel::class;

    public function get(int $id)
    {
        $response = $this->request->request('crm.productsection.get', ['ID' => $id]);

        return new $this->itemClass($response->getResponse());
    }

    public function list(array $params = [])
    {
        $response = $this->request->request('crm.productsection.list', $params);

        return $this->createCollection($response->getResponse());
    }
}
