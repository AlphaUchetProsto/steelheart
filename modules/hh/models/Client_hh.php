<?php

namespace app\modules\hh\models;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use UnexpectedValueException;
use App\HTTP\HTTP;
use Yii;
use app\models\bitrix\Bitrix;
use app\models\logger\DebugLogger;

/**
 * ApplicationClient
 * @property string $client_id
 * @property string $client_secret
 * @property Client $http_client
 */
class Client_hh
{
    /**
     * @var string
     */
    protected $client_id;
    /**
     * @var string
     */
    protected $client_secret;
    /**
     * @var Client
     */
    protected $http_client;

    /**
     * @var string|null
     */
    protected $application_token;

    /**
     * ApplicationClient constructor.
     * @param string $client_id
     * @param string $client_secret
     * @param string $application_token
     */

    private static $config_path;
    private static $visitors_path;

    public function __construct(?string $application_token = null)
    {
        static::$config_path = Yii::getAlias("@modules") . "/hh/config/Config.php";
        static::$visitors_path = Yii::getAlias("@modules") . "/hh/temp/visitors.php";

        if (empty($config)) {
            $config = static::getConfig();
        }

        $this->client_id = $config["client_id"] ?? null;
        $this->client_secret = $config["client_secret"] ?? null;
        $this->application_token = $config["access_token"];
        $this->http_client = new Client([
            'base_uri' => 'https://api.hh.ru',
            'headers' =>  [
                'Authorization' => "Bearer $this->application_token",
            ],
        ]);
    }
    private static function getConfig()
    {
        return require static::$config_path;
    }

    public static function getSuccessVisitors()
    {
        return require static::$visitors_path;
    }

    public function getApplicationToken(): ?string
    {
        return $this->application_token;
    }

    public function request($type, $url, $params = null)
    {
//        try {
            if ($type == 'GET') {
                $response = $this->http_client->request($type, $url, ['query' => $params])->getBody()->getContents();
            }
            else {
                $response = $this->http_client->request($type, $url, ['form_params' => $params])->getBody()->getContents();
            }
//        } catch (\Exception $e) {
////            $this->refreshToken();
////            $this->refreshToken();
////                'access_token' => 'USERGG0D4U2UIUUOTU5FCKARFTAE8QNTJS85FF1NEB07BAJMQPQDC4J850JOVOEV',
////                'refresh_token' => 'USERK5R763CIRJA71LH1D3MN242O8G2RU9C3N0GU67QPMMFRJCPMC0KQE8I8010V',
//            die;
//            file_get_contents("https://api.telegram.org/bot1034272956:AAHbDVLSIxmQhiZtbUDl3HLgzfu77KbFByo/sendMessage?chat_id=449614227&text="."HH Сломался!!");
//            $this->refreshToken();
//        }

        return json_decode($response, true);
    }

    public function refreshToken(): void
    {
        $config = static::getConfig();
        $client = new Client([
            'base_uri' => 'https://hh.ru',
            'headers' =>  [
                'Authorization' => "Bearer $this->application_token",
            ],
        ]);
        $logger = DebugLogger::instance("refresh-token");

        $response = $client->request(
            'POST',
            '/oauth/token',
            [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $config['refresh_token'],
                ]
            ]
        )->getBody()->getContents();

        $logger->save($response, $response, '$response');

        $response = json_decode($response, true);
        $this->refresh_token = $response["refresh_token"];
        $this->access_token = $response["access_token"];

        $this->updateConfig($response);
        //die;
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

