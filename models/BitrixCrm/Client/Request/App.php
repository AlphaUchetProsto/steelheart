<?php

namespace app\models\BitrixCrm\Client\Request;

use app\models\BitrixCrm\Client\Response;
use app\models\BitrixCrm\Client\Config;

class App extends Request
{
    public function request(string $method, array $params = []):Response
    {
        $params['auth'] = $this->config->accessToken;

        try {
            $response = new Response($this->httpClient->request("{$this->config->restUrl}/$method", 'POST', $params));
        } catch (\Exception $exception) {
            if ($this->isAllowCounter()) {
                sleep(60 * 10);
                $response = $this->request($method, $params);
            } else {
                $this->sendError('Произошла ошибка');
            }
        }

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
        } elseif ($response->hasError() && $response->isErrorExpiredToken() && $this->isAllowCounter()) {
            $this->refreshToken();
            $response->merge($this->request($method, $params));
        } elseif ($response->hasError()) {
            $this->sendError(
                "При выполнении метода {$method} произошла ошибка: " . ($response->isBatch() ? $response->toJson() : $response->getErrorMessage())
            );
        }

        $this->clearCounter();

        return $response;
    }

    public function batchRequest(array $batch, $halt = false) :Response
    {
        return $this->request("batch", ["cmd" => $batch, "halt" => $halt, 'auth' => $this->config->accessToken]);
    }

    protected function refreshToken():void
    {
        $params = [
            "grant_type" => "refresh_token",
            "client_id" => $this->config->clientId,
            "client_secret" => $this->config->clientSecret,
            "refresh_token" => $this->config->refreshToken,
        ];

        $response = $this->httpClient->request("https://oauth.bitrix.info/oauth/token/", "POST", $params);

        $this->config->refreshToken = $response['refresh_token'];
        $this->config->accessToken = $response['access_token'];
        
        $this->config->save();
    }
}