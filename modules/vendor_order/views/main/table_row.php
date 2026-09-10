<tr class="main-row">
    <td class="text-center">
        <div class="row row-bar">
            <div class="col-auto">
                <div class="open-menu">
                    <span></span>
                    <span></span>
                    <span></span>
                    <div class="menu shadow shadow-sm mb-5 bg-white rounded">
                        <button class="d-flex align-items-center copy-row"><i class="fa-solid fa-pencil"></i> Копировать</button>
                        <button class="d-flex align-items-center delete-row"><i class="fa-solid fa-trash"></i>Удалить</button>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <i class="fa-solid fa-chevron-up open-subtable"></i>
            </div>
        </div>
        <?= \yii\bootstrap5\Html::textInput('id', $row->id, ['type' => 'hidden']) ?>
    </td>
    <td>
        <div class="row g-0">
            <div class="col">
                    <?= \yii\bootstrap5\Html::textInput('name', $row->getProductName(), ['class' => 'form-control bg-white product-input', 'autocomplete' => 'off']) ?>
            </div>
            <?= \yii\bootstrap5\Html::textInput('productId', $row->getProductId(), ['type' => 'hidden', 'autocomplete' => 'off']) ?>
        </div>
    </td>
    <td>
        <div class="position-relative">
            <?= \yii\bootstrap5\Html::textInput('article', $row->getProductArticle(), [
                'class' => 'form-control bg-white article-input pe-4',
                'autocomplete' => 'off'
            ]) ?>
            <button class="btn btn-link p-0 position-absolute search-article-btn" type="button" style="right: 8px; top: 50%; transform: translateY(-50%); display: none; transition: opacity 0.2s;">
                <i class="fas fa-search text-secondary"></i>
            </button>
        </div>
<!--        --><?php //= \yii\bootstrap5\Html::textInput('article', $row->getProductArticle(), ['class' => 'form-control bg-white article-input', 'autocomplete' => 'off']) ?>
    </td>
    <td style="display: none"></td>
    <td style="display: none">
        <?php //\yii\bootstrap5\Html::dropDownList('type', $row->type, $row->getCollectionTypePar(), ['class' => 'form-select bg-white', 'prompt' => 'Выбрать']) ?>
        <?= \yii\bootstrap5\Html::dropDownList('type', $row->type, $typesDetails, ['class' => 'form-select bg-white', 'prompt' => 'Выбрать']) ?>
    </td>
    <td style="display: none">
        <?= \yii\bootstrap5\Html::textInput('quantity', $row->quantity, ['type' => 'number', 'class' => 'form-control bg-white']); ?>
    </td>
    <td style="display: none"></td>
    <td style="display: none">
        <?= \yii\bootstrap5\Html::textInput('price', $row->price, ['type' => 'number', 'class' => 'form-control', 'disabled' => true]); ?>
    </td>
    <td style="display: none">
        <?= \yii\bootstrap5\Html::dropDownList('brand', $row->brand, $row->getCollectionBrand(), ['class' => 'form-select bg-white', 'prompt' => 'Выбрать']) ?>
    </td>
    <td style="display: none">
        <?= \yii\bootstrap5\Html::dropDownList('typeMachine', $row->typeMachine, $row->getCollectionTypeMachine(), ['class' => 'form-select bg-white', 'prompt' => 'Выбрать']) ?>
    </td>
</tr>
<tr class="d-none additional-row out-suppliers">
    <td></td>
    <td colspan="9">
        <table class="table align-middle w-100 fs-10 product-variations" style="table-layout: fixed;width: auto !important;" data-id="<?= $row->id ?>">
            <thead>
            <tr>
                <th style="width: 30px !important;"></th>
                <th style="width: 50px;"></th>
<!--                <th class="w-25">Название</th>-->
                <th>Поставщик</th>
<!--                <th>Производитель</th>-->
                <th>Бренд</th>
                <th>Тип детали</th>
                <th>Вес</th>
                <th>Срок поставки</th>
                <th>Количество</th>
                <th>Цена</th>
                <th>Валюта</th>
                <th style="display: none">Состояние</th>
                <th>Источник</th>
            </tr>
            </thead>
            <tbody class="table-border-bottom-0">
            <?php if(!empty($row->getVariations())) : ?>
                <?php foreach ($row->getVariations() as $item) : ?>
                    <?= $this->render('variations', ['item' => $item, 'productName' => $row->getProductName(), 'brand' => ['object' => $row->brand, 'collections'=>$row->getCollectionBrand()], 'typesDetails'=>$typesDetails]); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center empty-row">Не найден у поставщиков</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </td>
</tr>