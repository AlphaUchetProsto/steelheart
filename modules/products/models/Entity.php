<?php

namespace app\modules\products\models;

use app\models\bitrix\Bitrix;
use app\modules\products\models\Client;

class Entity
{
    public static function getProducts($dealId)
    {
        $client = new Client;

        ['result' => $response] = $client->request('entity.item.get', [
            'ENTITY' => 'products',
            'filter' => [
                '=NAME' => $dealId
            ]
        ]);

        if (empty($response)) {
            $client->request('entity.item.add', [
                'ENTITY' => 'products',
                'NAME' => $dealId
            ]);

            return [];
        }

        $products = json_decode($response[0]['PROPERTY_VALUES']['products'], true) ?? [];

        foreach ($products as $key => $product) {
            if (empty($product['price'])) {
                $product['price'] = 0;
            }
            if (empty($product['count'])) {
                $product['count'] = 0;
            }

            $products[$key]['sum'] = $product['price'] * $product['count'];
        }

        return $products;
    }

    public static function saveProducts($data)
    {
        $client = new Client;

//        $client->request('entity.item.add', [
//            'ENTITY' => 'products',
//            'NAME' => $data['dealId']
//        ]);

        $commandRow['entity'] = $client->buildCommand('entity.item.get', [
            'ENTITY' => 'products',
            'filter' => [
                '=NAME' => $data['dealId']
            ]
        ]);

        $commandRow['save'] = $client->buildCommand('entity.item.update', [
            'ENTITY' => 'products',
            'id' => '$result[entity][0][ID]',
            'PROPERTY_VALUES' => [
                'products' => json_encode($data['products'])
            ]
        ]);

        if (!empty($data['products'])) {
            foreach ($data['products'] as $product) {
                $productRows[] = [
//                    'PRODUCT_ID' => 1,
                    'PRODUCT_NAME' => $product['name'],
                    'PRICE' => $product['price'],
                    'QUANTITY' => $product['count'],
                ];
            }
        } else {
            $productRows = [];
        }

//            dd($productRows);

        $commandRow['update-deal'] = $client->buildCommand('crm.deal.productrows.set', [
            'id' => $data['dealId'],
            'rows' => $productRows
        ]);


        $client->batchRequest($commandRow);
    }
}