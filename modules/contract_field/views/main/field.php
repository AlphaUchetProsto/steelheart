<?php

/** @var \app\modules\contract_field\models\Bitrix\Userfield\Dto\ContractFieldStateDto $state */

use yii\helpers\Html;

$isEdit = ($state->placement->mode ?? 'view') === 'edit';
$selectedId = $state->selectedContract->id ?? null;
?>
<div class="contract-field">
    <?php if ($state->error): ?>
        <div class="contract-field__error"><?= Html::encode($state->error) ?></div>
    <?php elseif ($isEdit): ?>
        <?php if (empty($state->contracts)): ?>
            <div class="contract-field__empty">Нет договоров, привязанных к компании</div>
        <?php else: ?>
            <select id="contract-field-select">
                <option value="">— не выбран —</option>
                <?php foreach ($state->contracts as $contract): ?>
                    <option
                        value="<?= (int)$contract->id ?>"
                        <?= $selectedId === $contract->id ? 'selected' : '' ?>
                    >
                        <?= Html::encode($contract->title !== '' ? $contract->title : ('Договор #' . $contract->id)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    <?php else: ?>
        <div class="contract-field__value">
            <?php if ($state->selectedContract): ?>
                <?= Html::encode($state->selectedContract->title !== '' ? $state->selectedContract->title : ('Договор #' . $state->selectedContract->id)) ?>
            <?php else: ?>
                <span class="contract-field__empty">Не выбран</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="//api.bitrix24.com/api/v1/"></script>
<script>
    BX24.init(function () {
        var select = document.getElementById('contract-field-select');
        if (!select) {
            return;
        }

        select.addEventListener('change', function () {
            var value = select.value ? String(select.value) : '';
            BX24.placement.call('setValue', value);
        });
    });
</script>
