<?php

namespace app\modules\vendor_order\models;

use app\models\logger\DebugLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use yii\base\Model;
use Yii;
use function Symfony\Component\String\u;

class OpenCartApi
{
    const URI = "https://steel-heart.com/index.php";

    private $token;
    private $client;
    private $logger;

    public function __construct($token = null)
    {
        $this->token = \Yii::$app->cache->get("opencart_token");

        $this->client = new Client([
            "base_uri" => static::URI,
        ]);

        $this->logger = DebugLogger::instance(Yii::$app->controller->action->id);
    }

    public function getToken()
    {
        return $this->token;
    }

    /**
     * @throws GuzzleException
     */
    public function request(string $method, string $route, ?array $params = null, float $sleep = 100.0, int $tries = 0): array
    {
        if ($tries > 3) {
            return [
                "error" => [
                    "code" => "429",
                    "content" => "Too Many Requests после $tries попыток",
                ]
            ];
        }

        $this->logger->save($params, NULL, "$method $route");

        $options = [
            "timeout" => 60,
            "headers" => array_filter([
                "Content-Type" => "application/x-www-form-urlencoded",
                "Accept" => "application/json",
            ]),
        ];

        if (!empty($params)) {
            $options[$method === 'GET' ? "query" : "form_params"] = $params;
        }

        $options["query"]["route"] = $route;

        if (!empty($this->token)) {
            $options["query"]["api_token"] = $this->token;
        }

        $jar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            "OCSESSID" => \Yii::$app->cache->get("opencart_sessid"),
        ], "steel-heart.com");
        $options["cookies"] = $jar;

        try {
            $res = $this->client->request($method, '', $options);
        } catch (ClientException $e) {
            if ($e->getCode() === 429) {
                usleep(intval($sleep * 100.0));
                return $this->request($method, $route, $params, $sleep * 2.0, $tries + 1);
            }

            return [
                "error" => [
                    "code" => $e->getCode(),
                    "content" => json_decode($e->getResponse()->getBody() ?? [], true),
                ]
            ];
        }

        $code = $res->getStatusCode();
        $reason = $res->getReasonPhrase();

        $this->logger->save("$method $route $code $reason");

        $decoded = json_decode($res->getBody()->getContents() ?? [], true);

        if (!empty($decoded["error"]) && $decoded["error"] == "error") {
            $this->fetchAuthData();
            return $this->request($method, $route, $params, $sleep * 2.0, $tries + 1);
        }

        return $decoded;
    }

    public function fetchAuthData()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://steel-heart.com/index.php?route=api%2Flogin',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'key=' . \Yii::$app->params['modules']['vendor_order']['octoken'] . '&username=' . \Yii::$app->params['modules']['vendor_order']['ocuser'],
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
            CURLOPT_HEADER => 1,
        ));

        $response = curl_exec($curl);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        $sessid = null;
        $header = explode("\r\n", substr($response, 0, $header_size));
        foreach ($header as $head) {
            if (str_starts_with($head, "Set-Cookie:")) {
                $expl = explode(" ", $head);
                $val = $expl[1];
                $expl2 = explode("=", $val);
                if ($expl2[0] == "OCSESSID") {
                    $sessid = u($expl2[1])->before(';')->toString();
                    break;
                }
            }
        }

        $body = json_decode(substr($response, $header_size), true);

        $logger =DebugLogger::instance('test');
        $logger->save($body);
        
        \Yii::$app->cache->set("opencart_sessid", $sessid);
        \Yii::$app->cache->set("opencart_token", $body["api_token"]);

        $this->token = \Yii::$app->cache->get("opencart_token");

        curl_close($curl);

        return [$sessid, $body];
    }
}
