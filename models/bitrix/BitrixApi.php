<?php

namespace app\models\bitrix;

use App\Bitrix24\Bitrix24API;
use App\Bitrix24\Bitrix24APIException;
use app\models\logger\DebugLogger;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class BitrixApi extends Bitrix24API
{
    public function batchRequest(array $cmds, $halt = true): array
    {
        try {
            return parent::batchRequest($cmds, false);
        } catch (Bitrix24APIException $e) {
            if (!empty($this->lastResponse['error']) || !empty($this->lastResponse['error_description'])) {
                if ($this->lastResponse['error_description'] == "Too many requests") {
                    if (!empty($this->logger)) $this->logger->save("batchRequest: 503 или 429, ждем 2 секунды");
                    sleep(2);
                    return static::batchRequest($cmds, $halt);
                }
            }

            if ($e->getCode() == 503 || $e->getCode() == 429) {
                if (!empty($this->logger)) $this->logger->save("batchRequest: 503 или 429, ждем 2 секунды");
                sleep(2);
                return static::batchRequest($cmds, $halt);
            } else {
                if (!empty($this->logger)) $this->logger->save($e->getMessage());
            }
        }

        return [];
    }

    public function request($command, $params = [])
    {
        try {
            return parent::request($command, $params);
        } catch (Bitrix24APIException $e) {
            if (!empty($this->lastResponse['error']) || !empty($this->lastResponse['error_description'])) {
                if ($this->lastResponse['error_description'] == "Too many requests") {
                    if (!empty($this->logger)) $this->logger->save("request: 503 или 429, ждем 2 секунды");
                    sleep(2);
                    return static::request($command, $params);
                }
            }

            if ($e->getCode() == 503 || $e->getCode() == 429) {
                if (!empty($this->logger)) $this->logger->save("request: 503 или 429, ждем 2 секунды");
                sleep(2);
                return static::request($command, $params);
            } else {
                if (!empty($this->logger)) $this->logger->save($e->getMessage());
            }
        }

        return [];
    }
}
