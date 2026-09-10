<tr>
    <td>
        <?php if ($item->id > 0) : ?>
            <i class="fa-regular fa-square-minus"></i>
        <?php else: ?>
            <i class="fa-regular fa-square-plus"></i>
        <?php endif; ?>
    </td>
    <td <?= $item->id > 0 ? '' : 'class="td-hidden"'?>>
        <?= \yii\bootstrap5\Html::textInput('supplier',  $item->supplier, ['class' => 'form-control supplier_input']) ?>
        <?= \yii\bootstrap5\Html::hiddenInput('productName',  $item->productName, ['class' => 'form-control']) ?>
        <?= \yii\bootstrap5\Html::hiddenInput('id',  $item->id, ['class' => 'form-control']) ?>
        <?= \yii\bootstrap5\Html::hiddenInput('productRowId',  $item->productRowId, ['class' => 'form-control']) ?>
        <?= \yii\bootstrap5\Html::hiddenInput('productId',  $item->productId, ['class' => 'form-control']) ?>
        <?= \yii\bootstrap5\Html::hiddenInput('variationId',  $item->variationId, ['class' => 'form-control']) ?>
    </td>
    <td <?= $item->id > 0 ? '' : 'class="td-hidden"'?>><?= \yii\bootstrap5\Html::dropDownList('brand', $item->brand, $brand['collections'], ['class' => 'form-select bg-white', 'prompt' => $item->brand > 0 && in_array($item->brand, $brand['collections']) ? $brand['collections'][$item->brand] : 'Выбрать']) ?>
    </td>
    <?php // \yii\bootstrap5\Html::textInput('brand',  $item->brand, ['class' => 'form-control']) ?>
    <td <?= $item->id > 0 ? '' : 'class="td-hidden"'?> style="width: 10rem;"><?= \yii\bootstrap5\Html::dropDownList('typeDetailId', $item->typeDetailId, $item->getCollectionTypeDetail(), ['class' => 'form-select', 'prompt' => 'Выбрать']); ?></td>
    <td <?= $item->id > 0 ? '' : 'class="td-hidden"'?>><?= \yii\bootstrap5\Html::textInput('weight',  $item->weight, ['class' => 'form-control']) ?></td>
    <td <?= $item->id > 0 ? '' : 'class="td-hidden"'?>><?= \yii\bootstrap5\Html::textInput('deliveryTime',  $item->deliveryTime, ['class' => 'form-control']) ?></td>
    <td <?= $item->id > 0 ? '' : 'class="td-hidden"'?>><?= \yii\bootstrap5\Html::textInput('source',  $item->source, ['class' => 'form-control']) ?></td>
    <td <?= $item->id > 0 ? '' : 'class="td-hidden"'?>>
        <?= \yii\bootstrap5\Html::textInput('quantity', $item->quantity, ['class' => 'form-control', 'type' => 'number']); ?>
    </td>
    <td <?= $item->id > 0 ? '' : 'class="td-hidden"'?> style="width: 10rem;"><?= \yii\bootstrap5\Html::textInput('price',  $item->price, ['class' => 'form-control']) ?></td>
    <td <?= $item->id > 0 ? '' : 'class="td-hidden"'?> style="width: 10rem;"><?= \yii\bootstrap5\Html::dropDownList('currency', $item->currency, $item->getCurrency(), ['class' => 'form-select', 'prompt' => 'Выбрать']); ?></td>
</tr>