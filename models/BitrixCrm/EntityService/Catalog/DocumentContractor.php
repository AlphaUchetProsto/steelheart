<?php

namespace app\models\BitrixCrm\EntityService\Catalog;

use app\models\BitrixCrm\Collection\BaseCollection;
use app\models\BitrixCrm\Models\BaseModel;

class DocumentContractor extends \app\models\BitrixCrm\EntityService\BaseService
{
    protected $collectionClass = BaseCollection::class;
    protected $itemClass = BaseModel::class;

    public function list(array $params = [])
    {
        $response = $this->request->request('catalog.documentcontractor.list', $params)->getResponse();

        return $this->createCollection($response['documentContractor']);
    }

    public function fetch(array $params = [])
    {
        $documentId = 0;
        $finish = false;

        $params['order']['id'] = 'ASC';
        $params['start'] = -1;

        while (!$finish) {
            $params['filter']['>id'] = $documentId;

            $response = $this->request->request('catalog.documentcontractor.list', $params)->getResponse();

            if (!empty($response['documentContractor'])) {
                $documentId = $response['documentContractor'][count($response['documentContractor']) - 1]['id'];
                yield $this->createCollection($response['documentContractor']);
            } else {
                $finish = true;
            }
        }
    }
}
