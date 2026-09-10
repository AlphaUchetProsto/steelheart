<?php

/**
 * Соответствие полей «Договор» по entityTypeId сущности CRM.
 *
 * native — штатное поле «Привязка к смарт-процессу» (Договоры), источник для 1С
 * custom — наше поле типа contract (rest_*)
 *
 * Если native или custom пустые — синхронизация для этого типа пропускается.
 *
 * Пример заполнения:
 *
 * return [
 *     1042 => [
 *         'native' => 'UF_CRM_9_1234567890',
 *         'custom' => 'UF_CRM_9_1789070555',
 *     ],
 *     31 => [
 *         'native' => 'UF_CRM_SMART_INVOICE_1234567890',
 *         'custom' => 'UF_CRM_SMART_INVOICE_1789070555',
 *     ],
 *     4 => [
 *         'native' => 'UF_CRM_1234567890',
 *         'custom' => 'UF_CRM_1789070555',
 *     ],
 * ];
 */
return [
    // УПД
    1042 => [
        'native' => '',
        'custom' => '',
    ],

    // Новые счета (smart invoice)
    31 => [
        'native' => '',
        'custom' => '',
    ],

    // Компания
    4 => [
        'native' => '',
        'custom' => '',
    ],
];
