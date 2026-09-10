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

$displayTitle = $selectedTitle ?: 'Не выбран';
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
                <div
                    class="ui-select<?= $selectedId ? '' : ' is-empty' ?>"
                    id="contract-field-select"
                    data-value="<?= $selectedId ? (int)$selectedId : '' ?>"
                >
                    <button type="button" class="ui-select__value" id="contract-field-toggle">
                        <span class="ui-select__text" id="contract-field-text"><?= Html::encode($displayTitle) ?></span>
                        <span class="ui-select__arrow" aria-hidden="true"></span>
                    </button>
                    <ul class="ui-select__dropdown" id="contract-field-dropdown">
                        <li>
                            <button
                                type="button"
                                class="ui-select__option<?= !$selectedId ? ' is-selected' : '' ?>"
                                data-value=""
                                data-title="Не выбран"
                            >Не выбран</button>
                        </li>
                        <?php foreach ($state->contracts as $contract): ?>
                            <?php
                            $title = $contract->title !== '' ? $contract->title : ('Договор #' . $contract->id);
                            $isSelected = $selectedId === $contract->id;
                            ?>
                            <li>
                                <button
                                    type="button"
                                    class="ui-select__option<?= $isSelected ? ' is-selected' : '' ?>"
                                    data-value="<?= (int)$contract->id ?>"
                                    data-title="<?= Html::encode($title) ?>"
                                ><?= Html::encode($title) ?></button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
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
        var closedHeight = <?= (int)$frameHeight ?>;
        var contractEntityTypeId = <?= (int)$contractEntityTypeId ?>;

        function resizeFrame(height) {
            BX24.resizeWindow('100%', Math.max(height || closedHeight, closedHeight));
        }

        function measureAndResize() {
            var root = document.getElementById('contract-field-root');
            var height = closedHeight;

            if (root) {
                height = Math.max(Math.ceil(root.getBoundingClientRect().height), closedHeight);
            }

            resizeFrame(height);
        }

        measureAndResize();
        setTimeout(measureAndResize, 30);
        setTimeout(measureAndResize, 120);

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

        var root = document.getElementById('contract-field-select');
        var toggle = document.getElementById('contract-field-toggle');
        var dropdown = document.getElementById('contract-field-dropdown');
        var textNode = document.getElementById('contract-field-text');

        if (!root || !toggle || !dropdown || !textNode) {
            return;
        }

        function setFieldValue(value) {
            if (value === '' || value === null || value === undefined) {
                BX24.placement.call('setValue', '0');
                BX24.placement.call('setValue', false);
                BX24.placement.call('setValue', '');
                return;
            }

            BX24.placement.call('setValue', String(value));
        }

        function setSelected(value, title) {
            root.setAttribute('data-value', value || '');
            textNode.textContent = title || 'Не выбран';

            if (value) {
                root.classList.remove('is-empty');
            } else {
                root.classList.add('is-empty');
            }

            dropdown.querySelectorAll('.ui-select__option').forEach(function (option) {
                var selected = option.getAttribute('data-value') === String(value || '');
                option.classList.toggle('is-selected', selected);
            });

            setFieldValue(value || '');
        }

        function openList() {
            root.classList.add('is-open');
            measureAndResize();
        }

        function closeList() {
            root.classList.remove('is-open');
            resizeFrame(closedHeight);
        }

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (root.classList.contains('is-open')) {
                closeList();
            } else {
                openList();
            }
        });

        dropdown.addEventListener('click', function (event) {
            var option = event.target.closest('.ui-select__option');
            if (!option) {
                return;
            }

            event.preventDefault();
            setSelected(option.getAttribute('data-value'), option.getAttribute('data-title'));
            closeList();
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                closeList();
            }
        });
    });
</script>
