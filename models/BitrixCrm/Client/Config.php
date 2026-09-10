<?php

namespace app\models\BitrixCrm\Client;

use function Symfony\Component\String\u;

class Config
{
    public ?string $restUrl = null;
    public ?string $accessToken = null;
    public ?string $refreshToken = null;
    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $path = null;

    public function __construct(array $params = [])
    {
        foreach ($params as $property => $value) {
            $property = u($property)->camel()->toString();

            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    public static function loadFromPath(string $path): self
    {
        $configData = require $path;

        $config = new self(is_array($configData) ? $configData : []);
        $config->path = $path;

        return $config;
    }

    public static function getInstanceByPath(string $path, bool $createIfNotExists = false): self
    {
        if (!file_exists($path)) {
            if (!$createIfNotExists) {
                throw new \RuntimeException("Config file not found: {$path}");
            }

            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $defaults = [
                'restUrl' => \Yii::$app->params['bitrix']['rest_url'] ?? null,
                'accessToken' => null,
                'refreshToken' => null,
                'clientId' => null,
                'clientSecret' => null,
            ];

            file_put_contents($path, "<?php\n return " . var_export($defaults, true) . ";\n");
        }

        return self::loadFromPath($path);
    }

    public function load(array $data): self
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $normalized[mb_strtolower((string)$key)] = $value;
        }

        if (isset($normalized['access_token'])) {
            $this->accessToken = $normalized['access_token'];
        } elseif (isset($normalized['auth_id'])) {
            $this->accessToken = $normalized['auth_id'];
        }

        if (isset($normalized['refresh_token'])) {
            $this->refreshToken = $normalized['refresh_token'];
        } elseif (isset($normalized['refresh_id'])) {
            $this->refreshToken = $normalized['refresh_id'];
        }

        if (isset($normalized['client_endpoint'])) {
            $this->restUrl = rtrim((string)$normalized['client_endpoint'], '/');
        } elseif (isset($normalized['domain']) && empty($this->restUrl)) {
            $this->restUrl = 'https://' . $normalized['domain'] . '/rest';
        }

        if (isset($normalized['client_id'])) {
            $this->clientId = $normalized['client_id'];
        }

        if (isset($normalized['client_secret'])) {
            $this->clientSecret = $normalized['client_secret'];
        }

        return $this;
    }

    public function save()
    {
        if ($this->path) {
            $exportConfig = [
                'restUrl' => $this->restUrl,
                'accessToken' => $this->accessToken,
                'refreshToken' => $this->refreshToken,
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ];

            $exportConfig = var_export($exportConfig, true);

            file_put_contents($this->path, "<?php\n return {$exportConfig};\n");
        }
    }
}
