<?php

/** @var \app\modules\contract_field\models\Bitrix\Userfield\Dto\ContractFieldStateDto $state */

use app\modules\contract_field\Module;
use yii\helpers\Html;

$isCardEdit = ($state->placement->mode ?? 'view') === 'edit';
$selectedId = $state->selectedContract->id ?? null;
$selectedTitle = null;
$contractEntityTypeId = Module::CONTRACT_ENTITY_TYPE_ID;
$fieldName = $state->placement->fieldName ?? '';
$itemFieldName = $state->itemFieldName !== '' ? $state->itemFieldName : $fieldName;
$nativeFieldName = $state->nativeFieldName ?? '';
$entityValueId = (int)($state->placement->entityValueId ?? 0);
$entityTypeId = (int)($state->entityTypeId ?? 0);

if ($state->selectedContract) {
    $selectedTitle = $state->selectedContract->title !== ''
        ? $state->selectedContract->title
        : ('Договор #' . $state->selectedContract->id);
}

$hasContracts = !empty($state->contracts);
$modeClass = $isCardEdit ? 'contract-field--edit' : 'contract-field--view';

$renderSelectOptions = static function () use ($state, $selectedId) {
    echo '<option value="">Не выбран</option>';
    foreach ($state->contracts as $contract) {
        $title = $contract->title !== '' ? $contract->title : ('Договор #' . $contract->id);
        $selected = $selectedId === $contract->id ? ' selected' : '';
        echo '<option value="' . (int)$contract->id . '"' . $selected . '>'
            . Html::encode($title)
            . '</option>';
    }
};
?>
<div
    class="contract-field <?= $modeClass ?>"
    id="contract-field-root"
    data-card-edit="<?= $isCardEdit ? '1' : '0' ?>"
    data-field-name="<?= Html::encode($fieldName) ?>"
    data-item-field-name="<?= Html::encode($itemFieldName) ?>"
    data-native-field-name="<?= Html::encode($nativeFieldName) ?>"
    data-entity-type-id="<?= $entityTypeId ?>"
    data-entity-value-id="<?= $entityValueId ?>"
    data-contract-entity-type-id="<?= (int)$contractEntityTypeId ?>"
    data-selected-id="<?= $selectedId ? (int)$selectedId : '' ?>"
    data-selected-title="<?= Html::encode($selectedTitle ?? '') ?>"
>
    <?php if ($state->error): ?>
        <div class="contract-field__error"><?= Html::encode($state->error) ?></div>
    <?php elseif (!$hasContracts): ?>
        <div class="contract-field__empty">Нет договоров, привязанных к компании</div>
    <?php elseif ($isCardEdit): ?>
        <div class="contract-field__control">
            <select id="contract-field-select" class="contract-field__select<?= $selectedId ? '' : ' is-empty' ?>">
                <?php $renderSelectOptions(); ?>
            </select>
        </div>
    <?php else: ?>
        <div class="contract-field__view-row" id="contract-field-view" title="Изменить">
            <?php if ($selectedId && $selectedTitle !== null): ?>
                <button
                    type="button"
                    class="contract-field__link"
                    id="contract-field-link"
                    data-id="<?= (int)$selectedId ?>"
                ><?= Html::encode($selectedTitle) ?></button>
            <?php else: ?>
                <span class="contract-field__empty" id="contract-field-empty">не заполнено</span>
            <?php endif; ?>
        </div>

        <div class="contract-field__inline-edit is-hidden" id="contract-field-inline-edit">
            <div class="contract-field__control">
                <select id="contract-field-select-view" class="contract-field__select<?= $selectedId ? '' : ' is-empty' ?>">
                    <?php $renderSelectOptions(); ?>
                </select>
            </div>
            <div class="contract-field__actions">
                <button type="button" class="contract-field__btn contract-field__btn--save" id="contract-field-save">Сохранить</button>
                <button type="button" class="contract-field__btn contract-field__btn--cancel" id="contract-field-cancel">Отменить</button>
            </div>
            <div class="contract-field__inline-error is-hidden" id="contract-field-inline-error"></div>
        </div>
    <?php endif; ?>
</div>

