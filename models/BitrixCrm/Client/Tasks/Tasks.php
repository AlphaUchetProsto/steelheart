<?php

namespace app\models\BitrixCrm\Client\Tasks;

use app\models\BitrixCrm\EntityService\Tasks\Task;

class Tasks extends \app\models\BitrixCrm\Client\Client
{
    public function task()
    {
        $request = $this->buildRequest();

        return new Task($request);
    }
}