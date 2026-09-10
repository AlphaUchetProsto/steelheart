<?php

namespace app\modules\reports\models\client;

use App\HTTP\HTTP;
use mysql_xdevapi\Exception;
use Yii;
use yii\helpers\ArrayHelper;
use app\models\bitrix\Bitrix;
use app\models\logger\DebugLogger;

class Client
{
    private $access_token;
    private $refresh_token;
    private $expires_in;
    private $client_endpoint;
    private $http;

    private static $config_path;

    private static $client_id = "local.648095ef8989c4.12619920";
    private static $client_secret = "LPfCh1UmUK4zBW0CZqvw3iyPFxT4X3f32ECS4274GO1aqxavct";

    public function __construct(array $config = [])
    {
        static::$config_path = Yii::getAlias("@reports") . "/config/Config.php";

        if (empty($config)) {
            $config = static::getConfig();
        }

        $this->access_token = $config["access_token"] ?? null;
        $this->refresh_token = $config["refresh_token"] ?? null;
        $this->expires_in = $config["expires_in"] ?? null;
        $this->client_endpoint = $config["client_endpoint"] ?? null;

        $this->http = new HTTP();
        $this->http->throttle = 2;
        $this->http->useCookies = false;
    }

    private static function getConfig()
    {
        return require static::$config_path;
    }

    public function request($method, $params = [])
    {
        $url = "{$this->client_endpoint}/{$method}.json";
        $params["auth"] = $this->access_token;

//        echo '<pre>';
//        print_r($url);
//        echo '<pre>';
//        print_r($this->access_token);
//        die;

        $response = $this->http->request($url, "POST", $params);

        if (isset($response["error"]) && $response["error"] == "expired_token") {
            $this->refreshToken();
            $params["auth"] = $this->access_token;

            $response = $this->http->request($url, "POST", $params);
        }

        return $response;
    }

    public function refreshToken(): void
    {
        $config = static::getConfig();

        $params = [
            "grant_type" => "refresh_token",
            "client_id" => static::$client_id,
            "client_secret" => static::$client_secret,
            "refresh_token" => $config["refresh_token"],
        ];

        $response = $this->http->request("https://oauth.bitrix.info/oauth/token/", "POST", $params);

        $this->refresh_token = $response["refresh_token"];
        $this->access_token = $response["access_token"];

        $this->updateConfig($response);
    }

    public function updateConfig($data): void
    {
        $config = static::getConfig();

        foreach ($config as $key => &$value) {
            if (isset($data[$key])) {
                $config[$key] = $data[$key];
            }
        }


        $config = var_export($config, true);

        file_put_contents(Yii::getAlias("@reports") . "/config/Config.php", "<?php\n return {$config};\n");
    }

    public function placementApp($placement, $handler, $title)
    {
        return $this->request('placement.bind', [
            'PLACEMENT' => $placement,
            'HANDLER' => $handler,
            'LANG_ALL' => [
                'ru' => [
                    'TITLE' => $title,
                ],
            ],
        ]);
    }

}