<?php

namespace app\models\yandex\client;

use GuzzleHttp\Client;

class Request
{
    protected $token;
    protected $restUrl;
    protected $http;
    protected $headers;

    public function __construct(string $restUrl, string $token)
    {
        $this->token = $token;
        $this->restUrl = $restUrl;

        $this->headers = ['Authorization: OAuth ' . $this->token];
    }

    public function get(string $method, array $params = [])
    {
        $ch = curl_init("{$this->restUrl}{$method}?" . http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->buildResponse($response);
    }

    public function put(string $method, array $params = [])
    {
        $ch = curl_init("{$this->restUrl}{$method}?" . http_build_query($params));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->buildResponse($response);
    }

    public function delete(string $method, array $params = [])
    {
        $ch = curl_init("{$this->restUrl}{$method}?" . http_build_query($params));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->buildResponse($response);
    }

    public function post(string $method, array $params = [])
    {
        $ch = curl_init("{$this->restUrl}{$method}?" . http_build_query($params));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->buildResponse($response);
    }

    private function buildResponse($response)
    {
        return json_decode($response, true);
    }
}