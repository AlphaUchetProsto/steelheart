<?= \yii\bootstrap5\Html::textInput('dealId', $dealId, ['type' => 'hidden', 'class' => 'deal-input']); ?>

<div class="card">
    <div class="row g-0">
        <div class="col">
            <div class="toolbar">
                <?= \yii\bootstrap5\Html::button('Импорт', ['class' => 'btn btn-outline-dark', 'data-bs-toggle' => 'modal',  'data-bs-target' => '#popup-import-benchmarking']); ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="popup-import-benchmarking" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Импорт</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <?php $form = \yii\bootstrap5\ActiveForm::begin([
                                                                    'id' => 'import-form',
                                                                    'enableAjaxValidation' => false,
                                                                    'enableClientValidation' => false,
                                                                ]); ?>
                <?= $form->field($importForm, 'dealId', ['options' => ['class' => 'mb-0']])->hiddenInput()->label(false) ?>
                <?= $form->field($importForm, 'file')->fileInput() ?>
                <?php $form::end(); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary">Продолжить</button>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 50px;">
    <div class="table-responsive">
        <table class="table products-table align-middle" style="min-width: max-content;">
            <thead>
            <tr>
                <th></th>
                <th class="w-25">Товар</th>
                <th>Артикул</th>
                <th style="display: none">Закупочная цена</th>
                <th style="display: none">Цена продажи</th>
                <th style="display: none">Бренд</th>
                <th>Тип техники</th>
                <th style="display: none">Поставщик</th>
                <th style="display: none">Запросить</th>
                <th>Статус</th>
            </tr>
            </thead>
            <tbody class="table-border-bottom-0">
            <?php if(!empty($productRows)) : ?>
                <?php foreach ($productRows as $row) : ?>
                    <?= $this->render('table_row', ['row' => $row]); ?>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bottom-menu">
        <button class="save grey bitrix-success me-3 save-benchmarking">
            Сохранить и завершить
        </button>
        <button class="btn btn-outline-dark" onclick="window.location.reload()">
            Обновить
        </button>
<!--    <button class="save grey bitrix-success me-3 save-benchmarking">-->
<!--        Сохранить-->
<!--    </button>-->
<!--    <button class="save grey btn btn-outline-dark me-3 complete-benchmarking">-->
<!--        Завершить-->
<!--    </button>-->
</div>

<style>
    .products-table tbody tr td:first-child{
        padding-right: 0 !important;
    }

    .row-bar > div:last-child i{
        transform: rotate(90deg) !important;
    }

    .row-bar > div:last-child i:hover, .btn-open-product, .open-menu:hover{
        cursor: pointer;
    }

    .open-menu{
        position: absolute;
    }
    
    .menu > .d-flex i{
        font-size: 12px;
        margin-right: 10px;
    }
    .td-hidden{
        display: none;
    }
</style>

<?php $this->registerJsFile('@web/js/vendor_order/benchmarking.js', ['depends' => [yii\web\JqueryAsset::className()]]); ?>
<?php $this->registerJsFile('@web/js/vendor_order/search_product_poppup.js', ['depends' => [yii\web\JqueryAsset::className()]]); ?>
