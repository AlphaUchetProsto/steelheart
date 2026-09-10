<?php

namespace app\modules\vendor_order\models\Helpers;

use app\modules\vendor_order\models\db\CurrencyTable;

class CurrencyHelper
{
    protected static $_instance;
    /**
     * Определяет код валюты по текстовому описанию
     *
     * @param string $text Текст валюты (например, "юань", "доллар", "рубль")
     * @return string Код валюты (RUB, USD, EUR, CNY)
     * @throws \Exception Если не удалось распознать валюту
     */
    public static function detectCurrencyCodeByName(string $text = null): ?string
    {
        if (empty($text)) {
            return null;
        }

        // Массив ключевых слов -> код валюты
        $map = [
            'руб'      => 'RUB',
            'р'        => 'RUB',
            'рубль'    => 'RUB',
            'rub'    => 'RUB',
            'доллар'   => 'USD',
            'usd'      => 'USD',
            'евро'     => 'EUR',
            'eur'      => 'EUR',
            'юань'     => 'CNY',
            'cny'      => 'CNY',
            'юа'       => 'CNY',
            'тенге'       => 'KZT',
            'kzt'       => 'KZT',
        ];

        $text = mb_strtolower(trim($text), 'UTF-8');

        foreach ($map as $key => $code) {
            if (mb_strpos($text, $key) !== false) {
                return $code;
            }
        }

        throw new \Exception("Не удалось определить валюту: '{$text}'");
    }

    public static function getCurrency()
    {
        if (!isset(static::$_instance['currency'])) {
            $currency = CurrencyTable::find()->select(['id', 'code'])->orderBy('id')->asArray()->all();
            static::$_instance['currency'] = array_column($currency, 'id', 'code');
        }

        return static::$_instance['currency'];
    }

    public static function getCurrencyCodeByName(string $text = null): ?string
    {
        if (empty($text)) {
            return null;
        }
        $text = mb_strtolower(trim($text), 'UTF-8');

        $text = preg_replace('/\s+/', '', $text);

        return match($text) {
            'руб','р','рубль', 'rub' => 'RUB',
            'доллар', 'usd' => 'USD',
            'евро', 'eur' => 'EUR',
            'юань', 'юа', 'cny' => 'CNY',
            'тенге', 'kzt' => 'KZT',
            default => throw new \Exception("Не удалось определить валюту: '{$text}'")
        };

    }

    public static function getCurrencyIdByName(string $text = null): ?int
    {
        $code = static::getCurrencyCodeByName($text);
        $currency = static::getCurrency();

        return $currency[$code] ?? null;
    }
}
