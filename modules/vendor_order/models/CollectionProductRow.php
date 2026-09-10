<?php

namespace app\modules\vendor_order\models;

use app\models\BitrixCrm\Models\BaseModel;
use app\models\logger\DebugLogger;
use app\modules\vendor_order\models\db\ProductVariationTable;
use app\modules\vendor_order\models\db\ProductRowVariationTable;
use app\modules\vendor_order\models\db\ProductRowTable;
use app\modules\vendor_order\models\db\ProductTable;
use app\modules\vendor_order\models\db\BrandTable;
use app\modules\vendor_order\models\db\BenchmarkingRowTable;
use app\modules\vendor_order\models\OpenCartApi;
use Tightenco\Collect\Support\Collection;

use function Symfony\Component\String\u;

class CollectionProductRow extends Collection
{
    protected $supplier = [];

    public function getCollectionId()
    {
        return $this->map(function ($item) {
            return $item->id + 2;
        });
    }

    public function getCollectionProductId()
    {
        return $this->map(function ($item) {
            return $item->productId + 2;
        });
    }

    public function getSupplier()
    {
        $client = Client::instance();

        $commands = $this->map(function ($item) use($client) {
            return $client->buildCommand('entity.item.get', ['ENTITY' => 'supplier', 'filter' => ['PROPERTY_productRowId' => $item->id], 'start' => -1]);
        })->toArray();

        $this->supplier = $client->batchRequest($commands)['result']['result'];

        return $this->supplier;
    }

    public function deleteSupplier()
    {
        foreach ($this->items as $item)
        {
            ProductRowVariationTable::deleteAll(['=', 'product_row_id', $item->id]);
        }

        return $this;
    }

    public function parseSite()
    {
        $collectionContent = new Collection();

        foreach ($this->items as $item)
        {
            $formattedArticle = preg_replace('/[^a-zA-ZА-Яа-я0-9]/', '', $item->getProduct()->article);

            $itemData = [
                "id" => $item->id,
                "name" => $item->getProduct()->name,
                "productId" => $item->productId,
                "article" => $item->getProduct()->article,
                "type" => $item->type,
                "quantity" => $item->quantity,
                "brand" => $item->brand,
                "typeMachine" => $item->typeMachine,
                "dealId" => $item->dealId,
            ];

            $collectionContent->put($itemData['article'], $itemData);

            if (u($formattedArticle)->trim()->toString() != u($item->getProduct()->article)->trim()->toString()) {
                $itemData['article'] = $formattedArticle;
                $collectionContent->put($itemData['article'], $itemData);
            }
        }

        if ($collectionContent->isNotEmpty()) {

            $http = new \GuzzleHttp\Client(['base_uri' => 'https://steelheart-win.uchetprosto.ru/']);
            $http->request('POST', '', [
                'form_params' => $collectionContent->toArray(),
            ]);


            $this->afterParse();
        }

        return $this;
    }

    public static function parseApi_searchArticle($article)
    {
        $logger = DebugLogger::instance('parse-api-search-article');
        $api = new OpenCartApi();
        $result = $api->request("POST", "api/product/find", ["model" => [$article]]);

        $logger->save($result);
//        return json_encode(json_decode($result, true)['products'][0]);
        return end($result["products"]);
    }

