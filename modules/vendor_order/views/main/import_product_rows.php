<?php if(!empty($productRows)) : ?>
    <?php foreach ($productRows as $row) : ?>
        <?= $this->render('table_row', ['row' => $row]); ?>
    <?php endforeach; ?>
<?php endif; ?>