        file_put_contents(Yii::getAlias("@modules") . "/hh/config/Config.php", "<?php\n return {$config};\n");
    }

    public function updateSuccessVisitors($data): void
    {
        $data = var_export($data, true);
        file_put_contents(Yii::getAlias("@modules") . "/hh/temp/visitors.php", "<?php\n return {$data};\n");
    }

    public function setApplicationToken(?string $application_token): void
    {
        $this->application_token = $application_token;
    }

    public function generateApplicationToken(): ?array
    {
        $client = new Client([
            'base_uri' => 'https://hh.ru',
            'headers' =>  [
                'Authorization' => "Bearer $this->application_token",
            ],
        ]);

        $response = $client->request('POST', '/oauth/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
            ]
        ])->getBody()->getContents();

        return json_decode($response, true);
    }

    /**
     * для получения $authorizationCode
     * https://hh.ru/oauth/authorize?response_type=code&client_id={client_id}
     */
    public function generateApplicationTokens($authorizationCode)
    {
        $client = new Client([
            'base_uri' => 'https://hh.ru',
        ]);

        $response = $client->request('POST', '/oauth/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'code' => $authorizationCode
            ]
        ])->getBody()->getContents();

        $tokens = json_decode($response, true);

        $this->updateConfig($tokens);

        return true;
    }

    /**
     * Информация о приложении, проверка токена приложения
     * @return array|null
     * @throws GuzzleException
     */
    public function getMe(): ?array
    {
        return $this->request('GET', '/me', null);
    }

    public function getResponseTest()
    {
        $response = $this->http_client->request(
            'GET',
            '/negotiations/86708206',
            [
//                'query' => [
////                    'grant_type' => 'client_credentials',
//                    'name' => 'Анастасия Борисова',
//                    'vacancy_id' => '86708206',
//                ]
            ]
        )->getBody()->getContents();

        return json_decode($response, true);
    }

    public function setWebhook($type)
    {
//        $response = $this->http_client->request(
//            'POST',
//            '/webhook/subscriptions',
////            '/url_used_for_subscription',
//            [
//                'form_params' => [
//                    'actions' =>
//                    [
//                       [
//                           'settings' => ['vacancies_only_mine' => false],
//                           'type' => $type
//                       ],
//                    ],
//                    'url' => 'https://up.emerhunt.ru/Yii_uchet/web/hh/get-webhook',
//                ],
//            ]
//        )->getBody()->getContents();
        $client = new Client([
            'headers' =>  [
                'Authorization' => "Bearer $this->application_token",
            ]
        ]);
        $response = $client->put('https://api.hh.ru/webhook/subscriptions/184086', [
            \GuzzleHttp\RequestOptions::JSON => [
                'actions' =>
                [
                    [
                        'settings' => ['vacancies_only_mine' => false],
                        'type' => $type
                    ],
                    [
                        'settings' => ['vacancies_only_mine' => false],
                        'type' => 'NEW_RESPONSE_OR_INVITATION_VACANCY'
                    ],
                ],
                'url' => 'https://up.emerhunt.ru/Yii_uchet/web/hh/get-webhook',]
        ]);
//        $response = $client->post('https://api.hh.ru/webhook/subscriptions', [
//            \GuzzleHttp\RequestOptions::JSON => [
//                'actions' =>
//                [
//                    [
//                        'settings' => ['vacancies_only_mine' => false],
//                        'type' => $type
//                    ]
//                ],
//                'url' => 'https://up.emerhunt.ru/Yii_uchet/web/hh/get-webhook',]
//        ]);
        dd($response);
        return json_decode($response, true);
    }

    public function getVacancies($managerId, $employer_id)
    {
        return $this->request('GET', "/employers/{$managerId}/vacancies/active", [
            'manager_id' => $employer_id,
        ]);
    }

    public function getVacancy($id)
    {
        return $this->request('GET', "/vacancies/$id",);
    }

    public function getVisitors($vacancy_id)
    {
        return $this->request('GET', "/vacancies/$vacancy_id/visitors", null);
    }

    public function getResponse($vacancy_id)
    {
        return $this->request('GET', "/negotiations/response", ['vacancy_id' => $vacancy_id,]);
    }

    public function getManagers($employer_id)
    {
        return $this->request('GET', "/employers/$employer_id/managers");
    }

    public function getDocument($url)
    {
        return $this->http_client->request('GET', str_replace('https://api.hh.ru','',$url), [])->getBody()->getContents();
    }

    public function getResume($id)
    {
        return $this->request('GET', "/resumes/$id");
    }

    public function getWebhooks()
    {
        $response = $this->http_client->request('GET', '/webhook/subscriptions', [])->getBody()->getContents();

        return json_decode($response, true);
    }

    public function getTime()
    {
        $errorFile = Yii::getAlias("@modules") . "/hh/config/error.php";

        if (file_exists($errorFile)) {
            return intval(file_get_contents($errorFile));
        } else {
            return strtotime('-1 minute');
        }
    }
}