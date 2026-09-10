<?php

/** @var \app\modules\contract_field\models\Bitrix\Userfield\Dto\ContractFieldStateDto $state */

use yii\helpers\Html;

$isEdit = ($state->placement->mode ?? 'view') === 'edit';
$selectedId = $state->selectedContract->id ?? null;
$selectedTitle = null;

if ($state->selectedContract) {
    $selectedTitle = $state->selectedContract->title !== ''
        ? $state->selectedContract->title
        : ('Договор #' . $state->selectedContract->id);
}

$frameHeight = $isEdit ? 36 : 18;
$modeClass = $isEdit ? 'contract-field--edit' : 'contract-field--view';
?>
<div class="contract-field <?= $modeClass ?>" id="contract-field-root">
    <?php if ($state->error): ?>
        <div class="contract-field__error"><?= Html::encode($state->error) ?></div>
    <?php elseif ($isEdit): ?>
        <?php if (empty($state->contracts)): ?>
            <div class="contract-field__empty">Нет договоров, привязанных к компании</div>
        <?php else: ?>
            <div class="contract-field__control">
                <select
                    id="contract-field-select"
                    class="contract-field__select<?= $selectedId ? '' : ' is-empty' ?>"
                >
                    <option value="">не выбрано</option>
                    <?php foreach ($state->contracts as $contract): ?>
                        <?php
                        $title = $contract->title !== '' ? $contract->title : ('Договор #' . $contract->id);
                        ?>
                        <option
                            value="<?= (int)$contract->id ?>"
                            <?= $selectedId === $contract->id ? 'selected' : '' ?>
                        >
                            <?= Html::encode($title) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($selectedTitle !== null): ?>
            <div class="contract-field__value"><?= Html::encode($selectedTitle) ?></div>
        <?php else: ?>
            <div class="contract-field__empty">не заполнено</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="//api.bitrix24.com/api/v1/"></script>
<script>
    BX24.init(function () {
        var defaultHeight = <?= (int)$frameHeight ?>;

        function resizeFrame() {
            var root = document.getElementById('contract-field-root');
            var height = defaultHeight;

            if (root) {
                height = Math.max(root.offsetHeight, defaultHeight);
            }

            BX24.resizeWindow('100%', height);
        }

        resizeFrame();
        setTimeout(resizeFrame, 50);
        setTimeout(function () {
            if (typeof BX24.fitWindow === 'function') {
                BX24.fitWindow();
            }
            resizeFrame();
        }, 150);

        var select = document.getElementById('contract-field-select');
        if (!select) {
            return;
        }

        function applyEmptyClass() {
            if (select.value) {
                select.classList.remove('is-empty');
            } else {
                select.classList.add('is-empty');
            }
        }

        function setFieldValue(value) {
            var payload = value ? String(value) : null;
            BX24.placement.call('setValue', payload);
        }

        select.addEventListener('change', function () {
            applyEmptyClass();
            setFieldValue(select.value);
            resizeFrame();
        });

        applyEmptyClass();
    });
</script>
