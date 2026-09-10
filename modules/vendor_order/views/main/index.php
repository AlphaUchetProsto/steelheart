<?php if ($dealId) : ?>
<?= \yii\bootstrap5\Html::textInput('dealId', $dealId, ['type' => 'hidden', 'class' => 'deal-input']); ?>

<div class="card">
    <div class="row g-0">
        <div class="col">
            <div class="toolbar">
                <?= \yii\bootstrap5\Html::button('Добавить товар', ['class' => 'btn btn-secondary add-product']); ?>
                <?= \yii\bootstrap5\Html::button('Импорт', ['class' => 'btn btn-outline-dark', 'data-bs-toggle' => 'modal',  'data-bs-target' => '#popup-import-supplier']); ?>
                <?= \yii\bootstrap5\Html::button('КП', ['class' => 'btn btn-outline-dark btn-create-document']); ?>
                <?= \yii\bootstrap5\Html::button('В проценку', ['class' => 'btn btn-outline-dark move-to-benchmarking']); ?>
            </div>
        </div>
        <div class="col d-flex justify-content-end">
            <div class="toolbar">
                <?= \yii\bootstrap5\Html::button('Экспорт', ['class' => 'btn btn-secondary export']); ?>
                <?= \yii\bootstrap5\Html::button('Поиск', ['class' => 'btn btn-outline-dark search-supplier']); ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="popup-select-product" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Выберите раздел</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="row content-popup-product h-100">
                    <div class="col-5 tree-popup-product h-100"></div>
                    <div class="col-7 wp-item-popup-product h-100"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirm-delete-variation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Вы уверены, что хотите удалить?</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Нет</button>
                <button type="button" class="btn btn-primary">Да</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="popup-import-supplier" tabindex="-1" aria-hidden="true">
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

<div class="modal fade" id="popup-create-document" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">КП</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid g-0 header_create-document-form mb-2">
                    <div class="row">
                        <div class="col d-flex align-items-center">
                        </div>
                        <div class="col-auto text-center" style="width: 150px;">
                            Закупочная цена
                        </div>
                        <div class="col-auto text-center" style="width: 150px;">
                            Цена в рублях
                        </div>
                    </div>
                </div>
                <div class="container-fluid g-0 create-document-form">

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary">Сохранить</button>
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
                <th class="w-25">Артикул</th>
                <th style="display: none">Срок поставки</th>
                <th style="display: none">Тип детали</th>
                <th style="display: none; width: 120px;">Количество</th>
                <th style="display: none">Закупочная цена</th>
                <th style="display: none">Цена продажи</th>
                <th style="display: none">Бренд</th>
                <th style="display: none">Тип техники</th>
                <th></th>
            </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                <?php if(!empty($productRows)) : ?>
                     <?php foreach ($productRows as $row) : ?>
                        <?= $this->render('table_row', ['row' => $row, 'typesDetails' => $typesDetails]); ?>
                     <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bottom-menu">
    <button class="save grey bitrix-success me-3 save-product">
        Сохранить
    </button>
    <button class="btn btn-outline-dark" onclick="window.location.reload()">
        Обновить
    </button>
</div>
<script>
    window.addEventListener('load', function () {
        initSearchArticleBtn();
    })
</script>

<style>

    .products-table thead tr th:first-child{
        width: 90px !important;
        padding-right: 0 !important;
    }

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

    .create-document-form > div:last-child{
        margin-bottom: 0 !important;
        border-bottom: none !important;
    }

    .create-document-form > div > div {
        padding: 10px;
    }

    .create-document-form > div > div {
        border-right: 1px solid #d5d5d5;
    }

    .create-document-form > div > div:last-child {
        border-right: none !important;
    }

    .create-document-form > div  {
        border-bottom: 1px solid #d5d5d5;
    }

    .wp-money_input {
        position: relative;
    }

    .wp-money_input input {
        padding-right: 40px; /* <- с запасом под .currency */
    }

    .currency {
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
        pointer-events: none;
        color: #666;
    }

    input, select, button {
        border-radius: 3px !important;
    }
    .td-hidden{
        display: none;
    }
</style>

<?php endif; ?>
<?php $this->registerJsFile('@web/js/vendor_order/search_product_poppup.js', ['depends' => [yii\web\JqueryAsset::className()]]); ?>
