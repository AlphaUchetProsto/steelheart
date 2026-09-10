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
        foreach ($params as $property => $value)
        {
            $property = u($property)->camel()->toString();
            
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    public static function loadFromPath(string $path)
    {
        $configData = require $path;

        return new self($configData);
    }


    public function save()
    {
        if ($this->path) {
            $exportConfig = json_decode(json_encode($this), true);
            $exportConfig = var_export($exportConfig, true);

            file_put_contents($this->path, "<?php\n return {$exportConfig};\n");
        }
    }
}