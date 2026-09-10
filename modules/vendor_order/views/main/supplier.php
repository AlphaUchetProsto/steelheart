<table class="table align-middle w-75 fs-10">
    <thead>
        <tr>
            <th style="width: 50px;"></th>
            <th class="w-25">Название</th>
            <th>Поставщик</th>
            <th>Производитель</th>
            <th>Срок поставки</th>
            <th>Источник</th>
            <th>Цена</th>
            <th>Состояние</th>
        </tr>
    </thead>
    <tbody class="table-border-bottom-0">
    <?php if(!empty($collection)) : ?>
        <?php foreach ($collection as $item) : ?>
        <tr>
            <td><?= \yii\bootstrap5\Html::checkbox('isExport', false, ['class' => 'form-check-input']) ?></td>
            <td><?= $item->productName ?></td>
            <td><?= $item->supplier ?></td>
            <td><?= $item->manufacturer ?></td>
            <td><?= $item->deliveryTime ?></td>
            <td><?= $item->source ?></td>
            <td><?= $item->price ?> руб.</td>
            <td><?= $item->status ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7">Не найден у поставщиков</td>
        </tr>
    <?php endif; ?>

    <?php if(!empty($notesAmo)) : ?>
        <?php foreach ($notesAmo as $item) : ?>
        <tr>
            <td colspan="6"><?= $item->getFullNote(); ?></td>
            <td></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>