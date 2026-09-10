<?php

namespace app\models\BitrixCrm\Client\Request;

use app\models\BitrixCrm\Client\Response;
use app\models\BitrixCrm\Client\Config;

class Webhook extends Request
{
    public function request(string $method, array $params = []):Response
    {
        $response = new Response($this->httpClient->request("{$this->config->restUrl}/$method", 'POST', $params));

        if($response->hasError() && $response->isBatch() && ($response->isErrorBlocked() || $response->isErrorManyRequest())) {
            $collectionCommands = [];
            $collectionNameCommands = array_keys($params['cmd']);
            $positionLastNameCommand = array_search($response->getIndexLastProcessedRow(), $collectionNameCommands);

            $collectionNotProcessedCommands = array_filter($collectionNameCommands, fn($item, $key) => $key >= $positionLastNameCommand);

            foreach ($collectionNotProcessedCommands as $commandName)
            {
                $collectionCommands[$commandName] = $params['cmd'][$commandName];
            }

            $params['cmd'] = $collectionCommands;
        }

        if($response->hasError() && $response->isErrorManyRequest() && $this->isAllowCounter()) {
            sleep(5);
            $response->merge($this->request($method, $params));
        } elseif ($response->hasError() && $response->isErrorBlocked() && $this->isAllowCounter()) {
            sleep($response->getOperatingRestTime());
            $response->merge($this->request($method, $params));
        } elseif ($response->hasError()) {
            $this->sendError(
                "При выполнении метода {$method} произошла ошибка: " . ($response->isBatch() ? $response->toJson() : $response->getErrorMessage())
            );
        }

        $this->clearCounter();

        return $response;
    }
}