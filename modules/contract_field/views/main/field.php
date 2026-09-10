<?php

/** @var \app\modules\contract_field\models\Bitrix\Userfield\Dto\ContractFieldStateDto $state */

use app\modules\contract_field\Module;
use yii\helpers\Html;

$isEdit = ($state->placement->mode ?? 'view') === 'edit';
$selectedId = $state->selectedContract->id ?? null;
$selectedTitle = null;
$contractEntityTypeId = Module::CONTRACT_ENTITY_TYPE_ID;

if ($state->selectedContract) {
    $selectedTitle = $state->selectedContract->title !== ''
        ? $state->selectedContract->title
        : ('Договор #' . $state->selectedContract->id);
}

$frameHeight = $isEdit ? 36 : 20;
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
        <?php if ($selectedId && $selectedTitle !== null): ?>
            <button
                type="button"
                class="contract-field__link"
                id="contract-field-link"
                data-entity-type-id="<?= (int)$contractEntityTypeId ?>"
                data-id="<?= (int)$selectedId ?>"
            ><?= Html::encode($selectedTitle) ?></button>
        <?php else: ?>
            <div class="contract-field__empty">не заполнено</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="//api.bitrix24.com/api/v1/"></script>
<script>
    BX24.init(function () {
        var defaultHeight = <?= (int)$frameHeight ?>;
        var contractEntityTypeId = <?= (int)$contractEntityTypeId ?>;

        function resizeFrame() {
            var root = document.getElementById('contract-field-root');
            var height = defaultHeight;

            if (root) {
                var measured = Math.ceil(root.getBoundingClientRect().height);
                height = Math.max(measured, defaultHeight);
            }

            BX24.resizeWindow('100%', height);
        }

        resizeFrame();
        setTimeout(resizeFrame, 30);
        setTimeout(resizeFrame, 120);

        var link = document.getElementById('contract-field-link');
        if (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                var id = link.getAttribute('data-id');
                var entityTypeId = link.getAttribute('data-entity-type-id') || contractEntityTypeId;

                if (!id) {
                    return;
                }

                BX24.openPath('/crm/type/' + entityTypeId + '/details/' + id + '/');
            });
        }

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
            // Пустые '' / null Битрикс часто игнорирует (нет «изменения» в модели).
            // Сначала пишем временное значение, затем очищаем через false и ''.
            if (value === '' || value === null || value === undefined) {
                BX24.placement.call('setValue', '0');
                BX24.placement.call('setValue', false);
                BX24.placement.call('setValue', '');
                return;
            }

            BX24.placement.call('setValue', String(value));
        }

        select.addEventListener('change', function () {
            applyEmptyClass();
            setFieldValue(select.value);
            resizeFrame();
        });

        applyEmptyClass();
    });
</script>
