<div id="itemsList" class="wrapper-popup-content-row h-100">
    <div class="wpc-row-content">
        <?php if(!empty($suppliers)) : ?>
            <?php foreach ($suppliers as $supplier) : ?>
                <div class="wpc-row-content-item-supplier">
                    <div class="wpc-row-content-item-content">
                        <h4><?= $supplier->name; ?></h4>
                    </div>
                    <?= \yii\bootstrap5\Html::hiddenInput('supplierId', $supplier->id); ?>
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