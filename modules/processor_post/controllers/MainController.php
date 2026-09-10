<?php

namespace app\modules\processor_post\controllers;

use Yii;
use yii\web\Controller;
use app\models\logger\CleanerLogger;
use app\models\logger\DebugLogger;
use app\models\bitrix\Bitrix;
use app\models\bitrix\BitrixNew;
use app\modules\processor_post\models\distributor\Distributor;
//use app\models\bitrix\entity\Contact;
use app\modules\processor_post\models\bitrix\Deal;
use app\modules\processor_post\models\bitrix\Contact;
use app\modules\processor_post\models\bitrix\Fields;
use League\Csv\Writer;
use Aspera\Spreadsheet\XLSX\Reader;
use app\modules\processor_post\models\db\Deals;

class MainController extends Controller
{
    public $documents = [];
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        CleanerLogger::clear();

        return parent::afterAction($action, $result);
    }

    public function actionIndex()
    {
        $post_data = \Yii::$app->request->post();
        $get_data = \Yii::$app->request->get();
        $distributor = new Distributor($post_data, $get_data);

        if(($url = $distributor->checkToken()) === null) return false;
        if(!$distributor->checkTelegramIdAndLog()) {
            $distributor->StartParser($url);
            return 200;
        }
        $distributor->checkUser();

        return 200;
    }

    public function actionAddEntity()
    {
        $logger = DebugLogger::instance('add-entity');
        $logger->save(Yii::$app->request->post(), Yii::$app->request, 'Данные сущностей');

        header('Content-Type: text/html; charset=utf-8');

        $get_data = \Yii::$app->request->get();
        $post_data = \Yii::$app->request->post();

        $fields = new Fields($post_data);
        $contact_id = $fields->checkDublicate();
        $fields->setFieldsDeals($contact_id);
        $fields->setFieldsLeads($fields);

    }

    public function actionPrepare()
    {
        $get_data = \Yii::$app->request->get();
        $post_data = \Yii::$app->request->post();
        $cookie = $_COOKIE;
        $prepare = new Prepare($post_data,$get_data, $cookie);
        $prepare->sending();
    }

    public function actionCheckAvailability()
    {
        $get_data = \Yii::$app->request->get();
        $logger = DebugLogger::instance('check-availability');
        $logger->save($get_data, $get_data, 'Get данные');
        return 200;
    }

    public function getCompanies()
    {
        $webhook = new BitrixNew();
        try {
            $reader = new Reader();
//            $reader->open(Yii::getAlias("@app") . "/web/temp/src/Перенос базы сделок.xlsx");
            $reader->open(Yii::getAlias('@app') . '/web/temp/Компании.xlsx');
        } catch (\Exception $e) {
            return ['error' => true, 'reason' => trim(explode('(', $e->getMessage())[0])];
        }

        $sheets = $reader->getSheets();
//        dd($sheets);
        $companies = [];
        $contacts = [];
        $rawCompaniesTable = [];
        /**
         *  Скан таблицы компаний
         */
        $reader->changeSheet(0);
        foreach ($reader as $row_number => $row) {
//            $companiesTable[$row_number] = $row;
//            continue;
            if ($row_number == 1) {
                continue;
            }
            if (trim($row[2]) == '') {
                continue;
            }
            $rawCompaniesTable[$row_number] = $row[2];
        }

        foreach ($rawCompaniesTable as $row => $item) {
            $commands[$item] = $webhook->buildCommand('crm.company.add', [
                'fields' => [
                    'TITLE' => $item
                ]
            ]);
        }
        return $commands;
    }
    public function getContacts($createCompanies)
    {
        $webhook = new BitrixNew();
        try {
            $reader = new Reader();
//            $reader->open(Yii::getAlias("@app") . "/web/temp/src/Перенос базы сделок.xlsx");
            $reader->open(Yii::getAlias('@app') . '/web/temp/Контакты.xlsx');
        } catch (\Exception $e) {
            return ['error' => true, 'reason' => trim(explode('(', $e->getMessage())[0])];
        }

        $sheets = $reader->getSheets();
//        dd($sheets);
        $companies = [];
        $contacts = [];
        $rawCompaniesTable = [];
        /**
         *  Скан таблицы компаний
         */
        $reader->changeSheet(0);
        $tempContacts = [];
        foreach ($reader as $row_number => $row) {
            $fields = [];
//            $companiesTable[$row_number] = $row;
//            continue;
            if ($row_number == 1) {
                continue;
            }
            if (trim($row[3]) != '') {
                $fields['NAME'] = trim($row[3]);
            }
            if (trim($row[4]) != '') {
                $fields['LAST_NAME'] = trim($row[4]);
            }

            if (trim($row[13]) != '') {
                $tempContacts[$row_number]['name'] = trim($row[2] ?? '');
                $tempContacts[$row_number]['deal'] = trim($row[13]);
            }

            if (trim($row[5]) != '') {
                if (isset($createCompanies[trim($row[5])])) {
                    $fields['COMPANY_ID'] = $createCompanies[trim($row[5])];
                    $tempContacts[$row_number]['company_id'] = $createCompanies[trim($row[5])];
                } else {
                    foreach ($createCompanies as $name => $createCompany) {
                        if (preg_match("~$name~", trim($row[5]))) {
                            $fields['COMPANY_ID'] = $createCompany;
                            $tempContacts[$row_number]['company_id'] = $createCompany;
                            break;
                        }
                    }
                }
            }

            if (trim($row[15]) != '') {
                $temp = explode(',', $row[15]);
                foreach ($temp as $item) {
                    $fields['EMAIL'][] = [
                        'VALUE' => $item,
                        'VALUE_TYPE' => 'WORK',
                    ];
                }
            }
            if (trim($row[16]) != '') {
                $temp = explode(',', $row[16]);
                foreach ($temp as $item) {
                    $fields['EMAIL'][] = [
                        'VALUE' => $item,
                        'VALUE_TYPE' => 'HOME',
                    ];
                }
            }
            if (trim($row[17]) != '') {
                $temp = explode(',', $row[17]);
                foreach ($temp as $item) {
                    $fields['EMAIL'][] = [
                        'VALUE' => $item,
                        'VALUE_TYPE' => 'OTHER',
                    ];
                }
            }
            if (trim($row[18]) != '') {
                $temp = explode(',', $row[18]);
                foreach ($temp as $item) {
                    $fields['PHONE'][] = [
                        'VALUE' => preg_replace('~[^0-9]~', '', $item),
                        'VALUE_TYPE' => 'MOBILE',
                    ];
                }
            }
            if (!empty($fields)) {
                $commands[$row_number] = $webhook->buildCommand('crm.contact.add', [
                    'fields' => $fields
                ]);
            }

        }
//        file_put_contents(Yii::getAlias("@app") . "/modules/processor_post/temp/tempContacts.json", json_encode($tempContacts));

        return $commands;
    }

    public function getProductInWork()
    {
        try {
            $reader = new Reader();
//            $reader->open(Yii::getAlias("@app") . "/web/temp/src/Перенос базы сделок.xlsx");
            $reader->open(Yii::getAlias('@app') . '/web/temp/Сделки в работе.xlsx');
        } catch (\Exception $e) {
            return ['error' => true, 'reason' => trim(explode('(', $e->getMessage())[0])];
        }

        $reader->changeSheet(0);
        foreach ($reader as $row_number => $row) {
//            $products[$row_number] = $row;
//            continue;
            if ($row_number == 1) {
                continue;
            }
            foreach ($row as $key => $item) {
                if ($key < 85) {
                    continue;
                }

                if (preg_match_all('~\b([[:digit:]]|[[:alpha:]]|-)*([[:digit:]])[^\n\t]+[\s]+(–|-|[\s])+[\s]+(([[:digit:]]|\s|\.|,)+(р|руб|РУБ|eur|евро|EUR|RMB|€|\\$|BYN|byn)+)*~', $item, $matches)) {
                    foreach ($matches[0] as $match)
                    {
                        $infoProducts = preg_split('~[\s]+(–|-|[\s])+[\s]+~', $match);
                        $article = preg_replace('~[\s]+(–|-)+[\s]+~', '', $infoProducts[0]);
                        if (isset($infoProducts[1]) && preg_match('~([[:digit:]]|\s|\.|,)+(р|руб|РУБ|eur|евро|EUR|RMB|€|\\$|BYN|byn)+~', $infoProducts[1], $currencyAndPrice)) {
//                            $commands = $webhook->request('catalog.product.add', ['fields' => [
//                                'iblockId' => '15',
//                                'name' => $article,
//                                'property107' => $article,
////                                'purchasingPrice' => preg_replace('~[^0-9]~', '', $currency[0]),
////                                'purchasingCurrency' => preg_replace('~[^0-9]~', '', $currency[0]),
////                                'purchasingCurrency' => 'RUB',
////                                'price' => preg_replace('~[^0-9]~', '', $currency[0]),
//                            ]]);
                            /**
                             * CNY -  Юань
                             * RUB -  Российский рубль
                             * TRY -  Турецкая лира
                             * USD -  Доллар США
                             * EUR -  Евро
                             * UAH -  Гривна
                             * BYN -  Белорусский рубль
                             */
                            if (preg_match('~р|руб|РУБ~', $currencyAndPrice[0])) {
                                $currency = 'RUB';
                            } elseif (preg_match('~eur|евро|€~', $currencyAndPrice[0])) {
                                $currency = 'EUR';
                            } elseif (preg_match('~RMB~', $currencyAndPrice[0])) {
                                $currency = 'CNY';
                            } elseif (preg_match('~\\$~', $currencyAndPrice[0])) {
                                $currency = 'USD';
                            } elseif (preg_match('~BYN|byn~', $currencyAndPrice[0])) {
                                $currency = 'BYN';
                            }
                            (preg_match_all('~\B[\s]*([[:digit:]]|[[:alpha:]]|-)*([[:digit:]])[\s]*~', $article, $temp));

                            $article = explode(' ',trim(preg_replace('/[\x{0410}-\x{042F}]+.*[\x{0410}-\x{042F}]+/iu', '',$temp[0][0] ?? $article)))[0];
                            $inWorkProducts[$row_number][$article] = [
                                'article' => $article,
                                'price' => preg_replace('~[^0-9]~', '', $currencyAndPrice[0]),
                                'currency' => $currency ?? 'RUB',
                            ];
//                            dump($currencyAndPrice[0]);
//                            dd($article);
//                            dump($match, $article, preg_split('~[\s]+(–|-|[\s])+[\s]+~', $match));
//                            dd($currency);
                        } else {
//                            $article = trim(preg_replace('~[а-я]+.*[А-Я]+~', '', $article));
                            $article = explode(' ',trim(preg_replace('/[\x{0410}-\x{042F}]+.*[\x{0410}-\x{042F}]+/iu', '', $article)))[0];
                            $inWorkProducts[$row_number][$article] = [
                                'article' => $article,
                                'price' => 0,
                                'currency' => 'RUB',
                            ];
                        }
                        //dump($match, preg_split('~[\s]+(–|-|[\s])+[\s]+~', $match, PREG_SPLIT_NO_EMPTY));
                    }
//                    dd($item);
                }
            }
        }
        return $inWorkProducts;
    }
    public function getProductInSuccess()
    {
        try {
            $reader = new Reader();
//            $reader->open(Yii::getAlias("@app") . "/web/temp/src/Перенос базы сделок.xlsx");
            $reader->open(Yii::getAlias('@app') . '/web/temp/Успешные сделки.xlsx');
        } catch (\Exception $e) {
            return ['error' => true, 'reason' => trim(explode('(', $e->getMessage())[0])];
        }

        $reader->changeSheet(0);
        foreach ($reader as $row_number => $row) {
//            $products[$row_number] = $row;
//            continue;
            if ($row_number == 1) {
                continue;
            }
            foreach ($row as $key => $item) {
                if ($key < 85) {
                    continue;
                }

                if (preg_match_all('~\b([[:digit:]]|[[:alpha:]]|-)*([[:digit:]])[^\n\t]+[\s]+(–|-|[\s])+[\s]+(([[:digit:]]|\s|\.|,)+(р|руб|РУБ|eur|евро|EUR|RMB|€|\\$|BYN|byn)+)*~', $item, $matches)) {
                    foreach ($matches[0] as $match)
                    {
                        $infoProducts = preg_split('~[\s]+(–|-|[\s])+[\s]+~', $match);
                        $article = preg_replace('~[\s]+(–|-)+[\s]+~', '', $infoProducts[0]);
                        if (isset($infoProducts[1]) && preg_match('~([[:digit:]]|\s|\.|,)+(р|руб|РУБ|eur|евро|EUR|RMB|€|\\$|BYN|byn)+~', $infoProducts[1], $currencyAndPrice)) {
//                            $commands = $webhook->request('catalog.product.add', ['fields' => [
//                                'iblockId' => '15',
//                                'name' => $article,
//                                'property107' => $article,
////                                'purchasingPrice' => preg_replace('~[^0-9]~', '', $currency[0]),
////                                'purchasingCurrency' => preg_replace('~[^0-9]~', '', $currency[0]),
////                                'purchasingCurrency' => 'RUB',
////                                'price' => preg_replace('~[^0-9]~', '', $currency[0]),
//                            ]]);
                            /**
                             * CNY -  Юань
                             * RUB -  Российский рубль
                             * TRY -  Турецкая лира
                             * USD -  Доллар США
                             * EUR -  Евро
                             * UAH -  Гривна
                             * BYN -  Белорусский рубль
                             */
                            if (preg_match('~р|руб|РУБ~', $currencyAndPrice[0])) {
                                $currency = 'RUB';
                            } elseif (preg_match('~eur|евро|€~', $currencyAndPrice[0])) {
                                $currency = 'EUR';
                            } elseif (preg_match('~RMB~', $currencyAndPrice[0])) {
                                $currency = 'CNY';
                            } elseif (preg_match('~\\$~', $currencyAndPrice[0])) {
                                $currency = 'USD';
                            } elseif (preg_match('~BYN|byn~', $currencyAndPrice[0])) {
                                $currency = 'BYN';
                            }
                            (preg_match_all('~\B[\s]*([[:digit:]]|[[:alpha:]]|-)*([[:digit:]])[\s]*~', $article, $temp));

                            $article = explode(' ',trim(preg_replace('/[\x{0410}-\x{042F}]+.*[\x{0410}-\x{042F}]+/iu', '',$temp[0][0] ?? $article)))[0];
                            $inWorkProducts[$row_number][$article] = [
                                'article' => $article,
                                'price' => preg_replace('~[^0-9]~', '', $currencyAndPrice[0]),
                                'currency' => $currency ?? 'RUB',
                            ];
//                            dump($currencyAndPrice[0]);
//                            dd($article);
//                            dump($match, $article, preg_split('~[\s]+(–|-|[\s])+[\s]+~', $match));
//                            dd($currency);
                        } else {
//                            $article = trim(preg_replace('~[а-я]+.*[А-Я]+~', '', $article));
                            $article = explode(' ',trim(preg_replace('/[\x{0410}-\x{042F}]+.*[\x{0410}-\x{042F}]+/iu', '', $article)))[0];
                            $inWorkProducts[$row_number][$article] = [
                                'article' => $article,
                                'price' => 0,
                                'currency' => 'RUB',
                            ];
                        }
                        //dump($match, preg_split('~[\s]+(–|-|[\s])+[\s]+~', $match, PREG_SPLIT_NO_EMPTY));
                    }
//                    dd($item);
                }
            }
        }
        return $inWorkProducts;
    }
    public function getProductInLose()
    {
        try {
            $reader = new Reader();
//            $reader->open(Yii::getAlias("@app") . "/web/temp/src/Перенос базы сделок.xlsx");
            $reader->open(Yii::getAlias('@app') . '/web/temp/Неуспешные сделки.xlsx');
        } catch (\Exception $e) {
            return ['error' => true, 'reason' => trim(explode('(', $e->getMessage())[0])];
        }

        $reader->changeSheet(0);
        foreach ($reader as $row_number => $row) {
//            $products[$row_number] = $row;
//            continue;
            if ($row_number == 1) {
                continue;
            }
            foreach ($row as $key => $item) {
                if ($key < 85) {
                    continue;
                }

                if (preg_match_all('~\b([[:digit:]]|[[:alpha:]]|-)*([[:digit:]])[^\n\t]+[\s]+(–|-|[\s])+[\s]+(([[:digit:]]|\s|\.|,)+(р|руб|РУБ|eur|евро|EUR|RMB|€|\\$|BYN|byn)+)*~', $item, $matches)) {
                    foreach ($matches[0] as $match)
                    {
                        $infoProducts = preg_split('~[\s]+(–|-|[\s])+[\s]+~', $match);
                        $article = preg_replace('~[\s]+(–|-)+[\s]+~', '', $infoProducts[0]);
                        if (isset($infoProducts[1]) && preg_match('~([[:digit:]]|\s|\.|,)+(р|руб|РУБ|eur|евро|EUR|RMB|€|\\$|BYN|byn)+~', $infoProducts[1], $currencyAndPrice)) {
//                            $commands = $webhook->request('catalog.product.add', ['fields' => [
//                                'iblockId' => '15',
//                                'name' => $article,
//                                'property107' => $article,
////                                'purchasingPrice' => preg_replace('~[^0-9]~', '', $currency[0]),
////                                'purchasingCurrency' => preg_replace('~[^0-9]~', '', $currency[0]),
////                                'purchasingCurrency' => 'RUB',
////                                'price' => preg_replace('~[^0-9]~', '', $currency[0]),
//                            ]]);
                            /**
                             * CNY -  Юань
                             * RUB -  Российский рубль
                             * TRY -  Турецкая лира
                             * USD -  Доллар США
                             * EUR -  Евро
                             * UAH -  Гривна
                             * BYN -  Белорусский рубль
                             */
                            if (preg_match('~р|руб|РУБ~', $currencyAndPrice[0])) {
                                $currency = 'RUB';
                            } elseif (preg_match('~eur|евро|€~', $currencyAndPrice[0])) {
                                $currency = 'EUR';
                            } elseif (preg_match('~RMB~', $currencyAndPrice[0])) {
                                $currency = 'CNY';
                            } elseif (preg_match('~\\$~', $currencyAndPrice[0])) {
                                $currency = 'USD';
                            } elseif (preg_match('~BYN|byn~', $currencyAndPrice[0])) {
                                $currency = 'BYN';
                            }
                            (preg_match_all('~\B[\s]*([[:digit:]]|[[:alpha:]]|-)*([[:digit:]])[\s]*~', $article, $temp));

                            $article = explode(' ',trim(preg_replace('/[\x{0410}-\x{042F}]+.*[\x{0410}-\x{042F}]+/iu', '',$temp[0][0] ?? $article)))[0];
                            $inWorkProducts[$row_number][$article] = [
                                'article' => $article,
                                'price' => preg_replace('~[^0-9]~', '', $currencyAndPrice[0]),
                                'currency' => $currency ?? 'RUB',
                            ];
//                            dump($currencyAndPrice[0]);
//                            dd($article);
//                            dump($match, $article, preg_split('~[\s]+(–|-|[\s])+[\s]+~', $match));
//                            dd($currency);
                        } else {
//                            $article = trim(preg_replace('~[а-я]+.*[А-Я]+~', '', $article));
                            $article = explode(' ',trim(preg_replace('/[\x{0410}-\x{042F}]+.*[\x{0410}-\x{042F}]+/iu', '', $article)))[0];
                            $inWorkProducts[$row_number][$article] = [
                                'article' => $article,
                                'price' => 0,
                                'currency' => 'RUB',
                            ];
                        }
                        //dump($match, preg_split('~[\s]+(–|-|[\s])+[\s]+~', $match, PREG_SPLIT_NO_EMPTY));
                    }
//                    dd($item);
                }
            }
        }
        return $inWorkProducts;
    }
    public function setRawProducts($rawProducts, &$commands)
    {
        $webhook = new BitrixNew();
        foreach ($rawProducts as $row => $products) {
            foreach ($products as $article => $product) {
                $commands[$article] = $webhook->buildCommand('catalog.product.add', [
                    'fields' => [
                        'iblockId' => '15',
                        'name' => $article,
                        'property107' => $product['article'],
                        'purchasingPrice' => $product['price'],
                        'purchasingCurrency' => $product['currency'],
                    ]
                ]);
            }
        }
        return $commands;
    }

    public function getDealInWork($rawProducts, $createContacts, $tempContacts)
    {
        $webhook = new BitrixNew();
        /**
         *  Скан таблицы компаний
         */
//        $reader->changeSheet(0);
        $tableDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInWork.json'), true);
        //$tableDealInSuccess_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
        //$tableDealInLose_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
        foreach ($tableDealInWork as $row_number => $row) {
            $fields = [];
            /** Change */
            $fields['STAGE_ID'] = 'NEW';
            /** Change */
            $fields['COMMENTS'] = '';
            if (isset($row[1]) && trim($row[1]) != '') {
                $fields['TITLE'] = trim($row[1]);
            }
            if (isset($row[3]) && trim($row[3]) != '') {
                foreach ($tempContacts as $numberContact => $contact) {
                    if (isset($contact['name'] ) && $contact['name'] == trim($row[3])) {
                        if (isset($createContacts[$numberContact])) {
                            $fields['CONTACT_ID'] = $createContacts[$numberContact];
                        }
                        if (isset($contact['company_id'])) {
                            $fields['COMPANY_ID'] = $contact['company_id'];
                        }
//                        dump($createContacts[$numberContact]);
//                        dump($contact);
                        break;
                    }
                }

//                dump($rawInWorkProducts);

//                dd($tempContacts);
            }

            /** Comment */
            if (isset($row[6]) && trim($row[6]) != '') {
                if (mb_strtolower(trim($row[6])) == 'первичный / квалификация') {
                    $fields['STAGE_ID'] = 'NEW';
                } elseif (mb_strtolower(trim($row[6])) == 'проценка') {
                    $fields['STAGE_ID'] = 'PREPARATION';
                } elseif (mb_strtolower(trim($row[6])) == 'предложение') {
                    $fields['STAGE_ID'] = 'PREPAYMENT_INVOICE';
                } elseif (mb_strtolower(trim($row[6])) == 'горячий') {
                    $fields['STAGE_ID'] = 'EXECUTING';
                } elseif (mb_strtolower(trim($row[6])) == 'оплата') {
                    $fields['STAGE_ID'] = 'FINAL_INVOICE';
                } elseif (mb_strtolower(trim($row[6])) == 'авансом отгружено') {
                    $fields['STAGE_ID'] = 'UC_0S198Y';
                } elseif (mb_strtolower(trim($row[6])) == 'закупка') {
                    $fields['STAGE_ID'] = 'UC_VIJAAU';
                } elseif (mb_strtolower(trim($row[6])) == 'перемещение в офис') {
                    $fields['STAGE_ID'] = 'UC_JO8TL6';
                } elseif (mb_strtolower(trim($row[6])) == 'доставка') {
                    $fields['STAGE_ID'] = 'UC_YZYQX9';
                }
            }
            /** Comment */
            $currency = 'RUB';
            if (isset($rawProducts[$row_number])) {
                $fields['CURRENCY_ID'] = end($rawProducts[$row_number])['currency'] ?? 'RUB';
                $currency = $fields['CURRENCY_ID'];
            }

            if (isset($row[8]) && trim($row[8]) != '') {
                $fields['OPPORTUNITY'] = trim($row[8]);
            }
            if (isset($row[18]) && trim($row[18]) != '') {
                if(preg_match('~новая любая~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 53;
                }
                if (preg_match('~новая аналог~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 61;
                }
                if(preg_match('~новая оригинал~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 57;
                }
                if(preg_match('~б/у~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 59;
                }
                if (preg_match('~восстановленная~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 63;
                }
            }
            if (isset($row[25]) && trim($row[25]) != '') {
                if(mb_strtolower(trim($row[25])) == 'безнал ндс') {
                    $fields['UF_CRM_1724308462859'] = 65;
                } elseif (mb_strtolower(trim($row[25])) == 'ип без ндс') {
                    $fields['UF_CRM_1724308462859'] = 67;
                }  elseif (mb_strtolower(trim($row[25])) == 'самат кз') {
                    $fields['UF_CRM_1724308462859'] = 69;
                } elseif (mb_strtolower(trim($row[25])) == 'карта') {
                    $fields['UF_CRM_1724308462859'] = 71;
                } elseif (mb_strtolower(trim($row[25])) == 'аванс отгрузка') {
                    $fields['UF_CRM_1724308462859'] = 73;
                } elseif (mb_strtolower(trim($row[25])) == 'эквайринг') {
                    $fields['UF_CRM_1724308462859'] = 75;
                } elseif (mb_strtolower(trim($row[25])) == 'наличные') {
                    $fields['UF_CRM_1724308462859'] = 77;
                }
            }
            if (isset($row[23]) && trim($row[23]) != '') {
                $fields['UF_CRM_1724308514065'] = trim($row[23])."|$currency";
            }
            if (isset($row[27]) && trim($row[27]) != '') {
                $fields['UF_CRM_1724308525883'] = trim($row[27])."|$currency";
            }

            if (isset($row[85]) && trim($row[85]) != '') {
                $fields['COMMENTS'] .= trim($row[85]) . "\n";
            }
            if (isset($row[86]) && trim($row[86]) != '') {
                $fields['COMMENTS'] .= trim($row[86]) . "\n";
            }
            if (isset($row[87]) && trim($row[87]) != '') {
                $fields['COMMENTS'] .= trim($row[87]) . "\n";
            }
            if (isset($row[88]) && trim($row[88]) != '') {
                $fields['COMMENTS'] .= trim($row[88]) . "\n";
            }
            if (isset($row[89]) && trim($row[89]) != '') {
                $fields['COMMENTS'] .= trim($row[89]) . "\n";
            }
            //UF_CRM_1724308160659 - Запчасть
            // 53 - Новая любая
            // 61 - Новая аналог
            // 57 - Новая оригинал
            // 59 - Б/У
            // 63 - Восстановленная
            //UF_CRM_1724308462859 - Тип оплаты
            // 65 - Безнал НДС
            // 67 - ИП без НДС
            // 69 - САМАТ КЗ
            // 71 - Карта
            // 73 - АВАНС ОТГРУЗКА
            // 75 - Эквайринг
            // 77 - Наличные
            //UF_CRM_1724308514065 - Сумма предоплаты - 23
            //UF_CRM_1724308525883 - Сумма доплаты - 27
            if (!empty($fields)) {
                $commands[$row_number] = $webhook->buildCommand('crm.deal.add', [
                    'fields' => $fields
                ]);
            }
        }
        return $commands;
    }
    public function getDealInSuccess($rawProducts, $createContacts, $tempContacts)
    {
        $webhook = new BitrixNew();
        /**
         *  Скан таблицы компаний
         */
//        $reader->changeSheet(0);
        $tableDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
        //$tableDealInSuccess_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
        //$tableDealInLose_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
        foreach ($tableDealInWork as $row_number => $row) {
            $fields = [];
            /** Change */
            $fields['STAGE_ID'] = 'WON';
            /** Change */
            if (isset($row[1]) && trim($row[1]) != '') {
                $fields['TITLE'] = trim($row[1]);
            }
            $fields['COMMENTS'] = '';
            if (isset($row[3]) && trim($row[3]) != '') {
                foreach ($tempContacts as $numberContact => $contact) {
                    if (isset($contact['name'] ) && $contact['name'] == trim($row[3])) {
                        if (isset($createContacts[$numberContact])) {
                            $fields['CONTACT_ID'] = $createContacts[$numberContact];
                        }
                        if (isset($contact['company_id'])) {
                            $fields['COMPANY_ID'] = $contact['company_id'];
                        }
//                        dump($createContacts[$numberContact]);
//                        dump($contact);
                        break;
                    }
                }

//                dump($rawInWorkProducts);

//                dd($tempContacts);
            }

            $currency = 'RUB';
            if (isset($rawProducts[$row_number])) {
                $fields['CURRENCY_ID'] = end($rawProducts[$row_number])['currency'] ?? 'RUB';
                $currency = $fields['CURRENCY_ID'];
            }

            if (isset($row[8]) && trim($row[8]) != '') {
                $fields['OPPORTUNITY'] = trim($row[8]);
            }
            if (isset($row[18]) && trim($row[18]) != '') {
                if(preg_match('~новая любая~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 53;
                }
                if (preg_match('~новая аналог~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 61;
                }
                if(preg_match('~новая оригинал~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 57;
                }
                if(preg_match('~б/у~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 59;
                }
                if (preg_match('~восстановленная~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 63;
                }
            }
            if (isset($row[25]) && trim($row[25]) != '') {
                if(mb_strtolower(trim($row[25])) == 'безнал ндс') {
                    $fields['UF_CRM_1724308462859'] = 65;
                } elseif (mb_strtolower(trim($row[25])) == 'ип без ндс') {
                    $fields['UF_CRM_1724308462859'] = 67;
                }  elseif (mb_strtolower(trim($row[25])) == 'самат кз') {
                    $fields['UF_CRM_1724308462859'] = 69;
                } elseif (mb_strtolower(trim($row[25])) == 'карта') {
                    $fields['UF_CRM_1724308462859'] = 71;
                } elseif (mb_strtolower(trim($row[25])) == 'аванс отгрузка') {
                    $fields['UF_CRM_1724308462859'] = 73;
                } elseif (mb_strtolower(trim($row[25])) == 'эквайринг') {
                    $fields['UF_CRM_1724308462859'] = 75;
                } elseif (mb_strtolower(trim($row[25])) == 'наличные') {
                    $fields['UF_CRM_1724308462859'] = 77;
                }
            }
            if (isset($row[23]) && trim($row[23]) != '') {
                $fields['UF_CRM_1724308514065'] = trim($row[23])."|$currency";
            }
            if (isset($row[27]) && trim($row[27]) != '') {
                $fields['UF_CRM_1724308525883'] = trim($row[27])."|$currency";
            }

            if (isset($row[85]) && trim($row[85]) != '') {
                $fields['COMMENTS'] .= trim($row[85]) . "\n";
            }
            if (isset($row[86]) && trim($row[86]) != '') {
                $fields['COMMENTS'] .= trim($row[86]) . "\n";
            }
            if (isset($row[87]) && trim($row[87]) != '') {
                $fields['COMMENTS'] .= trim($row[87]) . "\n";
            }
            if (isset($row[88]) && trim($row[88]) != '') {
                $fields['COMMENTS'] .= trim($row[88]) . "\n";
            }
            if (isset($row[89]) && trim($row[89]) != '') {
                $fields['COMMENTS'] .= trim($row[89]) . "\n";
            }

            if (!empty($fields)) {
                $commands[$row_number] = $webhook->buildCommand('crm.deal.add', [
                    'fields' => $fields
                ]);
            }
        }
        return $commands;
    }
    public function getDealInLose_0($rawProducts, $createContacts, $tempContacts)
    {
        $webhook = new BitrixNew();
        /**
         *  Скан таблицы компаний
         */
//        $reader->changeSheet(0);
        $tableDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
        //$tableDealInSuccess_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
        //$tableDealInLose_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
        foreach ($tableDealInWork as $row_number => $row) {
            $fields = [];
            /** Change */
            $fields['STAGE_ID'] = 'LOSE';
            /** Change */
            if (isset($row[1]) && trim($row[1]) != '') {
                $fields['TITLE'] = trim($row[1]);
            }
            $fields['COMMENTS'] = '';
            if (isset($row[3]) && trim($row[3]) != '') {
                foreach ($tempContacts as $numberContact => $contact) {
                    if (isset($contact['name'] ) && $contact['name'] == trim($row[3])) {
                        if (isset($createContacts[$numberContact])) {
                            $fields['CONTACT_ID'] = $createContacts[$numberContact];
                        }
                        if (isset($contact['company_id'])) {
                            $fields['COMPANY_ID'] = $contact['company_id'];
                        }
//                        dump($createContacts[$numberContact]);
//                        dump($contact);
                        break;
                    }
                }

//                dump($rawInWorkProducts);

//                dd($tempContacts);
            }

            $currency = 'RUB';
            if (isset($rawProducts[$row_number])) {
                $fields['CURRENCY_ID'] = end($rawProducts[$row_number])['currency'] ?? 'RUB';
                $currency = $fields['CURRENCY_ID'];
            }

            if (isset($row[8]) && trim($row[8]) != '') {
                $fields['OPPORTUNITY'] = trim($row[8]);
            }
            if (isset($row[18]) && trim($row[18]) != '') {
                if(preg_match('~новая любая~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 53;
                }
                if (preg_match('~новая аналог~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 61;
                }
                if(preg_match('~новая оригинал~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 57;
                }
                if(preg_match('~б/у~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 59;
                }
                if (preg_match('~восстановленная~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 63;
                }
            }
            if (isset($row[25]) && trim($row[25]) != '') {
                if(mb_strtolower(trim($row[25])) == 'безнал ндс') {
                    $fields['UF_CRM_1724308462859'] = 65;
                } elseif (mb_strtolower(trim($row[25])) == 'ип без ндс') {
                    $fields['UF_CRM_1724308462859'] = 67;
                }  elseif (mb_strtolower(trim($row[25])) == 'самат кз') {
                    $fields['UF_CRM_1724308462859'] = 69;
                } elseif (mb_strtolower(trim($row[25])) == 'карта') {
                    $fields['UF_CRM_1724308462859'] = 71;
                } elseif (mb_strtolower(trim($row[25])) == 'аванс отгрузка') {
                    $fields['UF_CRM_1724308462859'] = 73;
                } elseif (mb_strtolower(trim($row[25])) == 'эквайринг') {
                    $fields['UF_CRM_1724308462859'] = 75;
                } elseif (mb_strtolower(trim($row[25])) == 'наличные') {
                    $fields['UF_CRM_1724308462859'] = 77;
                }
            }
            if (isset($row[23]) && trim($row[23]) != '') {
                $fields['UF_CRM_1724308514065'] = trim($row[23])."|$currency";
            }
            if (isset($row[27]) && trim($row[27]) != '') {
                $fields['UF_CRM_1724308525883'] = trim($row[27])."|$currency";
            }

            if (isset($row[85]) && trim($row[85]) != '') {
                $fields['COMMENTS'] .= trim($row[85]) . "\n";
            }
            if (isset($row[86]) && trim($row[86]) != '') {
                $fields['COMMENTS'] .= trim($row[86]) . "\n";
            }
            if (isset($row[87]) && trim($row[87]) != '') {
                $fields['COMMENTS'] .= trim($row[87]) . "\n";
            }
            if (isset($row[88]) && trim($row[88]) != '') {
                $fields['COMMENTS'] .= trim($row[88]) . "\n";
            }
            if (isset($row[89]) && trim($row[89]) != '') {
                $fields['COMMENTS'] .= trim($row[89]) . "\n";
            }
            //UF_CRM_1724308160659 - Запчасть
            // 53 - Новая любая
            // 61 - Новая аналог
            // 57 - Новая оригинал
            // 59 - Б/У
            // 63 - Восстановленная
            //UF_CRM_1724308462859 - Тип оплаты
            // 65 - Безнал НДС
            // 67 - ИП без НДС
            // 69 - САМАТ КЗ
            // 71 - Карта
            // 73 - АВАНС ОТГРУЗКА
            // 75 - Эквайринг
            // 77 - Наличные
            //UF_CRM_1724308514065 - Сумма предоплаты - 23
            //UF_CRM_1724308525883 - Сумма доплаты - 27
            if (!empty($fields)) {
                $commands[$row_number] = $webhook->buildCommand('crm.deal.add', [
                    'fields' => $fields
                ]);
            }
        }
        return $commands;
    }
    public function getDealInLose_1($rawProducts, $createContacts, $tempContacts)
    {
        $webhook = new BitrixNew();
        /**
         *  Скан таблицы компаний
         */
//        $reader->changeSheet(0);
        $tableDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_1.json'), true);
        //$tableDealInSuccess_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
        //$tableDealInLose_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
        foreach ($tableDealInWork as $row_number => $row) {
            $fields = [];
            /** Change */
            $fields['STAGE_ID'] = 'LOSE';
            /** Change */
            if (isset($row[1]) && trim($row[1]) != '') {
                $fields['TITLE'] = trim($row[1]);
            }
            $fields['COMMENTS'] = '';
            if (isset($row[3]) && trim($row[3]) != '') {
                foreach ($tempContacts as $numberContact => $contact) {
                    if (isset($contact['name'] ) && $contact['name'] == trim($row[3])) {
                        if (isset($createContacts[$numberContact])) {
                            $fields['CONTACT_ID'] = $createContacts[$numberContact];
                        }
                        if (isset($contact['company_id'])) {
                            $fields['COMPANY_ID'] = $contact['company_id'];
                        }
//                        dump($createContacts[$numberContact]);
//                        dump($contact);
                        break;
                    }
                }

//                dump($rawInWorkProducts);

//                dd($tempContacts);
            }

            $currency = 'RUB';
            if (isset($rawProducts[$row_number])) {
                $fields['CURRENCY_ID'] = end($rawProducts[$row_number])['currency'] ?? 'RUB';
                $currency = $fields['CURRENCY_ID'];
            }

            if (isset($row[8]) && trim($row[8]) != '') {
                $fields['OPPORTUNITY'] = trim($row[8]);
            }
            if (isset($row[18]) && trim($row[18]) != '') {
                if(preg_match('~новая любая~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 53;
                }
                if (preg_match('~новая аналог~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 61;
                }
                if(preg_match('~новая оригинал~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 57;
                }
                if(preg_match('~б/у~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 59;
                }
                if (preg_match('~восстановленная~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 63;
                }
            }
            if (isset($row[25]) && trim($row[25]) != '') {
                if(mb_strtolower(trim($row[25])) == 'безнал ндс') {
                    $fields['UF_CRM_1724308462859'] = 65;
                } elseif (mb_strtolower(trim($row[25])) == 'ип без ндс') {
                    $fields['UF_CRM_1724308462859'] = 67;
                }  elseif (mb_strtolower(trim($row[25])) == 'самат кз') {
                    $fields['UF_CRM_1724308462859'] = 69;
                } elseif (mb_strtolower(trim($row[25])) == 'карта') {
                    $fields['UF_CRM_1724308462859'] = 71;
                } elseif (mb_strtolower(trim($row[25])) == 'аванс отгрузка') {
                    $fields['UF_CRM_1724308462859'] = 73;
                } elseif (mb_strtolower(trim($row[25])) == 'эквайринг') {
                    $fields['UF_CRM_1724308462859'] = 75;
                } elseif (mb_strtolower(trim($row[25])) == 'наличные') {
                    $fields['UF_CRM_1724308462859'] = 77;
                }
            }
            if (isset($row[23]) && trim($row[23]) != '') {
                $fields['UF_CRM_1724308514065'] = trim($row[23])."|$currency";
            }
            if (isset($row[27]) && trim($row[27]) != '') {
                $fields['UF_CRM_1724308525883'] = trim($row[27])."|$currency";
            }

            if (isset($row[85]) && trim($row[85]) != '') {
                $fields['COMMENTS'] .= trim($row[85]) . "\n";
            }
            if (isset($row[86]) && trim($row[86]) != '') {
                $fields['COMMENTS'] .= trim($row[86]) . "\n";
            }
            if (isset($row[87]) && trim($row[87]) != '') {
                $fields['COMMENTS'] .= trim($row[87]) . "\n";
            }
            if (isset($row[88]) && trim($row[88]) != '') {
                $fields['COMMENTS'] .= trim($row[88]) . "\n";
            }
            if (isset($row[89]) && trim($row[89]) != '') {
                $fields['COMMENTS'] .= trim($row[89]) . "\n";
            }
            //UF_CRM_1724308160659 - Запчасть
            // 53 - Новая любая
            // 61 - Новая аналог
            // 57 - Новая оригинал
            // 59 - Б/У
            // 63 - Восстановленная
            //UF_CRM_1724308462859 - Тип оплаты
            // 65 - Безнал НДС
            // 67 - ИП без НДС
            // 69 - САМАТ КЗ
            // 71 - Карта
            // 73 - АВАНС ОТГРУЗКА
            // 75 - Эквайринг
            // 77 - Наличные
            //UF_CRM_1724308514065 - Сумма предоплаты - 23
            //UF_CRM_1724308525883 - Сумма доплаты - 27
            if (!empty($fields)) {
                $commands[$row_number] = $webhook->buildCommand('crm.deal.add', [
                    'fields' => $fields
                ]);
            }
        }
        return $commands;
    }
    public function getDealInLose_2($rawProducts, $createContacts, $tempContacts)
    {
        $webhook = new BitrixNew();
        /**
         *  Скан таблицы компаний
         */
//        $reader->changeSheet(0);
        $tableDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_2.json'), true);
        //$tableDealInSuccess_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
        //$tableDealInLose_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
        foreach ($tableDealInWork as $row_number => $row) {
            $fields = [];
            /** Change */
            $fields['STAGE_ID'] = 'LOSE';
            /** Change */
            if (isset($row[1]) && trim($row[1]) != '') {
                $fields['TITLE'] = trim($row[1]);
            }
            $fields['COMMENTS'] = '';
            if (isset($row[3]) && trim($row[3]) != '') {
                foreach ($tempContacts as $numberContact => $contact) {
                    if (isset($contact['name'] ) && $contact['name'] == trim($row[3])) {
                        if (isset($createContacts[$numberContact])) {
                            $fields['CONTACT_ID'] = $createContacts[$numberContact];
                        }
                        if (isset($contact['company_id'])) {
                            $fields['COMPANY_ID'] = $contact['company_id'];
                        }
//                        dump($createContacts[$numberContact]);
//                        dump($contact);
                        break;
                    }
                }

//                dump($rawInWorkProducts);

//                dd($tempContacts);
            }

            $currency = 'RUB';
            if (isset($rawProducts[$row_number])) {
                $fields['CURRENCY_ID'] = end($rawProducts[$row_number])['currency'] ?? 'RUB';
                $currency = $fields['CURRENCY_ID'];
            }

            if (isset($row[8]) && trim($row[8]) != '') {
                $fields['OPPORTUNITY'] = trim($row[8]);
            }
            if (isset($row[18]) && trim($row[18]) != '') {
                if(preg_match('~новая любая~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 53;
                }
                if (preg_match('~новая аналог~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 61;
                }
                if(preg_match('~новая оригинал~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 57;
                }
                if(preg_match('~б/у~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 59;
                }
                if (preg_match('~восстановленная~', mb_strtolower(trim($row[18])))) {
                    $fields['UF_CRM_1724308160659'][] = 63;
                }
            }
            if (isset($row[25]) && trim($row[25]) != '') {
                if(mb_strtolower(trim($row[25])) == 'безнал ндс') {
                    $fields['UF_CRM_1724308462859'] = 65;
                } elseif (mb_strtolower(trim($row[25])) == 'ип без ндс') {
                    $fields['UF_CRM_1724308462859'] = 67;
                }  elseif (mb_strtolower(trim($row[25])) == 'самат кз') {
                    $fields['UF_CRM_1724308462859'] = 69;
                } elseif (mb_strtolower(trim($row[25])) == 'карта') {
                    $fields['UF_CRM_1724308462859'] = 71;
                } elseif (mb_strtolower(trim($row[25])) == 'аванс отгрузка') {
                    $fields['UF_CRM_1724308462859'] = 73;
                } elseif (mb_strtolower(trim($row[25])) == 'эквайринг') {
                    $fields['UF_CRM_1724308462859'] = 75;
                } elseif (mb_strtolower(trim($row[25])) == 'наличные') {
                    $fields['UF_CRM_1724308462859'] = 77;
                }
            }
            if (isset($row[23]) && trim($row[23]) != '') {
                $fields['UF_CRM_1724308514065'] = trim($row[23])."|$currency";
            }
            if (isset($row[27]) && trim($row[27]) != '') {
                $fields['UF_CRM_1724308525883'] = trim($row[27])."|$currency";
            }

            if (isset($row[85]) && trim($row[85]) != '') {
                $fields['COMMENTS'] .= trim($row[85]) . "\n";
            }
            if (isset($row[86]) && trim($row[86]) != '') {
                $fields['COMMENTS'] .= trim($row[86]) . "\n";
            }
            if (isset($row[87]) && trim($row[87]) != '') {
                $fields['COMMENTS'] .= trim($row[87]) . "\n";
            }
            if (isset($row[88]) && trim($row[88]) != '') {
                $fields['COMMENTS'] .= trim($row[88]) . "\n";
            }
            if (isset($row[89]) && trim($row[89]) != '') {
                $fields['COMMENTS'] .= trim($row[89]) . "\n";
            }
            //UF_CRM_1724308160659 - Запчасть
            // 53 - Новая любая
            // 61 - Новая аналог
            // 57 - Новая оригинал
            // 59 - Б/У
            // 63 - Восстановленная
            //UF_CRM_1724308462859 - Тип оплаты
            // 65 - Безнал НДС
            // 67 - ИП без НДС
            // 69 - САМАТ КЗ
            // 71 - Карта
            // 73 - АВАНС ОТГРУЗКА
            // 75 - Эквайринг
            // 77 - Наличные
            //UF_CRM_1724308514065 - Сумма предоплаты - 23
            //UF_CRM_1724308525883 - Сумма доплаты - 27
            if (!empty($fields)) {
                $commands[$row_number] = $webhook->buildCommand('crm.deal.add', [
                    'fields' => $fields
                ]);
            }
        }
        return $commands;
    }

    public function setProductsInWork($createProducts, $rawProducts, $createDeal)
    {
        $webhook = new BitrixNew();
        /**
         *  Скан таблицы компаний
         */
//        $reader->changeSheet(0);
        //['element']['id']
        $tableDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInWork.json'), true);
        //$tableDealInSuccess_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
        //$tableDealInLose_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
        foreach ($tableDealInWork as $row_number => $row) {
            $products_response = [];

            if (isset($rawProducts[$row_number])) {
                foreach ($rawProducts[$row_number] as $key => $products) {
                    if (isset($createProducts[$key]['element']['id'])) {
                        $products_response[] = [
                            'PRODUCT_ID' => $createProducts[$key]['element']['id'],
                            'PRICE' => $products['price'],
                            'QUANTITY' => 1,
                        ];
                    }
                }
            } else {
                if (isset($row[85]) && trim($row[85]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[85]),
                    ];
                }
                if (isset($row[86]) && trim($row[86]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[86]),
                    ];
                }
                if (isset($row[87]) && trim($row[87]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[87]),
                    ];
                }
                if (isset($row[88]) && trim($row[88]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[88]),
                    ];
                }
                if (isset($row[89]) && trim($row[89]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[89]),
                    ];
                }
                //productName
            }

            if (empty($products_response)) {
                continue;
            }
            $commands[$row_number] = $webhook->buildCommand('crm.deal.productrows.set', [
                'id' => $createDeal[$row_number],
                'rows' => $products_response,
            ]);
        }
        return $commands;
    }
    public function setProductsInSuccess($createProducts, $rawProducts, $createDeal)
    {
        $webhook = new BitrixNew();
        /**
         *  Скан таблицы компаний
         */
//        $reader->changeSheet(0);
        //['element']['id']
        $tableDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
        //$tableDealInSuccess_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
        //$tableDealInLose_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
        foreach ($tableDealInWork as $row_number => $row) {
            $products_response = [];

            if (isset($rawProducts[$row_number])) {
                foreach ($rawProducts[$row_number] as $key => $products) {
                    if (isset($createProducts[$key]['element']['id'])) {
                        $products_response[] = [
                            'PRODUCT_ID' => $createProducts[$key]['element']['id'],
                            'PRICE' => $products['price'],
                            'QUANTITY' => 1,
                        ];
                    }
                }
            } else {
                if (isset($row[85]) && trim($row[85]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[85]),
                    ];
                }
                if (isset($row[86]) && trim($row[86]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[86]),
                    ];
                }
                if (isset($row[87]) && trim($row[87]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[87]),
                    ];
                }
                if (isset($row[88]) && trim($row[88]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[88]),
                    ];
                }
                if (isset($row[89]) && trim($row[89]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[89]),
                    ];
                }
                //productName
            }

            if (empty($products_response)) {
                continue;
            }
            $commands[$row_number] = $webhook->buildCommand('crm.deal.productrows.set', [
                'id' => $createDeal[$row_number],
                'rows' => $products_response,
            ]);
        }
        return $commands;
    }
    public function setProductsInLose_0($createProducts, $rawProducts, $createDeal)
    {
        $webhook = new BitrixNew();
        /**
         *  Скан таблицы компаний
         */
//        $reader->changeSheet(0);
        //['element']['id']
        $tableDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
        foreach ($tableDealInWork as $row_number => $row) {
            $products_response = [];

            if (isset($rawProducts[$row_number])) {
                foreach ($rawProducts[$row_number] as $key => $products) {
                    if (isset($createProducts[$key]['element']['id'])) {
                        $products_response[] = [
                            'PRODUCT_ID' => $createProducts[$key]['element']['id'],
                            'PRICE' => $products['price'],
                            'QUANTITY' => 1,
                        ];
                    }
                }
            } else {
                if (isset($row[85]) && trim($row[85]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[85]),
                    ];
                }
                if (isset($row[86]) && trim($row[86]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[86]),
                    ];
                }
                if (isset($row[87]) && trim($row[87]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[87]),
                    ];
                }
                if (isset($row[88]) && trim($row[88]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[88]),
                    ];
                }
                if (isset($row[89]) && trim($row[89]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[89]),
                    ];
                }
                //productName
            }

            if (empty($products_response)) {
                continue;
            }
            $commands[$row_number] = $webhook->buildCommand('crm.deal.productrows.set', [
                'id' => $createDeal[$row_number],
                'rows' => $products_response,
            ]);
        }
        return $commands;
    }
    public function setProductsInLose_1($createProducts, $rawProducts, $createDeal)
    {
        $webhook = new BitrixNew();
        /**
         *  Скан таблицы компаний
         */
//        $reader->changeSheet(0);
        //['element']['id']
        $tableDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_1.json'), true);
        foreach ($tableDealInWork as $row_number => $row) {
            $products_response = [];

            if (isset($rawProducts[$row_number])) {
                foreach ($rawProducts[$row_number] as $key => $products) {
                    if (isset($createProducts[$key]['element']['id'])) {
                        $products_response[] = [
                            'PRODUCT_ID' => $createProducts[$key]['element']['id'],
                            'PRICE' => $products['price'],
                            'QUANTITY' => 1,
                        ];
                    }
                }
            } else {
                if (isset($row[85]) && trim($row[85]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[85]),
                    ];
                }
                if (isset($row[86]) && trim($row[86]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[86]),
                    ];
                }
                if (isset($row[87]) && trim($row[87]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[87]),
                    ];
                }
                if (isset($row[88]) && trim($row[88]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[88]),
                    ];
                }
                if (isset($row[89]) && trim($row[89]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[89]),
                    ];
                }
                //productName
            }

            if (empty($products_response)) {
                continue;
            }
            $commands[$row_number] = $webhook->buildCommand('crm.deal.productrows.set', [
                'id' => $createDeal[$row_number],
                'rows' => $products_response,
            ]);
        }
        return $commands;
    }
    public function setProductsInLose_2($createProducts, $rawProducts, $createDeal)
    {
        $webhook = new BitrixNew();
        /**
         *  Скан таблицы компаний
         */
//        $reader->changeSheet(0);
        //['element']['id']
        $tableDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_2.json'), true);
        foreach ($tableDealInWork as $row_number => $row) {
            $products_response = [];

            if (isset($rawProducts[$row_number])) {
                foreach ($rawProducts[$row_number] as $key => $products) {
                    if (isset($createProducts[$key]['element']['id'])) {
                        $products_response[] = [
                            'PRODUCT_ID' => $createProducts[$key]['element']['id'],
                            'PRICE' => $products['price'],
                            'QUANTITY' => 1,
                        ];
                    }
                }
            } else {
                if (isset($row[85]) && trim($row[85]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[85]),
                    ];
                }
                if (isset($row[86]) && trim($row[86]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[86]),
                    ];
                }
                if (isset($row[87]) && trim($row[87]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[87]),
                    ];
                }
                if (isset($row[88]) && trim($row[88]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[88]),
                    ];
                }
                if (isset($row[89]) && trim($row[89]) != '') {
                    $products_response[] = [
                        'PRODUCT_NAME' => trim($row[89]),
                    ];
                }
                //productName
            }

            if (empty($products_response)) {
                continue;
            }
            $commands[$row_number] = $webhook->buildCommand('crm.deal.productrows.set', [
                'id' => $createDeal[$row_number],
                'rows' => $products_response,
            ]);
        }
        return $commands;
    }

    public function actionTest()
    {
        $webhook = new BitrixNew();
//        dd($webhook->request('user.search', ['FIND' => 'Ермилова Юлия']));

        /**
         * Формирование сырых продуктов
         */
//        $rawInWorkProducts = $this->getProductInWork();
//        $rawInSuccessProducts = $this->getProductInSuccess();
//        $rawInLoseProducts = $this->getProductInLose();
//        file_put_contents(Yii::getAlias("@app") . "/modules/processor_post/temp/rawInWorkProducts.json", json_encode($rawInWorkProducts));
//        file_put_contents(Yii::getAlias("@app") . "/modules/processor_post/temp/rawInSuccessProducts.json", json_encode($rawInSuccessProducts));
//        file_put_contents(Yii::getAlias("@app") . "/modules/processor_post/temp/rawInLoseProducts.json", json_encode($rawInLoseProducts));

//        $rawInWorkProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/rawInWorkProducts.json'), true);
//        $rawInSuccessProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/rawInSuccessProducts.json'), true);
//        $rawInLoseProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/rawInLoseProducts.json'), true);

//        dd($rawInLoseProducts);
//        dd($rawInSuccessProducts);
//        dd($rawInWorkProducts);

        /**
         * Формирование команд продуктов и их создание
         */
//        $createProducts = [];
//        $commands = [];
//        $this->setRawProducts($rawInWorkProducts, $commands);
//        $this->setRawProducts($rawInSuccessProducts, $commands);
//        $this->setRawProducts($rawInLoseProducts, $commands);
//        die;
//        $commandChunks = array_chunk($commands, 50, true);
//
//        foreach ($commandChunks as $key => $command)
//        {
//            $createProducts = array_replace($createProducts, $webhook->batchRequest($command));
//            sleep(2);
//        }
//        file_put_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createProducts.json', json_encode($createProducts));


//        $createProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createProducts.json'), true);

        /**
         * Формирование команд компаний и их создание
         */
//        $commands = $this->getCompanies();
//        $commandChunks = array_chunk($commands, 50, true);
//        $createCompanies = [];
//        foreach ($commandChunks as $key => $command)
//        {
//            $createCompanies = array_replace($createCompanies, $webhook->batchRequest($command));
//            sleep(2);
//        }
//        file_put_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createCompanies.json', json_encode($createCompanies));


//        $createCompanies =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createCompanies.json'), true);




        /**
         * Формирование команд контактов и их создание
         */
//        $commands = $this->getContacts($createCompanies);
////        dd($tempContacts);
//        die;
//        $commandChunks = array_chunk($commands, 50, true);
//        $createContacts= [];
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $createContacts = array_replace($createContacts, $webhook->batchRequest($command));
//            } catch (\Exception $exception) {
//
//            }
//            sleep(2);
//        }
//        file_put_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createContacts.json', json_encode($createContacts));

//        $createContacts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createContacts.json'), true);
//        $tempContacts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tempContacts.json'), true);
//        $createProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createProducts.json'), true);
//        $rawInWorkProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/rawInWorkProducts.json'), true);
//        $rawInSuccessProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/rawInSuccessProducts.json'), true);
//        $rawInLoseProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/rawInLoseProducts.json'), true);

        /**
         * Формирование команд сделок в работе и их создание
         */
//        $commands = $this->getDealInWork($rawInWorkProducts, $createContacts, $tempContacts);
//        $commandChunks = array_chunk($commands, 50, true);
//        $createDealInWork= [];
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $createDealInWork = array_replace($createDealInWork, $webhook->batchRequest($command));
//            } catch (\Exception $exception) {
//
//            }
//            sleep(2);
//        }
//        file_put_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createDealInWork.json', json_encode($createDealInWork));
//        dump($createProducts);
//        die;

//        $commands = $this->getDealInSuccess($rawInSuccessProducts, $createContacts, $tempContacts);
//        $commandChunks = array_chunk($commands, 50, true);
//        $createDealInSuccess= [];
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $createDealInSuccess = array_replace($createDealInSuccess, $webhook->batchRequest($command));
//            } catch (\Exception $exception) {
//
//            }
//            sleep(2);
//        }
//        file_put_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createDealInSuccess.json', json_encode($createDealInSuccess));
//        die;


//        $commands = $this->getDealInLose_0($rawInLoseProducts, $createContacts, $tempContacts);
//        $commandChunks = array_chunk($commands, 50, true);
//        $createDealInLose_0= [];
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $createDealInLose_0 = array_replace($createDealInLose_0, $webhook->batchRequest($command));
//            } catch (\Exception $exception) {
//
//            }
//            sleep(2);
//        }
//        file_put_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createDealInLose_0.json', json_encode($createDealInLose_0));
//        die;


//        $commands = $this->getDealInLose_1($rawInLoseProducts, $createContacts, $tempContacts);
//        $commandChunks = array_chunk($commands, 50, true);
//        $createDealInLose_1= [];
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $createDealInLose_1 = array_replace($createDealInLose_1, $webhook->batchRequest($command));
//            } catch (\Exception $exception) {
//
//            }
//            sleep(2);
//        }
//        file_put_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createDealInLose_1.json', json_encode($createDealInLose_1));
//        die;


//        $commands = $this->getDealInLose_2($rawInLoseProducts, $createContacts, $tempContacts);
//        $commandChunks = array_chunk($commands, 50, true);
//        $createDealInLose_2= [];
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $createDealInLose_2 = array_replace($createDealInLose_2, $webhook->batchRequest($command));
//            } catch (\Exception $exception) {
//
//            }
//            sleep(2);
//        }
//        file_put_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createDealInLose_2.json', json_encode($createDealInLose_2));
//        die;

//        $createDealInWork =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createDealInWork.json'), true);
//        $commands = $this->setProductsInWork($createProducts, $rawInWorkProducts, $createDealInWork);
//        $commandChunks = array_chunk($commands, 50, true);
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $webhook->batchRequest($command);
//            } catch (\Exception $exception) {
//                dump('Ошибочка');
//            }
//            sleep(2);
//        }
//        $createDealInSuccess =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createDealInSuccess.json'), true);
//        $commands = $this->setProductsInSuccess($createProducts, $rawInSuccessProducts, $createDealInSuccess);
//        $commandChunks = array_chunk($commands, 50, true);
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $webhook->batchRequest($command);
//            } catch (\Exception $exception) {
//                dump('Ошибочка - setProductsInSuccess' . "$key");
//            }
//            sleep(2);
//        }

//        $createDealInLose_0 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createDealInLose_0.json'), true);
//        $commands = $this->setProductsInLose_0($createProducts, $rawInLoseProducts, $createDealInLose_0);
//        $commandChunks = array_chunk($commands, 50, true);
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $webhook->batchRequest($command);
//            } catch (\Exception $exception) {
//                dump('Ошибочка - setProductsInLose_0' . "$key");
//            }
//            sleep(2);
//        }
//
//
//        $createDealInLose_1 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createDealInLose_1.json'), true);
//        $commands = $this->setProductsInLose_1($createProducts, $rawInLoseProducts, $createDealInLose_1);
//        $commandChunks = array_chunk($commands, 50, true);
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $webhook->batchRequest($command);
//            } catch (\Exception $exception) {
//                dump('Ошибочка - setProductsInLose_1' . "$key");
//            }
//            sleep(2);
//        }
//
//
//        $createDealInLose_2 =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createDealInLose_2.json'), true);
//        $commands = $this->setProductsInLose_2($createProducts, $rawInLoseProducts, $createDealInLose_2);
//        $commandChunks = array_chunk($commands, 50, true);
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $webhook->batchRequest($command);
//            } catch (\Exception $exception) {
//                dump('Ошибочка - setProductsInLose_2' . "$key");
//            }
//            sleep(2);
//        }
//
//        dd('ok');


//        dump($webhook->request('catalog.document.add', [
//            'fields' => [
//                'docType' => 'A',
//                'createdBy' => 5,
//                'contractorId' => 1,
//                'responsibleId' => '1',
//                'currency' => 'RUB',
//                'status' => 'Y',
//            ]
//        ]));

//        dump($webhook->request('catalog.document.update', [
//            'fields' => [
////                'docType' => 'A',
////                'createdBy' => 5,
//                'contractorId' => '1',
////                'responsibleId' => '5',
////                'currency' => 'RUB',
////                'status' => 'Y',
//                'total' => '2000',
//            ],
//            'id' => 23,
//        ]));

//        dump($webhook->request('catalog.document.conduct', [
//            'id' => 23,
//        ]));
//        dd($webhook->request('catalog.document.list', ['select' => ['contractorId']]));
//        die;

//        $createProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/createProducts.json'), true);
//        $rawInWorkProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/rawInWorkProducts.json'), true);
//        $rawInSuccessProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/rawInSuccessProducts.json'), true);
//        $rawInLoseProducts =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/rawInLoseProducts.json'), true);
//
////        dump($rawInSuccessProducts);
////        dump($table0);
////        dump($table1);
//
//        $this->getWorkProduct($createProducts, $rawInWorkProducts);
//        $this->getSuccessProduct($createProducts, $rawInSuccessProducts);
//        $this->getLoseProduct0($createProducts, $rawInLoseProducts);
//        $this->getLoseProduct1($createProducts, $rawInLoseProducts);
//        $this->getLoseProduct2($createProducts, $rawInLoseProducts);
//        unset($createProducts);
//        unset($rawInWorkProducts);
//        unset($rawInSuccessProducts);
//        unset($rawInLoseProducts);
//        file_put_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/documents.json', json_encode($this->documents));


//        die;
//        $commands = [];
//        $documents =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/documents.json'), true);
////        dd($documents[35]);
//        $sum = [];
//        foreach ($documents as $id => $document) {
//            if ($id != 35) {
//                continue;
//            }
//            $docId = 109;
//            $count = 0;
//            foreach ($document as $item) {
//                if (!isset($sum[$id])) {
//                    $sum[$id] = (double)$item['purchasingPrice'];
//                } else {
//                    $sum[$id] += (double)$item['purchasingPrice'];
//                }
//                if ($count == 100) {
////                    $commands[] = $webhook->buildCommand('catalog.document.add', ['fields' => [
////                        'docType' => 'A',
////                        'createdBy' => 5,
//////                        'contractorId' => 2153,
////                        'responsibleId' => '1',
////                        'currency' => 'EUR',
////                        'status' => 'Y',
////                        'total' => $sum[$id],
////                        ]]);
//                    $docId += 2;
//                    $sum[$id] = 0;
//                    $count = 0 ;
//                }
//                $item['docId'] = $docId;
//                $item['storeTo'] = 1;
//                $commands[] = $webhook->buildCommand('catalog.document.element.add', ['fields' => $item]);
//                $count++;
//            }
//        }
//        $commandChunks = array_chunk($commands, 50, true);
////        dd($commandChunks);
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $webhook->batchRequest($command);
//            } catch (\Exception $exception) {
//                dump('Ошибочка - ' . "$key");
//            }
//            sleep(2);
//        }
//        dd($commands);
//        //file_put_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/sum.json', json_encode($sum));


//        dump($sum);
//        dump($commands);
//        dump($documents[51]);
//        $commandChunks = array_chunk($commands, 50, true);
////        dd($commandChunks);
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $webhook->batchRequest($command);
//            } catch (\Exception $exception) {
//                dump('Ошибочка - ' . "$key");
//            }
//            sleep(2);
//        }
//        unset($documents);
//        unset($sum);
//        unset($commands);


        /** установка суммы */
//        $sum =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/sum.json'), true);
//        $commands = [];
//        foreach ($sum as $id => $item){
//            $commands[] = $webhook->buildCommand('catalog.document.update', ['fields' => ['total' => $item], 'id' => $id]);
//        }
//        $commandChunks = array_chunk($commands, 50, true);
//        foreach ($commandChunks as $key => $command)
//        {
//            try {
//                $webhook->batchRequest($command);
//            } catch (\Exception $exception) {
//                dump('Ошибочка - ' . "$key");
//            }
//            sleep(2);
//        }

        /** проведение */
//        $ids = [];
//        foreach ($sum as $id => $item) {
//            $ids[] = $id;
//        }
//        $webhook->request('catalog.document.conductList', ['documentIds' => $ids]);

        die;
        /** Импорт в дб */

//        $table =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInWork.json'), true);
//        $this->addRow($table);
//        unset($table);


//        $table =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
//        $this->addRow($table);
//        unset($table);


//        $table =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
//        $this->addRow($table);
//        unset($table);

//        $table =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_1.json'), true);
//        $this->addRow($table);
//        unset($table);

//        $table =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_2.json'), true);
//        $this->addRow($table);
//        unset($table);

//        $deals_table = new Deals();
//
        die;
    }

    public function addRow($table)
    {
        $deals_table = new Deals();
        $count = 0;
        foreach ($table as $items) {
            $deals_table->addRow($items);
            if ($count == 100) {
                sleep(3);
                $count = 0;
            }
            $count++;
        }
//        dump($table);
    }

    public function getWorkProduct($createProducts, $rawProducts)
    {
        $table =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInWork.json'), true);
        foreach ($rawProducts as $row => $products) {
            foreach ($products as $article => $product) {
                if (isset($table[$row][85])) {
                    $this->getContractor($table[$row][85], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][86])) {
                    $this->getContractor($table[$row][86], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][87])) {
                    $this->getContractor($table[$row][87], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][88])) {
                    $this->getContractor($table[$row][88], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][89])) {
                    $this->getContractor($table[$row][89], $product, $createProducts[$article]['element']['id']);
                }
            }
        }
        unset($table);
    }
    public function getSuccessProduct($createProducts, $rawProducts)
    {
        $table =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInSuccess_0.json'), true);
        foreach ($rawProducts as $row => $products) {
            foreach ($products as $article => $product) {
                if (isset($table[$row][85])) {
                    $this->getContractor($table[$row][85], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][86])) {
                    $this->getContractor($table[$row][86], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][87])) {
                    $this->getContractor($table[$row][87], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][88])) {
                    $this->getContractor($table[$row][88], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][89])) {
                    $this->getContractor($table[$row][89], $product, $createProducts[$article]['element']['id']);
                }
            }
        }
        unset($table);
    }
    public function getLoseProduct0($createProducts, $rawProducts)
    {
        $table =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_0.json'), true);
        foreach ($rawProducts as $row => $products) {
            foreach ($products as $article => $product) {
                if (isset($table[$row][85])) {
                    $this->getContractor($table[$row][85], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][86])) {
                    $this->getContractor($table[$row][86], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][87])) {
                    $this->getContractor($table[$row][87], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][88])) {
                    $this->getContractor($table[$row][88], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][89])) {
                    $this->getContractor($table[$row][89], $product, $createProducts[$article]['element']['id']);
                }
            }
        }
        unset($table);
    }
    public function getLoseProduct1($createProducts, $rawProducts)
    {
        $table =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_1.json'), true);
        foreach ($rawProducts as $row => $products) {
            foreach ($products as $article => $product) {
                if (isset($table[$row][85])) {
                    $this->getContractor($table[$row][85], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][86])) {
                    $this->getContractor($table[$row][86], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][87])) {
                    $this->getContractor($table[$row][87], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][88])) {
                    $this->getContractor($table[$row][88], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][89])) {
                    $this->getContractor($table[$row][89], $product, $createProducts[$article]['element']['id']);
                }
            }
        }
        unset($table);
    }
    public function getLoseProduct2($createProducts, $rawProducts)
    {
        $table =  json_decode(file_get_contents(Yii::getAlias('@app') . '/modules/processor_post/temp/tableDealInLose_2.json'), true);
        foreach ($rawProducts as $row => $products) {
            foreach ($products as $article => $product) {
                if (isset($table[$row][85])) {
                    $this->getContractor($table[$row][85], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][86])) {
                    $this->getContractor($table[$row][86], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][87])) {
                    $this->getContractor($table[$row][87], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][88])) {
                    $this->getContractor($table[$row][88], $product, $createProducts[$article]['element']['id']);
                }
                if (isset($table[$row][89])) {
                    $this->getContractor($table[$row][89], $product, $createProducts[$article]['element']['id']);
                }
            }
        }
        unset($table);
    }

    public function getContractor($row, $product, $id)
    {
        if (mb_strpos($row, $product['article']) !== false) {
            if (preg_match('~Daisy~', $row)) {
                $this->documents[25][] = [
                    'docId' => 25,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }
            if (preg_match('~Джастин~', $row)) {
                $this->documents[27][] = [
                    'docId' => 27,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }
            if (preg_match('~Yana Wu~', $row)) {
                $this->documents[29][] = [
                    'docId' => 29,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }
            if (preg_match('~YBD Max Rita~', $row)) {
                $this->documents[31][] = [
                    'docId' => 31,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }
            if (preg_match('~Айви~', $row)) {
                $this->documents[33][] = [
                    'docId' => 33,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Александр~', $row) || preg_match('~Литва~', $row)) {
                $this->documents[35][] = [
                    'docId' => 35,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Александр~', $row)) {
                $this->documents[37][] = [
                    'docId' => 37,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Андрей~', $row)) {
                $this->documents[39][] = [
                    'docId' => 39,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Андрей~', $row)) {
                $this->documents[41][] = [
                    'docId' => 41,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Андрей~', $row)) {
                $this->documents[43][] = [
                    'docId' => 43,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Вадим~', $row)) {
                $this->documents[45][] = [
                    'docId' => 45,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Владимир~', $row)) {
                $this->documents[47][] = [
                    'docId' => 47,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Гровема~', $row)) {
                $this->documents[49][] = [
                    'docId' => 49,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Дмитрий~', $row)) {
                $this->documents[51][] = [
                    'docId' => 51,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Константин~', $row)) {
                $this->documents[53][] = [
                    'docId' => 53,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Лидия~', $row)) {
                $this->documents[55][] = [
                    'docId' => 55,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Никита~', $row)) {
                $this->documents[57][] = [
                    'docId' => 57,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Никки~', $row)) {
                $this->documents[59][] = [
                    'docId' => 59,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Октай~', $row)) {
                $this->documents[61][] = [
                    'docId' => 61,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Сергей~', $row)) {
                $this->documents[63][] = [
                    'docId' => 63,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Спецопотпоставка~', $row)) {
                $this->documents[65][] = [
                    'docId' => 65,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Эрхан~', $row)) {
                $this->documents[67][] = [
                    'docId' => 67,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~Юрий герм~', $row)) {
                $this->documents[69][] = [
                    'docId' => 69,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            } elseif (preg_match('~Юрий~', $row)) {
                $this->documents[71][] = [
                    'docId' => 71,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }

            if (preg_match('~YEM~', $row)) {
                $this->documents[73][] = [
                    'docId' => 73,
                    'amount' => 1,
                    'purchasingPrice' => $product['price'] ?? 0,
                    'elementId' => $id,
                ];
            }
            //25
        }

    }


    public function deleteEmptyCol($table) {
        foreach ($table[1] as $item) {
            $hasItem[] = false;
        }

        foreach ($table as $key => $items) {
            if ($key == 1) {
                continue;
            }
            foreach ($items as $key => $item) {
                if (trim($item) != null) {
                    $hasItem[$key] = true;
                }
            }
        }

        foreach ($hasItem as $key => $item) {
            if (!$item) {
                foreach ($table as $key1 => $values) {
                    foreach ($values as $key2 => $value) {
                        unset($table[$key1][$key]);
                    }
                }
            }
        }
        return $table;
    }

}
