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
            position: relative;
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
            position: relative;
        }
        .ui-select {
            position: relative;
            width: 100%;
        }
        .ui-select__value {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            height: 36px;
            box-sizing: border-box;
            margin: 0;
            padding: 0 10px 0 11px;
            border: 1px solid #c6cdd3;
            border-radius: 2px;
            background: #fff;
            color: #333;
            font: inherit;
            font-size: 14px;
            line-height: 20px;
            text-align: left;
            cursor: pointer;
            outline: none;
            transition: border-color .15s ease;
        }
        .ui-select__value:hover {
            border-color: #66afe9;
        }
        .ui-select.is-open .ui-select__value,
        .ui-select__value:focus {
            border-color: #2fc6f6;
        }
        .ui-select.is-empty .ui-select__text {
            color: #a8adb4;
        }
        .ui-select__text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-right: 8px;
        }
        .ui-select__arrow {
            flex: 0 0 auto;
            width: 10px;
            height: 6px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23a8adb4' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E") center no-repeat;
        }
        .ui-select.is-open .ui-select__arrow {
            transform: rotate(180deg);
        }
        .ui-select__dropdown {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 4px);
            z-index: 20;
            max-height: 220px;
            overflow-y: auto;
            margin: 0;
            padding: 6px 0;
            list-style: none;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .12), 0 1px 3px rgba(0, 0, 0, .06);
        }
        .ui-select.is-open .ui-select__dropdown {
            display: block;
        }
        .ui-select__option {
            display: block;
            width: 100%;
            box-sizing: border-box;
            margin: 0;
            padding: 8px 14px;
            border: 0;
            background: transparent;
            color: #333;
            font: inherit;
            font-size: 14px;
            line-height: 20px;
            text-align: left;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ui-select__option:hover,
        .ui-select__option.is-active {
            background: #f0f2f5;
        }
        .ui-select__option.is-selected {
            font-weight: 600;
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
