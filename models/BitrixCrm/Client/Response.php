<?php

namespace app\models\BitrixCrm\Client;

use function Symfony\Component\String\u;

class Response
{
    protected const MANY_REQUEST_ERROR = 'Too many requests';
    protected const BLOCKED_ERROR = 'Method is blocked due to operation time limit';
    protected const INTERNAL_ERROR = 'Internal server error';
    protected const EXPIRED_TOKEN = 'The access token provided has expired';

    protected $response;

    public function __construct(array $data = [])
    {
        $this->response = $data;
    }

    public function isBatch() :bool
    {
        return isset($this->response['result']['result']);
    }

    public function hasError() :bool
    {
        return $this->isBatch() ? !empty($this->response['result']['result_error']) : array_key_exists('error', $this->response);
    }

    public function getErrorMessage() :?string
    {
        if(!$this->hasError()) {
            return null;
        }

        return $this->isBatch() ? array_values($this->response['result']['result_error'])[0]['error_description'] : $this->response['error_description'];
    }

    public function isErrorManyRequest() :bool
    {
        return u($this->getErrorMessage())->containsAny(self::MANY_REQUEST_ERROR) || u($this->getErrorMessage())->containsAny(self::INTERNAL_ERROR);
    }

    public function isErrorBlocked() :bool
    {
        return u($this->getErrorMessage())->containsAny(self::BLOCKED_ERROR);
    }

    public function isErrorExpiredToken() :bool
    {
        return u($this->getErrorMessage())->containsAny(self::EXPIRED_TOKEN);
    }

    public function getOperatingRestTime() :int
    {
        return $this->response['time']['operating_reset_at'] - strtotime('now');
    }

    public function getIndexLastProcessedRow() :?int
    {
        if(!$this->hasError() && !$this->isBatch()) {
            return null;
        }

        return array_keys($this->response['result']['result_error'])[0];
    }

    public function merge(Response $response) :void
    {
        $response = $response->getFullResponse();

        if($this->isBatch()) {
            $this->response['result']['result'] = array_merge($this->response['result']['result'], $response['result']['result']);
            $this->response['result']['result_error'] = array_merge($this->response['result']['result_error'], $response['result']['result_error']);
            $this->response['result']['result_total'] = array_merge($this->response['result']['result_total'], $response['result']['result_total']);
            $this->response['result']['result_next'] = array_merge($this->response['result']['result_next'], $response['result']['result_next']);
            $this->response['result']['result_time'] = array_merge($this->response['result']['result_time'], $response['result']['result_time']);
            $this->response['time'] = $response['time'];
        } else {
            $this->response = $response;
        }
    }

    public function getAmount() : int
    {
        return $this->response['total'] ?? 0;
    }

    public function getPage() :int
    {
        return $this->response['next'] ?? 0;
    }

    public function getFullResponse() :array
    {
        return $this->response;
    }

    public function getResponse() :array
    {
        return $this->isBatch() ? $this->response['result']['result'] : $this->response['result'];
    }

    public function toJson() :string
    {
        return json_encode($this->response, JSON_UNESCAPED_UNICODE);
    }
}