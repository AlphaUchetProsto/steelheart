<?php

namespace app\models\BitrixCrm\EntityService\Catalog;

use app\models\BitrixCrm\Collection\BaseCollection;
use app\models\BitrixCrm\Models\BaseModel;

class Documents extends \app\models\BitrixCrm\EntityService\BaseService
{
    protected $collectionClass = BaseCollection::class;
    protected $itemClass = BaseModel::class;

    public function list(array $params = [])
    {
        $response = $this->request->request('catalog.document.list', $params)->getResponse();

        return $this->createCollection($response['documents']);
    }

    public function fetch(array $params = [])
    {
        $documentId = 0;
        $finish = false;

        $params['order']['id'] = 'ASC';
        $params['start'] = -1;

        while (!$finish) {
            $params['filter']['>id'] = $documentId;

            $response = $this->request->request('catalog.document.list', $params)->getResponse();

            if (!empty($response['documents'])) {
                $documentId = $response['documents'][count($response['documents']) - 1]['id'];
                yield $this->createCollection($response['documents']);
            } else {
                $finish = true;
            }
        }
    }
}
