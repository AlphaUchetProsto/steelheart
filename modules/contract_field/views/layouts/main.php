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
            background: #f6fafb !important;
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
            background: #f6fafb;
        }
        .contract-field--view {
            min-height: 20px;
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
        .contract-field__view-row {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 20px;
        }
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
            border-bottom-color: #2067b0;
        }
        .contract-field__edit-trigger {
            flex: 0 0 auto;
            margin: 0;
            padding: 0;
            border: 0;
            background: none;
            color: #2067b0;
            font: inherit;
            font-size: 13px;
            line-height: 20px;
            cursor: pointer;
            text-decoration: none;
            border-bottom: 1px dashed #2067b0;
            white-space: nowrap;
        }
        .contract-field__edit-trigger:hover {
            border-bottom-style: solid;
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
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23a8adb4' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
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
        .contract-field__actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        .contract-field__btn {
            height: 28px;
            padding: 0 12px;
            border-radius: 2px;
            border: 1px solid transparent;
            font: inherit;
            font-size: 13px;
            line-height: 26px;
            cursor: pointer;
        }
        .contract-field__btn:disabled {
            opacity: .6;
            cursor: default;
        }
        .contract-field__btn--save {
            background: #3bc8f5;
            border-color: #3bc8f5;
            color: #fff;
        }
        .contract-field__btn--save:hover:not(:disabled) {
            background: #3eddff;
        }
        .contract-field__btn--cancel {
            background: #fff;
            border-color: #c6cdd3;
            color: #525c69;
        }
        .contract-field__btn--cancel:hover:not(:disabled) {
            border-color: #a8adb4;
        }
        .contract-field__inline-error {
            margin-top: 6px;
            color: #ff5752;
            font-size: 12px;
            line-height: 16px;
        }
        .is-hidden {
            display: none !important;
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
