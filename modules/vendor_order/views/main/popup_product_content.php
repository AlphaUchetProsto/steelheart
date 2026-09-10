<div id="itemsList" class="wrapper-popup-content-row h-100">
    <div class="wpc-row-content">
        <?php if(!empty($products)) : ?>
            <?php foreach ($products as $product) : ?>
                <div class="wpc-row-content-item">
                    <div class="wpc-row-content-item-content">
                        <h4><?= $product->name; ?></h4>
                        <p><?= $product->article ?></p>
                    </div>
                    <?= \yii\bootstrap5\Html::hiddenInput('productId', $product->id); ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <span class="d-flex align-justify-center">Пусто.</span>
        <?php endif; ?>
    </div>
</div>
<script>
    $(document).ready(function() {
        function applyWidth() {
            if ($('#itemsList').length) {
                // console.log('change width');
                $('#itemsList').parent().parent().css('width', 'auto');
            } else {
                setTimeout(applyWidth, 100);
            }
        }
        applyWidth();
    });
</script>