    public function parseApi()
    {
        $logger = DebugLogger::instance('parse-api');
        $collectionContent = new Collection();

        foreach ($this->items as $item)
        {
            $formattedArticle = preg_replace('/[^a-zA-ZА-Яа-я0-9]/', '', $item->getProduct()->article);

            $itemData = [
                "id" => $item->id,
                "name" => $item->getProduct()->name,
                "productId" => $item->productId,
                "article" => $item->getProduct()->article,
                "type" => $item->type,
                "quantity" => $item->quantity,
                "brand" => $item->brand,
                "typeMachine" => $item->typeMachine,
                "dealId" => $item->dealId,
            ];

            $collectionContent->put($itemData['article'], $itemData);

            if (u($formattedArticle)->trim()->toString() != u($item->getProduct()->article)->trim()->toString()) {
                $itemData['article'] = $formattedArticle;
                $collectionContent->put($itemData['article'], $itemData);
            }
        }

        $logger->save($collectionContent);

        $api = new OpenCartApi();
        $result = $api->request("POST", "api/product/find", ["model" => $collectionContent->keys()->toArray()]);

        $logger->save($result);

        $type_detail = [
            "1" => 3, // Новая любая
            "0" => 1, // БУ
            "2" => 6, // Восстановленная
        ];

        $createdCount = 0;
        $skippedCount = 0;
        $linksCreated = 0;

        foreach ($this->items as $item)
        {
            foreach ($result["products"] as $productModel => $product) {
                if (str_contains($productModel, trim($item->getProduct()->article))) {

                    $productId = $item->productId;
                    $price = preg_replace('/[^0-9.]/', '', $product['price']);
                    $weight = preg_replace('/[^0-9.]/', '', $product['weight']);
                    $brandId = BrandTable::findOrCreateId($product['manufacturer'] ?? null);
                    $typeDetailId = $type_detail[$product['product_condition']] ?? null;
                    $supplier = '';
                    $currency = 1;
                    $source = "steel-heart.com";

                    $existingVariation = ProductVariationTable::find()
                        ->where([
                            'product_id' => $productId,
                            'supplier' => $supplier,
                            'price' => $price,
                            'currency' => $currency,
                            'weight' => $weight,
                            'source' => $source,
                            'brand' => $brandId,
                            'type_detail_id' => $typeDetailId,
                        ])
                        ->one();

                    if ($existingVariation) {
                        $variationId = $existingVariation->id;
                        $skippedCount++;
                    } else {
                        $productVariation = new ProductVariationTable();
                        $productVariation->product_id = $productId;
                        $productVariation->supplier = $supplier;
                        $productVariation->price = $price;
                        $productVariation->currency = $currency;
                        $productVariation->weight = $weight;
                        $productVariation->source = $source;
                        $productVariation->brand = $brandId;
                        $productVariation->type_detail_id = $typeDetailId;

                        if ($productVariation->save()) {
                            $variationId = $productVariation->id;
                            $createdCount++;
                        } else {
                            $logger->error("Ошибка сохранения вариации: " . print_r($productVariation->getErrors(), true));
                            continue;
                        }
                    }

                    $existingLink = ProductRowVariationTable::find()
                        ->where([
                            'product_row_id' => $item->id,
                            'variation_id' => $variationId,
                        ])
                        ->one();

                    if (!$existingLink) {
                        $rowVariation = new ProductRowVariationTable();
                        $rowVariation->price = $price;
                        $rowVariation->manufacturer = $product['manufacturer'] ?? null;
                        $rowVariation->source = $source;
                        $rowVariation->product_name = $product["name"] ?? $item->getProduct()->name;
                        $rowVariation->delivery_time = null;
                        $rowVariation->weight = $weight;
                        $rowVariation->product_row_id = $item->id;
                        $rowVariation->currency = $currency;
                        $rowVariation->variation_id = $variationId;
                        $rowVariation->brand = $brandId;
                        $rowVariation->type_detail_id = $typeDetailId;

                        if ($rowVariation->save()) {
                            $linksCreated++;
                            $logger->save("Создана связь для вариации ID: {$variationId}");
                        } else {
                            $logger->save("Ошибка создания связи: " . print_r($rowVariation->getErrors(), true));
                        }
                    }
                }
            }
        }

        $logger->save("Статистика parseApi: вариаций создано={$createdCount}, пропущено={$skippedCount}, связей создано={$linksCreated}");

        $client = new \app\models\BitrixCrm\Client\Client();

        $commands['get_deal'] = $client->api()->buildCommand('crm.deal.get', ['ID' => $this->get(0)->dealId]);
        $commands['send_info'] = $client->api()->buildCommand('im.message.add', [
            'DIALOG_ID' => '$result[get_deal][ASSIGNED_BY_ID]',
            'MESSAGE' => 'Парсинг товаров по сделке окончен - [URL=/crm/deal/details/' . $this->get(0)->dealId . '/]Открыть сделку[/URL]'
        ]);

        $response = $client->api()->batchRequest($commands);
        $logger->save($response);

        $this->processorSupplier();
        return $this;
    }

