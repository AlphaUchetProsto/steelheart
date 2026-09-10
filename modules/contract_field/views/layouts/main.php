<?php

/** @var yii\web\View $this */
/** @var string $content */

use yii\bootstrap5\Html;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <style>
        body {
            margin: 0;
            padding: 8px 10px;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #333;
            background: transparent;
        }
        .contract-field__error {
            color: #d9534f;
        }
        .contract-field__empty {
            color: #80868e;
        }
        .contract-field select {
            width: 100%;
            box-sizing: border-box;
            height: 34px;
            border: 1px solid #c6cdd3;
            border-radius: 2px;
            padding: 0 8px;
            background: #fff;
        }
        .contract-field__value {
            line-height: 20px;
            word-break: break-word;
        }
    </style>
</head>
<body>
<?php $this->beginBody() ?>
<?= $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
