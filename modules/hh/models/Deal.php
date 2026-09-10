<?php

namespace app\modules\hh\models;

use app\models\bitrix\Bitrix;
use app\components\bitrix\GeneralBitrixInterface;
use app\modules\hh\models\Client_hh;

class Deal extends Bitrix implements GeneralBitrixInterface
{
    public $id;
    public $title;
    public $stage_id;
    public $company_id; // ID Компании
    public $contact_id; // ID Контакта

    use \app\components\bitrix\Deal;

    private static function mapFields()
    {
        return [
            "ID" => "id",
            "TITLE" => "title",
            "CONTACT_ID" => "contact_id",
            "COMPANY_ID" => "company_id",
            "STAGE_ID" => "stage_id",
            "UF_CRM_1700056225025" => "Файл резюме",
            "UF_CRM_1700057856406" => "Источник привлечения кандидата",
            "UF_CRM_1700057892055" => "Ссылка",
            "UF_CRM_1700123670471" => "Город проживания",
            "UF_CRM_1700123697362" => "Опыт работы",
            "UF_CRM_1700123735118" => "Опыт вождения (из резюме)",
            "UF_CRM_1700123761212" => "Знание языков (из резюме)",
            "UF_CRM_1700123782227" => "Отклики (из резюме)",
            "UF_CRM_1700123805123" => "Навыки",
            "UF_CRM_1700123824660" => "Повышение квалификации, курсы",
            "UF_CRM_1700123841334" => "Тесты, экзамены",
            "UF_CRM_1700123873346" => "Электронные сертификаты",
            "UF_CRM_1700123890247" => "Рекомендации",
            "UF_CRM_1700123958968" => "Обо мне",
            "UF_CRM_1700123976818" => "Возраст",
            "UF_CRM_1700124019711" => "Пол",
            "UF_CRM_1700124037189" => "Дата рождения",
            "UF_CRM_1700124063645" => "Комментарии к резюме",
        ];
    }

    public function __construct($fields = [])
    {
        parent::__construct($fields, self::MAP_FIELDS);
    }

    public static function create($resume, $contact)
    {
        $bitrix = Bitrix::Bx24init();

        $mapFields = array_flip(static::mapFields());

        $name = $resume['first_name'] ?? 'скрыто соискателем';
        $last_name = $resume['last_name'] ?? 'ФИО';

        $fields = [
            'TITLE' => "$last_name $name {$resume['title']}",
            'ASSIGNED_BY_ID' => 535,
            'CATEGORY_ID' => 3,
            'STAGE_ID' => 'C3:PREPARATION',
            $mapFields['Источник привлечения кандидата'] => 1491,
            $mapFields['Ссылка'] => $resume['alternate_url'] ?? '',
            $mapFields['Город проживания'] => $resume['area']['name'] ?? null,
            $mapFields['Обо мне'] => $resume['skills'] ?? '',
            $mapFields['Возраст'] => $resume['age'] ?? '',
            $mapFields['Пол'] => $resume['gender']['name'] ?? '',
            $mapFields['Дата рождения'] => $resume['birth_date'] ?? '',
        ];

        if ($contact) {
            $fields['CONTACT_ID'] = $contact;
        }

        if(isset($resume['download']['pdf']['url']))
        {
            $client = new Client_hh;

            $document = $client->getDocument($resume['download']['pdf']['url']);
            $file_base64 = base64_encode($document);

            $fields[$mapFields['Файл резюме']] = $file_base64;
        }

        if (isset($resume['total_experience']['months'])) {
            $month = "Месяцев: {$resume['total_experience']['months']}\n";
        } else {
            $month = '';
        }

        $experience = "$month";
        if (isset($resume['experience'])) {
            foreach ($resume['experience'] as $item) {
                $experience .= $item['start'] . ' - ' . $item['end'] ?? 'по настоящее время' . " \n";
                $experience .= "{$item['company']}\n";
                $experience .= "{$item['position']}\n";
                $experience .= "{$item['description']}\n\n";
            }
        }
        $fields[$mapFields['Опыт работы']] = $experience;

        if (isset($resume['driver_license_types'])) {
            $driver_license_types = '';

            foreach ($resume['driver_license_types'] as $item)
            {
                $driver_license_types .= "{$item['id']} ";
            }

            $fields[$mapFields['Опыт вождения (из резюме)']] = $driver_license_types;
        }

        if (isset($resume['language'])) {
            $language = '';
            foreach ($resume['language'] as $item) {
                $language .= "{$item['name']} - {$item['level']['name']}\n";
            }

            $fields[$mapFields['Знание языков (из резюме)']] = $language;
        }

        if (isset($resume['skill_set'])) {
            $fields[$mapFields['Навыки']] = implode(', ', $resume['skill_set']);
        }

        if (isset($resume['education']['additional']))
        {
            $education_additional = '';

            foreach ($resume['education']['additional'] as $item)
            {
                $education_additional .= "{$item['year']} - {$item['name']}\n    {$item['organization']} - {$item['result']}\n";
            }

            $fields[$mapFields['Повышение квалификации, курсы']] = $education_additional;
        }

        if (isset($resume['education']['attestation']))
        {
            $education_attestation = null;

            foreach ($resume['education']['attestation'] as $item)
            {
                $education_attestation .= "{$item['year']} {$item['name']} {$item['organization']}\n";
            }

            $fields[$mapFields['Тесты, экзамены']] = $education_attestation;
        }

        if (isset($resume['certificate']))
        {
            $certificate = '';
            foreach ($resume['certificate'] as $item)
            {
                $certificate .= "{$item['achieved_at']} - {$item['title']}\n";
            }

            $fields[$mapFields['Электронные сертификаты']] = $certificate;
        }

        if (isset($resume['recommendation']))
        {
            $recommendation = '';
            foreach ($resume['recommendation'] as $item)
            {
                $recommendation .= "{$item['organization']}\n    {$item['name']} {$item['position']}\n";
            }

            $fields[$mapFields['Рекомендации']] = $recommendation;
        }

        if (isset($resume['owner']['comments'])) {
            $comments = json_decode($client->getDocument($resume['owner']['comments']['url']), true);

            if (!empty($comments['items'])) {
                $comments = collect($comments['items'])->map(function ($item) {
                    return $item['text'];
                })->implode("\n");

                $fields[$mapFields['Комментарии к резюме']] = $comments;
            }
        }

        $bitrix->request('crm.deal.add', [
            'fields' => $fields
        ]);
    }
}