    public function afterParse()
    {
        $logger = DebugLogger::instance('search-supplier');

        $http = new \GuzzleHttp\Client(['base_uri' => 'https://steelheart-win.uchetprosto.ru/']);
        $dealId = $this->get(0)->dealId;

        $response = $http->request('POST', 'finish.php', [
            'form_params' => ['dealId' => $dealId],
        ]);

        $response = $response->getBody()->getContents();
        $response = json_decode($response, true);

        $logger->save($response, $response, 'Ответ на ожидание');

        if (!$response['result']) {
            sleep(20);
            $this->afterParse();
        } else {
            $client = new \app\models\BitrixCrm\Client\Client();

            $commands['get_deal'] = $client->api()->buildCommand('crm.deal.get', ['ID' => $dealId]);
            $commands['send_info'] = $client->api()->buildCommand('im.message.add', [
                'DIALOG_ID' => '$result[get_deal][ASSIGNED_BY_ID]',
                'MESSAGE' => 'Парсинг товаров по сделке окончен - [URL=/crm/deal/details/' . $dealId . '/]Открыть сделку[/URL]'
            ]);

            $logger->save($commands, $commands, 'Команды на таймлайн');

            $response = $client->api()->batchRequest($commands);

            $logger->save($response->getResponse(), $response, 'Ответ Команды на таймлайн');

            $this->processorSupplier();
        }

        return $this;
    }

    public function moveToBenchmarking()
    {
        $result = [];
//        foreach ($this->items as $item)
        foreach ($this as $item)
        {
            $productRow = ProductRowTable::findOne($item->id);

            $model = BenchmarkingRowTable::find()->where(['=', 'product_id', $productRow->product_id])->where(['=', 'deal_id', $productRow->deal_id])->where(['=', 'product_row_id', $productRow->id])->one();

            if (!$model) {
                $model = new BenchmarkingRowTable();
            }

            $model->product_id = $productRow->product_id;
            $model->deal_id = $productRow->deal_id;
            $model->product_row_id = $productRow->id;
            $result[] = [
                '$productRow' => $productRow,
                '$model' => $model,
                'save' => $model->save()
            ];
        }
        return $result;
    }

    public function processorSupplier()
    {
        foreach ($this->items as $item)
        {
            $productRow = ProductRowTable::findOne($item->id);

            if (empty($productRow->variation)) {
                $model = BenchmarkingRowTable::find()->where(['=', 'product_id', $productRow->product_id])->where(['=', 'deal_id', $productRow->deal_id])->where(['=', 'product_row_id', $productRow->id])->one();

                if (!$model) {
                    $model = new BenchmarkingRowTable();
                }

                $model->product_id = $productRow->product_id;
                $model->deal_id = $productRow->deal_id;
                $model->product_row_id = $productRow->id;
                $model->save();
            }
        }

        return $this->moveToProductRow();
    }

