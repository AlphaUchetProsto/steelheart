<?php

namespace app\models\BitrixCrm\EntityService\Catalog;

use app\models\BitrixCrm\Collection\BaseCollection;
use app\models\BitrixCrm\Models\BaseModel;

class DocumentProducts extends \app\models\BitrixCrm\EntityService\BaseService
{
    protected $collectionClass = BaseCollection::class;
    protected $itemClass = BaseModel::class;

    public function list(array $params = [])
    {
        $response = $this->request->request('catalog.document.element.list', $params)->getResponse();

        return $this->createCollection($response['documentElements']);
    }

    public function fetch(array $params = [])
    {
        $documentId = 0;
        $finish = false;

        $params['order']['id'] = 'ASC';
        $params['start'] = -1;

        while (!$finish) {
            $params['filter']['>id'] = $documentId;

            $response = $this->request->request('catalog.document.element.list', $params)->getResponse();

            if (!empty($response['documentElements'])) {
                $documentId = $response['documentElements'][count($response['documentElements']) - 1]['id'];
                yield $this->createCollection($response['documentElements']);
            } else {
                $finish = true;
            }
        }
    }
}
