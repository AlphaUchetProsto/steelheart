<?php

namespace app\models\BitrixCrm\Client\Catalog;

use app\models\BitrixCrm\EntityService\BaseService;
use app\models\BitrixCrm\EntityService\Catalog\Documents;
use app\models\BitrixCrm\EntityService\Catalog\DocumentProducts;
use app\models\BitrixCrm\EntityService\Catalog\DocumentContractor;
use app\models\BitrixCrm\EntityService\Crm\Products;

class Client extends \app\models\BitrixCrm\Client\Client
{
    public function documents()
    {
        $request = $this->buildRequest();

        return new Documents($request);
    }

    public function documentProducts()
    {
        $request = $this->buildRequest();

        return new DocumentProducts($request);
    }

    public function documentContractor()
    {
        $request = $this->buildRequest();

        return new DocumentContractor($request);
    }
}