    public function moveToProductRow()
    {
        /*$client = Client::instance();

        $suppliers = collect($this->getSupplier())->flatten(1);

        $filledProductRowSupplier = $this->filter(function ($item) use($suppliers){
            return $suppliers->filter(function ($supplier) use($item) {
                return $supplier['PROPERTY_VALUES']['productRowId'] == $item->id;
            })->isNotEmpty();
        });

        if ($filledProductRowSupplier->isNotEmpty()) {
            $dealProductRows = [];
            $dealId = 0;

            foreach ($filledProductRowSupplier as $row)
            {
                $params = $row->getAttributes();
                $dealId = $params['dealId'];

                $dealProductRows[] = ['PRODUCT_ID' => $params['productId'], 'QUANTITY' => $params['quantity']];
            }

            $client->request('crm.deal.productrows.set', ['id' => $dealId, 'rows' => $dealProductRows]);
        }*/

        return $this;
    }

    public function parseBitrix()
    {
        foreach ($this->items as $item)
        {
            $productVariation = ProductVariationTable::find()->where(['=', 'product_id', $item->productId])->all();

            foreach ($productVariation as $variation)
            {
                $productRowVariation = new ProductRowVariationTable();
                $productRowVariation->supplier = $variation->supplier;
                $productRowVariation->source = $variation->source;
                $productRowVariation->price = $variation->price;
                $productRowVariation->delivery_time = $variation->delivery_time;
                $productRowVariation->product_name = $variation->product->name;
                $productRowVariation->product_row_id = $item->id;
                $productRowVariation->variation_id = $variation->id;
                $productRowVariation->weight = $variation->weight;
//                $productRowVariation->type_detail_id = $variation->type_detail_id;
                $productRowVariation->type_detail_id = $variation->type_detail_id ?: null;
                $productRowVariation->currency = $variation->currency;

                $productRowVariation->save();
            }
        }

        return $this;
    }

