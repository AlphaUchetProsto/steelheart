<?php

namespace app\models\BitrixCrm\EntityService\Tasks;

use app\models\BitrixCrm\Models\BaseModel;
use app\models\BitrixCrm\EntityService\BaseService;
use  app\models\BitrixCrm\Models\Tasks\TaskModel;

class Task extends BaseService
{
    protected $itemClass = TaskModel::class;

    public function get(int $taskId, array $select = [])
    {
        $data = $this->request->request('tasks.task.get', ['taskId' => $taskId, 'select' => $select])->getResponse();

        return new $this->itemClass($data['task']);
    }

    public function getFields()
    {
        return $this->request->request('tasks.task.getFields')->getResponse();
    }

    public function list(array $params = [])
    {
        $data = $this->request->request('tasks.task.list', $params)->getResponse();

        return $this->createCollection($data['tasks']);
    }
}
