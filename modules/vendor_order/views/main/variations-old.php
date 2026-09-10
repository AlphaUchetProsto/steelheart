<tr>
    <td>
        <?php if ($item->id > 0) : ?>
            <i class="fa-regular fa-square-minus delete-variation" data-bs-toggle="modal"  data-bs-target="#confirm-delete-variation"></i>
        <?php else: ?>
            <i class="fa-regular fa-square-plus"></i>
        <?php endif; ?>

        <!--                <i class="fa-solid fa-trash delete-variation"  data-bs-toggle="modal"  data-bs-target="#confirm-delete-variation"></i>-->
    </td>
    <td>
        <?= \yii\bootstrap5\Html::checkbox('isExport', false, ['class' => 'form-check-input']) ?>
        <?= \yii\bootstrap5\Html::hiddenInput('id', $item->id); ?>
        <?= \yii\bootstrap5\Html::hiddenInput('productRowId', $item->productRowId); ?>
    </td>
    <td style="display: none">
        <?= \yii\bootstrap5\Html::textInput('productName', $productName, ['class' => 'form-control']); ?>
    </td>
    <td>
        <?= \yii\bootstrap5\Html::textInput('supplier', $item->supplier, ['class' => 'form-control supplier_input', 'data-itemId' => $item->id]); ?>
    </td>
    <td>
        <?= \yii\bootstrap5\Html::dropDownList('brand', $item->brand, $brand['collections'], ['class' => 'form-select bg-white', 'prompt' => $item->brand > 0 && in_array($item->brand, $brand['collections']) ? $brand['collections'][$item->brand] : 'Выбрать']) ?>
    </td>
    <td style="width: 10rem;">
        <?= \yii\bootstrap5\Html::dropDownList('typeDetailId', $item->typeDetailId, $item->getCollectionTypeDetail(), ['class' => 'form-select', 'prompt' => 'Выбрать']); ?>
    </td>
    <td>
        <?php if ($item->id > 0) : ?>
            <?= \yii\bootstrap5\Html::textInput('weight', $item->weight, ['class' => 'form-control', 'disabled' => 'disabled']); ?>
        <?php else: ?>
            <?= \yii\bootstrap5\Html::textInput('weight', $item->weight, ['class' => 'form-control']); ?>
        <?php endif; ?>
    </td>
    <td>
        <?= \yii\bootstrap5\Html::textInput('deliveryTime', $item->deliveryTime, ['class' => 'form-control']); ?>
    </td>
    <td>
        <?= \yii\bootstrap5\Html::textInput('quantity', $item->quantity, ['class' => 'form-control', 'type' => 'number']); ?>
    </td>
    <td>
        <div class="price-field">

            <?php if ($item->id > 0) : ?>
                <?= \yii\bootstrap5\Html::textInput('price', $item->price, ['class' => 'form-control', 'disabled' => 'disabled']); ?>
            <?php else: ?>
                <?= \yii\bootstrap5\Html::textInput('price', $item->price, ['class' => 'form-control']); ?>
            <?php endif; ?>
        </div>
    </td>
    <td style="width: 10rem;">
        <?php if ($item->id > 0) : ?>
            <?= \yii\bootstrap5\Html::dropDownList('currency', $item->currency, $item->getCurrency(), ['class' => 'form-select', 'prompt' => 'Выбрать', 'disabled' => 'disabled']); ?>
        <?php else: ?>
            <?= \yii\bootstrap5\Html::dropDownList('currency', $item->currency, $item->getCurrency(), ['class' => 'form-select', 'prompt' => 'Выбрать']); ?>
        <?php endif; ?>
    </td>
    <td style="display: none">
        <?= $item->status ?>
        <?= \yii\bootstrap5\Html::hiddenInput('status', $item->status); ?>
    </td>
    <td>
        <?= $item->source ?>
        <?= \yii\bootstrap5\Html::hiddenInput('source', $item->source); ?>
    </td>
</tr>