    public function lookForPosting()
    {
        $result = [];
        $issetVariations = [];
        $productRows = ProductRow::findByDealID($this->items[0]->dealId);
        foreach ($productRows as $row){
            foreach ($row->getVariations() as $item){
                $issetVariations[] = $item;
            }
        }
        $pervVariations = $issetVariations;

        $result['issetVariations'] = $pervVariations;
//        foreach ($row->variations as $newVariation){
//            $found = false;
//            $tmp=[];
//            foreach ($pervVariations as $oldVariation){
//                if ($newVariation->supplier == $oldVariation->supplier && $newVariation->brand == $oldVariation->brand && $newVariation->typeDetailId == $oldVariation->typeDetailId && (int)$newVariation->weight == $oldVariation->weight && $newVariation->deliveryTime == $oldVariation->deliveryTime && $newVariation->source == $oldVariation->source && (int)$newVariation->price == $oldVariation->price && $newVariation->currency == $oldVariation->currency){
//                    $found = true;
//                    $tmp = [
//                        'supplier' => "{$newVariation->supplier} == {$oldVariation->supplier}",
//                        'brand' => "{$newVariation->brand} == {$oldVariation->brand}",
//                        'typeDetailId' => "{$newVariation->typeDetailId} == {$oldVariation->typeDetailId}",
//                        'weight' => (int)$newVariation->weight." == {$oldVariation->weight}",
//                        'deliveryTime' => "{$newVariation->deliveryTime} == {$oldVariation->deliveryTime}",
//                        'source' => "{$newVariation->source} == {$oldVariation->source}",
//                        'price' => (int)$newVariation->price." == {$oldVariation->price}",
//                        'currency' => "{$newVariation->currency} == {$oldVariation->currency}"
//                    ];
//                }
//            }
//            if ($found){
//                $result['checkVariations'][] = [
//                    $found,
//                    $tmp
//                ];
//            }else{
//                $newVariation->save();
//                $result['checkVariations'][] = [
//                    'found' => $found,
//                    'saved' => $newVariation
//                ];
//            }
//        }



        foreach ($this->items as $item)
        {
            $result['item'][] = $item;
            $row = BenchmarkingRowTable::findOne($item->id);
            $variations = $row->variations;

            if (!empty($variations)) {

                foreach ($variations as $variation)
                {
                    $found = false;
                    $tmp=[];
                    $newVariation = $variation->productVariation;
                    foreach ($pervVariations as $oldVariation){

                        $result['vars'][] = [
                            'supplier' => "{$newVariation->supplier} == {$oldVariation->supplier}",
                            'brand' => "{$newVariation->brand} == {$oldVariation->brand}",
                            'typeDetailId' => "{$newVariation->type_detail_id} == {$oldVariation->typeDetailId}",
                            'weight' => (int)$newVariation->weight." == {$oldVariation->weight}",
                            'deliveryTime' => "{$newVariation->delivery_time} == {$oldVariation->deliveryTime}",
                            'source' => "{$newVariation->source} == {$oldVariation->source}",
                            'status' => (property_exists($newVariation, 'status') ? $newVariation->status : null) . " == " . (property_exists($oldVariation, 'status') ? $oldVariation->status : null),
                            'price' => (int)$newVariation->price." == {$oldVariation->price}",
                            'currency' => "{$newVariation->currency} == {$oldVariation->currency}"
                        ];
                        if ($newVariation->supplier == $oldVariation->supplier && $newVariation->brand == $oldVariation->brand && $newVariation->type_detail_id == $oldVariation->typeDetailId && (int)$newVariation->weight == $oldVariation->weight && $newVariation->delivery_time == $oldVariation->deliveryTime && $newVariation->source == $oldVariation->source && (int)$newVariation->price == $oldVariation->price && $newVariation->currency == $oldVariation->currency){
                            $found = true;
                        }
                    }

                    if ($found){
                        $result['checkVariations'][] = [
                            $found,
                            $tmp
                        ];
                    }else{
//                        $newVariation->save();
                        $result['checkVariations'][] = [
                            'found' => $found,
                            'saved' =>  is_object($newVariation) ? $newVariation->toArray() : $newVariation
                        ];
                        $model = new ProductRowVariationTable();
                        $model->supplier = $variation->productVariation->supplier;
                        $model->price = $variation->productVariation->price;
                        $model->product_name = $row->product->name;
                        $model->delivery_time = $variation->productVariation->delivery_time;
                        $model->source = $variation->productVariation->source;
                        $model->status = property_exists($variation->productVariation, 'status') ? $variation->productVariation->status : null;
                        $model->brand = $variation->productVariation->brand;
                        $model->currency = $variation->productVariation->currency;
                        $model->product_row_id = $row->product_row_id;
                        $model->weight = $variation->productVariation->weight;
                        $model->type_detail_id = $variation->productVariation->type_detail_id;
                        $model->quantity = $variation->productVariation->quantity;
                        $model->variation_id = $variation->productVariation->id;

                        $model->save();
                    }


//                    $variationData = is_object($variation->productVariation) ? $variation->productVariation->toArray() : $variation->productVariation;
//                    $result['variation'][] = $variationData;


                }
            }
        }

       return $result;
    }

    public function lookForPosting1()
    {
        foreach ($this->items as $item)
        {
            $row = BenchmarkingRowTable::findOne($item->id);
            $variations = $row->variations;

            if (!empty($variations)) {

                foreach ($variations as $variation)
                {
                    $model = new ProductRowVariationTable();
                    $model->supplier = $variation->productVariation->supplier;
                    $model->price = $variation->productVariation->price;
                    $model->product_name = $row->product->name;
                    $model->delivery_time = $variation->productVariation->delivery_time;
                    $model->source = $variation->productVariation->source;
                    $model->brand = $variation->productVariation->brand;
                    $model->currency = $variation->productVariation->currency;
                    $model->product_row_id = $row->product_row_id;
                    $model->weight = $variation->productVariation->weight;
                    $model->type_detail_id = $variation->productVariation->type_detail_id;
                    $model->quantity = $variation->productVariation->quantity;
                    $model->variation_id = $variation->productVariation->id;

                    $model->save();
                }
            }
        }

        return true;
    }
}