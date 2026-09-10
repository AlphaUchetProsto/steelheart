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
        html, body {
            margin: 0;
            padding: 0;
            background: transparent;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 17px;
            color: #525c69;
            overflow: hidden;
        }
        .contract-field {
            min-height: 34px;
            display: flex;
            align-items: center;
        }
        .contract-field__error {
            color: #ff5752;
            font-size: 13px;
        }
        .contract-field__empty {
            color: #a8adb4;
        }
        .contract-field__value {
            color: #525c69;
            word-break: break-word;
        }
        .contract-field__control {
            position: relative;
            width: 100%;
        }
        .contract-field__select {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 34px;
            box-sizing: border-box;
            margin: 0;
            padding: 0 28px 0 9px;
            border: 1px solid #c6cdd3;
            border-radius: 2px;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23525c69' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E") no-repeat right 10px center;
            color: #525c69;
            font: inherit;
            outline: none;
            cursor: pointer;
            transition: border-color .2s;
        }
        .contract-field__select:hover {
            border-color: #66afe9;
        }
        .contract-field__select:focus {
            border-color: #2fc6f6;
            box-shadow: 0 0 0 1px #2fc6f6;
        }
        .contract-field__select.is-empty {
            color: #a8adb4;
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
