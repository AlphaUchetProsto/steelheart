<table class="table align-middle w-75 fs-10">
    <thead>
        <tr>
            <th></th>
            <th>Название</th>
        </tr>
    </thead>
    <tbody class="table-border-bottom-0">
    <?php if(!empty($suppliers)) : ?>
        <?php foreach ($suppliers as $item) : ?>
        <tr>
            <td>
                <?= \yii\bootstrap5\Html::checkbox('is-select', $model->isSelectedSupplier($item->getFieldValue('id')), ['class' => 'form-check-input select-supplier']) ?>
                <?= \yii\bootstrap5\Html::textInput('id', $item->getFieldValue('id'), ['type' => 'hidden']) ?>
            </td>
            <td><?= $item->getFieldValue('title') ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="2">Не найден у поставщиков</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>