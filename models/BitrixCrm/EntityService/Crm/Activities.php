<?php

namespace app\models\BitrixCrm\EntityService\Crm;

use app\models\BitrixCrm\Client\Client;
use app\models\BitrixCrm\Collection\BaseCollection;
use app\models\BitrixCrm\Collection\CollectionCompanies;
use app\models\BitrixCrm\Models\Crm\CompanyModel;
use app\models\BitrixCrm\Models\Crm\ContactModel;
use app\models\BitrixCrm\Models\BaseModel;
use Tightenco\Collect\Support\Collection;
use app\models\BitrixCrm\EntityService\BaseService;

class Activities extends BaseService
{
    protected $collectionClass = BaseCollection::class;
    protected $itemClass = BaseModel::class;

    public function list(array $params = [])
    {
        $response = $this->request->request('crm.activity.list', $params);

        return $this->createCollection($response->getResponse());
    }
}
