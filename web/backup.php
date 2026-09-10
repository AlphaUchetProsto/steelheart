<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '300');

$config = require __DIR__ . '/../config/web.php';

$application = new yii\web\Application($config);

$postData = Yii::$app->request->post();

if(Yii::$app->request->isPost){
    $server = new app\models\yandex\Utils();
    $server->createArchive($postData['nameFileBackup']);

    echo json_encode([
        'downloadLink' => "{$postData['domain']}{$postData['nameFileBackup']}",
    ], JSON_UNESCAPED_UNICODE);

    exit;
}
