<?php

namespace app\models\BitrixCrm\EntityService\Crm;

use app\models\BitrixCrm\Collection\CollectionProducts;
use app\models\BitrixCrm\EntityService;

use app\models\BitrixCrm\Models\BaseModel;
use app\models\BitrixCrm\Models\Crm\ProductModel;

class Products extends EntityService\BaseService
{
    protected $collectionClass = CollectionProducts::class;
    protected $itemClass = ProductModel::class;

    public function get($id)
    {
        $response = $this->request->request('crm.product.get', ['ID' => $id]);

        return new $this->itemClass($response->getResponse());
    }

    public function fields()
    {
        return $this->request->request('crm.product.fields')->getResponse();
    }

    public function list(array $params = [])
    {
        $response = $this->request->request('crm.product.list', $params);

        return $this->createCollection($response->getResponse())->setAmount($response->getAmount())->setPage($response->getPage());
    }

    public function add(BaseModel $product)
    {
        return $this->request->request('crm.product.add', ['fields' => $product->getFields()]);
    }

    public function getWithVariations(int $id)
    {
        $commands['get_product'] = $this->request->buildCommand('crm.product.get', ['ID' => $id]);
        $commands['get_catalogs'] = $this->request->buildCommand('catalog.catalog.list', ['filter' => ['=name' => 'Товарный каталог CRM (предложения)']]);
        $commands['get_variations'] = $this->request->buildCommand('catalog.product.offer.list', [
            'select' => ['id', 'iblockId', '*'],
            'filter' => ['iblockId' => '$result[get_catalogs][catalogs][0][id]', '=parentId' => $id]
        ]);

        $response = $this->request->batchRequest($commands)->getResponse();

        $commandsGetPrice = collect($response['get_variations']['offers'])->mapWithKeys(fn($item) => [$item['id'] => $this->request->buildCommand('catalog.price.list', ['filter' => ['productId' => $item['id']]])])->toArray();
        $responseGetPrice = $this->request->batchRequest($commandsGetPrice)->getResponse();

        $model = new $this->itemClass($response['get_product']);

        foreach ($response['get_variations']['offers'] as $variation)
        {
            $variation = new $this->itemClass($variation);

            if (isset($responseGetPrice[$variation->getFieldValue('id')]) && !empty($responseGetPrice[$variation->getFieldValue('id')]['prices'])) {
                $variation->setPrice(new BaseModel($responseGetPrice[$variation->getFieldValue('id')]['prices'][0]));
            }

            $model->addVariation($variation);
        }

        return $model;
    }
}
