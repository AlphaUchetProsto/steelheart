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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&subset=cyrillic&display=swap">
    <?php $this->head() ?>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: auto;
            background: transparent !important;
            font-family: "OpenSans", "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 20px;
            color: #333;
            -webkit-font-smoothing: antialiased;
            overflow: hidden;
        }
        .contract-field {
            width: 100%;
            box-sizing: border-box;
            background: transparent;
        }
        .contract-field--view {
            min-height: 20px;
            display: flex;
            align-items: center;
        }
        .contract-field--edit {
            min-height: 36px;
        }
        .contract-field__error {
            color: #ff5752;
            font-size: 13px;
            line-height: 18px;
        }
        .contract-field__empty {
            color: #a8adb4;
            font-size: 14px;
            line-height: 20px;
            font-weight: 400;
        }
        /* Как ссылка на сущность CRM в карточке Битрикс */
        .contract-field__link {
            display: inline;
            margin: 0;
            padding: 0;
            border: 0;
            background: none;
            color: #2067b0;
            font: inherit;
            font-size: 14px;
            line-height: 20px;
            font-weight: 400;
            text-align: left;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            cursor: pointer;
            word-break: break-word;
        }
        .contract-field__link:hover {
            color: #2067b0;
            border-bottom-color: #2067b0;
        }
        .contract-field__control {
            width: 100%;
        }
        .contract-field__select {
            -webkit-appearance: none;
            appearance: none;
            display: block;
            width: 100%;
            height: 36px;
            box-sizing: border-box;
            margin: 0;
            padding: 0 30px 0 11px;
            border: 1px solid #c6cdd3;
            border-radius: 2px;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23525c69' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            color: #333;
            font-family: inherit;
            font-size: 14px;
            line-height: 36px;
            outline: none;
            cursor: pointer;
            transition: border-color .15s ease;
        }
        .contract-field__select:hover {
            border-color: #66afe9;
        }
        .contract-field__select:focus {
            border-color: #2fc6f6;
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
