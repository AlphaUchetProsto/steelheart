<?php

namespace app\models\BitrixCrm\Client\Crm;

use app\models\BitrixCrm\EntityService\Crm\Deals;
use app\models\BitrixCrm\EntityService\BaseService;
use app\models\BitrixCrm\EntityService\Crm\ProductSections;
use app\models\BitrixCrm\EntityService\Crm\Products;
use app\models\BitrixCrm\EntityService\Crm\Companies;
use app\models\BitrixCrm\EntityService\Crm\Items;
use app\models\BitrixCrm\EntityService\Crm\Contacts;
use app\models\BitrixCrm\EntityService\Crm\Activities;

class Client extends \app\models\BitrixCrm\Client\Client
{
    public function productSections()
    {
        $request = $this->buildRequest();

        return new ProductSections($request);
    }

    public function products()
    {
        $request = $this->buildRequest();

        return new Products($request);
    }

    public function companies()
    {
        $request = $this->buildRequest();

        return new Companies($request);
    }

    public function items()
    {
        $request = $this->buildRequest();

        return new Items($request);
    }

    public function deals()
    {
        $request = $this->buildRequest();

        return new Deals($request);
    }

    public function contacts()
    {
        $request = $this->buildRequest();

        return new Contacts($request);
    }

    public function activities()
    {
        $request = $this->buildRequest();

        return new Activities($request);
    }
}