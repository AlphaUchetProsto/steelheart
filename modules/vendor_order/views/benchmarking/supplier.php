<?php if(!empty($rows)) : ?>
    <?php foreach ($rows as $item) : ?>
        <tr>
            <td><?= $item->productName ?></td>
            <td><?= $item->supplier ?></td>
            <td><?= $item->manufacturer ?></td>
            <td><?= $item->deliveryTime ?></td>
            <td><?= $item->source ?></td>
            <td><?= $item->price ?> руб.</td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="6" class="text-center">Не найден у поставщиков</td>
    </tr>
<?php endif; ?>