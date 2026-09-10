<div class="card">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th class="w-75">Название</th>
                <th>Ед. измерения</th>
                <th>Цена</th>
            </tr>
        </thead>
        <tbody class="table-border-bottom-0">
        <?php if($products->isNotEmpty()) : ?>
            <?php foreach ($products as $product) : ?>
                <tr aria-label="<?= $product->getFieldValue('ID') ?>" class="product-item">
                    <td><?= $product->getFieldValue('NAME') ?></td>
                    <td><?= $product->getFieldValue('MEASURE') ?></td>
                    <td><?= $product->getFieldValue('PRICE') ?></td>
                </tr>
             <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" class="text-center">Нет данных</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>