<script src="//api.bitrix24.com/api/v1/"></script>
<script>
    BX24.init(function () {
        var root = document.getElementById('contract-field-root');
        if (!root) {
            return;
        }

        var isCardEdit = root.getAttribute('data-card-edit') === '1';
        var fieldName = root.getAttribute('data-field-name') || '';
        var itemFieldName = root.getAttribute('data-item-field-name') || fieldName;
        var nativeFieldName = root.getAttribute('data-native-field-name') || '';
        var entityTypeId = parseInt(root.getAttribute('data-entity-type-id') || '0', 10);
        var entityValueId = parseInt(root.getAttribute('data-entity-value-id') || '0', 10);
        var contractEntityTypeId = parseInt(root.getAttribute('data-contract-entity-type-id') || '0', 10);
        var selectedId = root.getAttribute('data-selected-id') || '';
        var selectedTitle = root.getAttribute('data-selected-title') || '';

        function resizeFrame(height) {
            BX24.resizeWindow('100%', height);
        }

        function resizeForMode(mode) {
            if (mode === 'inline-edit') {
                resizeFrame(80);
            } else if (mode === 'card-edit') {
                resizeFrame(36);
            } else {
                resizeFrame(20);
            }
        }

        resizeForMode(isCardEdit ? 'card-edit' : 'view');
        setTimeout(function () {
            resizeForMode(isCardEdit ? 'card-edit' : 'view');
        }, 50);

        function applyEmptyClass(select) {
            if (!select) {
                return;
            }
            if (select.value) {
                select.classList.remove('is-empty');
            } else {
                select.classList.add('is-empty');
            }
        }

        function sendSetValue(value) {
            var parts = (window.name || '').split('|');
            var domain = (parts[0] || '').replace(/:(80|443)$/, '');
            var protocol = parseInt(parts[1], 10) ? 's' : '';
            var appSid = parts[2] || '';
            var target = 'http' + protocol + '://' + domain;

            parent.postMessage(
                'setValue:' + JSON.stringify(value) + '::' + appSid,
                target
            );
        }

        function setFieldValue(value) {
            sendSetValue(value === '' || value === null || value === undefined ? '' : String(value));
        }

        function buildSaveFields(value) {
            var fields = {};
            fields[itemFieldName] = value;
            if (nativeFieldName) {
                fields[nativeFieldName] = value;
            }
            return fields;
        }

        // Режим редактирования карточки: штатные Сохранить / Отменить
        if (isCardEdit) {
            var select = document.getElementById('contract-field-select');
            if (!select) {
                return;
            }

            select.addEventListener('change', function () {
                applyEmptyClass(select);
                setFieldValue(select.value);
                if (nativeFieldName && entityTypeId && entityValueId) {
                    BX24.callMethod('crm.item.update', {
                        entityTypeId: entityTypeId,
                        id: entityValueId,
                        fields: buildSaveFields(select.value)
                    });
                }
            });
            applyEmptyClass(select);
            return;
        }

        // Режим просмотра: клик по полю (не по ссылке) → локальное изменение
        var viewRow = document.getElementById('contract-field-view');
        var inlineEdit = document.getElementById('contract-field-inline-edit');
        var saveBtn = document.getElementById('contract-field-save');
        var cancelBtn = document.getElementById('contract-field-cancel');
        var selectView = document.getElementById('contract-field-select-view');
        var inlineError = document.getElementById('contract-field-inline-error');
        var link = document.getElementById('contract-field-link');

        if (!viewRow || !inlineEdit || !saveBtn || !cancelBtn || !selectView) {
            return;
        }

        function showInlineError(message) {
            if (!inlineError) {
                return;
            }
            if (!message) {
                inlineError.textContent = '';
                inlineError.classList.add('is-hidden');
                return;
            }
            inlineError.textContent = message;
            inlineError.classList.remove('is-hidden');
        }

        function enterInlineEdit() {
            selectView.value = selectedId || '';
            applyEmptyClass(selectView);
            showInlineError('');
            viewRow.classList.add('is-hidden');
            inlineEdit.classList.remove('is-hidden');
            resizeForMode('inline-edit');
        }

        function leaveInlineEdit() {
            inlineEdit.classList.add('is-hidden');
            viewRow.classList.remove('is-hidden');
            showInlineError('');
            resizeForMode('view');
        }

        function updateViewDisplay(id, title) {
            selectedId = id || '';
            selectedTitle = title || '';
            root.setAttribute('data-selected-id', selectedId);
            root.setAttribute('data-selected-title', selectedTitle);

            var emptyNode = document.getElementById('contract-field-empty');
            var currentLink = document.getElementById('contract-field-link');

            if (selectedId) {
                if (emptyNode) {
                    emptyNode.remove();
                }
                if (!currentLink) {
                    currentLink = document.createElement('button');
                    currentLink.type = 'button';
                    currentLink.className = 'contract-field__link';
                    currentLink.id = 'contract-field-link';
                    viewRow.appendChild(currentLink);
                    currentLink.addEventListener('click', openContract);
                }
                currentLink.setAttribute('data-id', selectedId);
                currentLink.textContent = selectedTitle || ('Договор #' + selectedId);
            } else {
                if (currentLink) {
                    currentLink.remove();
                }
                if (!document.getElementById('contract-field-empty')) {
                    var span = document.createElement('span');
                    span.className = 'contract-field__empty';
                    span.id = 'contract-field-empty';
                    span.textContent = 'не заполнено';
                    viewRow.appendChild(span);
                }
            }
        }

        function openContract(event) {
            event.preventDefault();
            event.stopPropagation();
            var id = (event.currentTarget || link).getAttribute('data-id');
            if (!id) {
                return;
            }
            BX24.openPath('/crm/type/' + contractEntityTypeId + '/details/' + id + '/');
        }

        if (link) {
            link.addEventListener('click', openContract);
        }

        viewRow.addEventListener('click', function (event) {
            if (event.target.closest && event.target.closest('.contract-field__link')) {
                return;
            }
            enterInlineEdit();
        });

        selectView.addEventListener('change', function () {
            applyEmptyClass(selectView);
        });

        cancelBtn.addEventListener('click', function () {
            leaveInlineEdit();
        });

        saveBtn.addEventListener('click', function () {
            if (!fieldName || !entityTypeId || !entityValueId) {
                showInlineError('Недостаточно данных для сохранения');
                return;
            }

            var value = selectView.value || '';
            var fields = buildSaveFields(value);

            saveBtn.disabled = true;
            cancelBtn.disabled = true;
            showInlineError('');

            BX24.callMethod('crm.item.update', {
                entityTypeId: entityTypeId,
                id: entityValueId,
                fields: fields
            }, function (result) {
                saveBtn.disabled = false;
                cancelBtn.disabled = false;

                if (result.error()) {
                    showInlineError(result.error_description() || result.error().toString());
                    return;
                }

                var option = selectView.options[selectView.selectedIndex];
                var title = value ? (option ? option.text : ('Договор #' + value)) : '';
                updateViewDisplay(value, title);
                leaveInlineEdit();
            });
        });

        applyEmptyClass(selectView);
    });
</script>
