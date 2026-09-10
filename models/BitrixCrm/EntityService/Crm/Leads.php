<?php

namespace app\models\BitrixCrm\EntityService\Crm;

use app\models\BitrixCrm\Collection\BaseCollection;
use app\models\BitrixCrm\EntityService;

use app\models\BitrixCrm\Models\BaseModel;
use app\models\logger\DebugLogger;

class Leads extends EntityService\BaseService
{
    protected $collectionClass = BaseCollection::class;
    protected $itemClass = BaseModel::class;

    public function get($id)
    {
        $response = $this->request->request('crm.lead.get', ['ID' => $id]);

        return new $this->itemClass($response->getResponse());
    }

    public function fields()
    {
        return $this->request->request('crm.lead.fields')->getResponse();
    }

    public function list(array $params = [])
    {
        $response = $this->request->request('crm.lead.list', $params);

        return $this->createCollection($response->getResponse());
    }

    public function fetch(array $params = [])
    {
        $leadId = 0;
        $finish = false;

        $params['order']['ID'] = 'ASC';
        $params['start'] = -1;

        while (!$finish) {
            $params['filter']['>ID'] = $leadId;

            $response = $this->request->request('crm.lead.list', $params)->getResponse();

            if (!empty($response)) {
                $leadId = $response[count($response) - 1]['ID'];
                yield $this->createCollection($response);
            } else {
                $finish = true;
            }
        }
    }

    public function multipleUpdate(BaseCollection $collection)
    {
        $client = $this->request;

        $commandsUpdate = $collection->map(function ($item) use($client) {
            return $client->buildCommand('crm.lead.update', ['ID' => $item->getFieldValue('ID'), 'fields' => $item->getFields()]);
        })->toArray();

        return $this->request->multipleBatchRequest($commandsUpdate);
    }

    public function add(BaseModel $model)
    {
        return $this->request->request('crm.lead.add', ['fields' => $model->getFields()]);
    }
}
