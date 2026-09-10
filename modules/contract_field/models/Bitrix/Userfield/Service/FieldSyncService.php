<?php

namespace app\modules\contract_field\models\Bitrix\Userfield\Service;

use app\modules\contract_field\models\Bitrix\Userfield\Dto\FieldPairDto;
use app\modules\contract_field\models\Bitrix\Userfield\Provider\CrmItemProvider;
use app\modules\contract_field\Module;

class FieldSyncService
{
    private CrmItemProvider $crmItemProvider;

    public function __construct(CrmItemProvider $crmItemProvider)
    {
        $this->crmItemProvider = $crmItemProvider;
    }

    /**
     * Штатное поле — источник истины: при расхождении обновляет кастомное.
     */
    public function syncFromNative(int $entityTypeId, int $itemId): bool
    {
        $pair = Module::getFieldPair($entityTypeId);
        if (!$pair || !$this->isPairConfigured($pair)) {
            return false;
        }

        $item = $this->crmItemProvider->getItem($entityTypeId, $itemId);
        if (!$item) {
            return false;
        }

        $nativeKey = $this->resolveItemFieldKey($item, $pair->native);
        $customKey = $this->resolveItemFieldKey($item, $pair->custom);

        $nativeId = $this->normalizeContractId($item[$nativeKey] ?? null);
        $customId = $this->normalizeContractId($item[$customKey] ?? null);

        if ($nativeId === $customId) {
            return false;
        }

        $this->crmItemProvider->updateItem($entityTypeId, $itemId, [
            $customKey => $nativeId !== null ? (string)$nativeId : '',
        ]);

        return true;
    }

    /**
     * Поля для записи из UI: кастомное + штатное (одно значение).
     *
     * @return array<string, string>
     */
    public function buildDualWriteFields(int $entityTypeId, string $customFieldName, $value): array
    {
        $normalized = $this->normalizeContractId($value);
        $stringValue = $normalized !== null ? (string)$normalized : '';

        $fields = [
            $customFieldName => $stringValue,
        ];

        $pair = Module::getFieldPair($entityTypeId);
        if (!$pair || !$this->isPairConfigured($pair)) {
            return $fields;
        }

        $nativeKey = $this->toCrmItemFieldName($pair->native);
        $customKey = $this->toCrmItemFieldName($pair->custom);

        $fields[$customKey] = $stringValue;
        $fields[$nativeKey] = $stringValue;

        if ($customFieldName !== $customKey && $customFieldName !== '') {
            $fields[$customFieldName] = $stringValue;
        }

        return $fields;
    }

    public function isPairConfigured(FieldPairDto $pair): bool
    {
        return $pair->native !== '' && $pair->custom !== '';
    }

    public function normalizeContractId($value): ?int
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if ($value === null || $value === '' || $value === false || $value === 'false') {
            return null;
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $id = (int)$value;

            return $id > 0 ? $id : null;
        }

        if (is_string($value)) {
            if (preg_match('/^(?:DYNAMIC_\d+|T\d+)_(\d+)$/i', $value, $matches)) {
                $id = (int)$matches[1];

                return $id > 0 ? $id : null;
            }

            // Название договора и прочий текст из 1С в ID не превращаем
            if (!preg_match('/^\d+$/', $value)) {
                return null;
            }
        }

        return null;
    }

    public function toCrmItemFieldName(string $code): string
    {
        if ($code === '') {
            return '';
        }

        if (preg_match('/^uf[A-Z0-9]/', $code)) {
            return $code;
        }

        return lcfirst(str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $code)))));
    }

    private function resolveItemFieldKey(array $item, string $configuredCode): string
    {
        $candidates = array_values(array_unique(array_filter([
            $configuredCode,
            $this->toCrmItemFieldName($configuredCode),
        ])));

        foreach ($candidates as $key) {
            if (array_key_exists($key, $item)) {
                return $key;
            }
        }

        $lowerMap = [];
        foreach ($item as $key => $unused) {
            $lowerMap[strtolower((string)$key)] = (string)$key;
        }

        foreach ($candidates as $key) {
            $lower = strtolower($key);
            if (isset($lowerMap[$lower])) {
                return $lowerMap[$lower];
            }
        }

        return $this->toCrmItemFieldName($configuredCode);
    }
}
