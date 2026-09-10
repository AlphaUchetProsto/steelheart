<tr class="main-row">
    <td class="text-center" style="width: 60px;">
        <div class="row row-bar">
            <div class="col-auto">
                <div class="open-menu">
                    <span></span>
                    <span></span>
                    <span></span>
                    <div class="menu shadow shadow-sm mb-5 bg-white rounded">
                        <button class="d-flex align-items-center delete-row"><i class="fa-solid fa-trash"></i>Удалить</button>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <i class="fa-solid fa-chevron-up open-subtable"></i>
            </div>
        </div>
        <?= \yii\bootstrap5\Html::textInput('id', $row->id, ['type' => 'hidden']) ?>
        <?= \yii\bootstrap5\Html::textInput('ssuppliers', $row->ssuppliers, ['type' => 'hidden']) ?>
    </td>
    <td>
        <div class="row g-0">
            <div class="col">
                <?= \yii\bootstrap5\Html::textInput('name', $row->getProduct()->name, ['class' => 'form-control bg-white', 'disabled' => true]) ?>
            </div>
            <?= \yii\bootstrap5\Html::textInput('productId', $row->getProduct()->id, ['type' => 'hidden']) ?>
        </div>
    </td>
    <td>
        <?= \yii\bootstrap5\Html::textInput('article', $row->getProduct()->article, ['class' => 'form-control bg-white', 'disabled' => true]) ?>
    </td>
    <td style="display: none"></td>
    <td style="display: none">
    </td>
    <td style="display: none">
        <?= \yii\bootstrap5\Html::dropDownList('brand', $row->brand, $row->getCollectionBrand(), ['class' => 'form-select bg-white', 'prompt' => 'Выбрать']) ?>
    </td>
    <td>
        <?= \yii\bootstrap5\Html::dropDownList('typeMachine', $row->typeMachine, $row->getCollectionTypeMachine(), ['class' => 'form-select bg-white', 'prompt' => 'Выбрать']) ?>
    </td>
    <td style="display: none"></td>
    <td style="display: none" class="text-center">
        <?= \yii\bootstrap5\Html::checkbox('request', $row->request, ['class' => 'form-check-input']) ?>
    </td>
    <td>
        <?= \yii\bootstrap5\Html::dropDownList('status', $row->status, $row->getCollectionStatus(), ['class' => 'form-select bg-white']) ?>
    </td>
</tr>
<tr class="d-none additional-row bitrix-suppliers">
    <td style="background-color: #eef2f4;"></td>
    <td colspan="9">
        <table class="table align-middle w-100 fs-10 benchmarking-variations" data-id="<?= $row->id ?>">
            <thead>
            <tr>
                <th></th>
                <th>Поставщик</th>
                <th>Бренд</th>
                <th>Тип детали</th>
                <th>Вес</th>
                <th>Срок поставки</th>
                <th>Источник</th>
                <th>Количество</th>
                <th>Цена</th>
                <th>Валюта</th>
            </tr>
            </thead>
            <tbody class="table-border-bottom-0">
            <?php if(!empty($row->getVariations())) : ?>
                <?php foreach ($row->getVariations() as $item) : ?>
                    <?= $this->render('variations', ['item' => $item, 'brand' => ['object' => $row->brand, 'collections'=>$row->getCollectionBrand()]]); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center empty-row">Не найден у поставщиков</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </td>
</tr>