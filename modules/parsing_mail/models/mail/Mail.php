<?php
namespace app\modules\parsing_mail\models\mail;

use App\HTTP\HTTP;
use Yii;
use yii\helpers\ArrayHelper;
use PhpImap\Mailbox;
use PhpImap\Exceptions\ConnectionException;
use app\models\bitrix\Bitrix;
use app\models\logger\DebugLogger;
use app\modules\config_distribution\models\ConfigModel;

class Mail
{
    public $mailbox;
    public $mail_ids;
    public $login;
    public $last_id;
    public $file;
    private $webhook;

    public $search_email;
    public $search_text;
    public $search_subject;

    public function __construct($host,$port,$login, $password, $folder)
    {
        $this->login = $login;
        $this->file = Yii::getAlias("@parsing_mail") . "/temp/mail/$login.txt";
        $this->mailbox = new Mailbox(
            "{".$host.":".$port."/imap/ssl}$folder", // cairo_font_options_get_subpixel_order(options)
            $login, // Username for the before configured mailbox
            $password, // Password for the before configured username

            '', // Directory, where attachments will be saved (optional)
            'UTF-8', // Server encoding (optional)
            true, // Trim leading/ending whitespaces of IMAP path (optional)
            false // Attachment filename mode (optional; false = random filename; true = original filename)
        );
    }

    public function getMailbox()
    {
        try {
            $date = date('d F Y', strtotime('-1 day', time()));
            $this->mail_ids = $this->mailbox->searchMailbox("SINCE \"$date\"");//SINCE 27-09-2023 //1695796327
//            $this->mail_ids = $this->mailbox->searchMailbox("ALL");//SINCE 27-09-2023 //1695796327
//            $this->mail_ids = $this->mailbox->searchMailbox("FROM \"lidysts@yandex.ru\"");//SINCE 27-09-2023 //1695796327
        } catch(\PhpImap\Exceptions\ConnectionException $ex) {
            echo "IMAP connection failed: " . implode(",", $ex->getErrors('all'));
            die();
        }
    }

    public function getMailboxTest()
    {
        try {
            $date = date('d F Y', strtotime('-3 day', time()));
//            dd($date);
//            $date = date('d F Y', strtotime('-3 day', time()));
//            $date = date('27 F Y');
            $this->mail_ids = $this->mailbox->searchMailbox("SINCE \"$date\"");//SINCE 27-09-2023 //1695796327
//            $this->mail_ids = $this->mailbox->searchMailbox("ON \"$date\"");//SINCE 27-09-2023 //1695796327
//            $this->mail_ids = $this->mailbox->searchMailbox("ALL");//SINCE 27-09-2023 //1695796327
//            $this->mail_ids = $this->mailbox->searchMailbox("FROM \"noreply@tilda.ws\" SINCE \"$date\"");//SINCE 27-09-2023 //1695796327
        } catch(\PhpImap\Exceptions\ConnectionException $ex) {
            echo "IMAP connection failed: " . implode(",", $ex->getErrors('all'));
            die();
        }
    }



    public function getLastId()
    {
        if(!file_exists($this->file))
        {
            file_put_contents($this->file, 0);
            $this->last_id = 0;
            return false;
        }
        $this->last_id = file_get_contents($this->file);
    }

    public function setLastMailbox()
    {
        file_put_contents($this->file, $this->last_id);
    }

    public function setLastId($id_message)
    {
        $this->last_id = $id_message;
    }

    public function createLead($fields = [])
    {
        $lead_id = $this->webhook->request('crm.lead.add', ['fields' => $fields]);
        return $lead_id;
    }


    public function createDeal($fields = [])
    {
        $deal_id = $this->webhook->request('crm.deal.add', ['fields' => $fields]);
        return $deal_id;
    }

    public function checkCompany($email)
    {
        $companies = $this->webhook->request('crm.company.list', ['FILTER' => ['EMAIL' => $email]]);
        if(empty($companies))
            return null;
        else
            return $companies[0]['ID'];
    }

    public function createContact($fields = [])
    {
        try{
            $contact_id = $this->webhook->request('crm.contact.add', ['fields' => $fields]);
        }
        catch (\Exception $e)
        {
            unset($fields['EMAIL']);
            $contact_id = $this->webhook->request('crm.contact.add', ['fields' => $fields]);
        }
        return $contact_id;
    }

    function get_string_between($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return trim(substr($string, $ini, $len));
    }

    function deleteEnter($string){
        return trim(preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $string));
    }
	
    public function readMail()
    {
        $this->webhook = Bitrix::BX24init();
        $logger_new = DebugLogger::instance("mail"); //!!!
//        dump($this->last_id);
//        dd($this->mail_ids);
            foreach ($this->mail_ids as $num_key => $id_message)
            {
                try {

                    if($this->last_id >= $id_message)
                    continue;

                    $this->setLastId($id_message);
                    $this->setLastMailbox();


                    $log = '';

                    $mail = $this->mailbox->getMail($id_message);

                    $haystack = $mail->subject;
                    $sender = $mail->fromAddress;
                    $name = $mail->fromName;
                    $mailContent = $mail->textPlain;
                    $log .= "ID сообщения $id_message\n";
                    $log .= "Тема $haystack\n";
                    $log .= "Отправитель $sender\n";
                    $log .= "Имя $name\n";
                    $logger_new->save($log, $log, '$log'); //!!!

                    $html = false;
                    if($mailContent == '')
                    {
                        $mailContent_0 = trim(preg_replace('/<div>/', "\r\n", $mail->textHtml));
                        $mailContent = trim(str_replace(['&nbsp;', '<br>'], "\n",$mailContent_0));
                        $mailContent = strip_tags(html_entity_decode($mailContent));
                        $html = true;
                    }
                    else
                    {
                        $mailContent = trim(preg_replace('/[\r\n]+/', "\r\n",strip_tags(html_entity_decode($mailContent))));
    //                $mailContent = html_entity_decode($mailContent);
                    }

                    if($sender != 'noreply@tilda.ws' && $sender != 'ads.notifications@vk.company' && $sender != 'lidysts@yandex.ru' && $sender != 'wordpress@sts57.ru' && $sender != 'robot@marquiz.ru')
                    {
                        echo "Fail sender\n";
                        continue;
                    }

                    $fields = [];
                    $fields['SOURCE_ID'] = 'EMAIL'; //UNCOMMENT

                    if($sender == 'noreply@tilda.ws')
                    {
                        if (preg_match('~sts\.club~', $haystack)) {
                            $config_block_id = require Yii::getAlias("@modules") . '/parsing_mail/config/config.php';
                            $fields['COMMENTS'] = "";

                            $name = $this->get_string_between($mailContent, 'Name:', "\n");
                            $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Телефон:', "\n"));
                            if($phone == '') {
                                $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Phone:', "\n"));
                            }

                            $number_rooms = $this->get_string_between($mailContent, 'Нужно_комнат:', "\n");
                            $important = $this->get_string_between($mailContent, 'Важно:', "\n");
                            $date =$this->get_string_between($mailContent, 'Дата_покупки:', "\n");
                            $gift = $this->get_string_between($mailContent, 'Подарок:', "\n");
                            $block_id =$this->get_string_between($mailContent, 'Block ID:', "\n");

                            $fields['TITLE'] = "Квиз СТС";
                            $fields['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                            $fields['PHONE'][0]['VALUE'] = mb_substr($fields['PHONE'][0]['VALUE'] ?? '', 0, 11);
                            $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                            $fields['NAME'] = $name;
                            if(isset($config_block_id[$block_id]))
                            {
                                $fields['UF_CRM_1647262108349'] = $config_block_id[$block_id]['object'] ?? '0';
                                $fields['COMMENTS'] .="{$config_block_id[$block_id]['comment']}\n" ?? '';
                            }
                            if($number_rooms != '')
                            {
                                $fields['COMMENTS'] .= "Нужно_комнат: {$number_rooms}\n";
                            }
                            if($important != '')
                            {
                                $fields['COMMENTS'] .= "Важно: {$important}\n";
                            }
                            if($date != '')
                            {
                                $fields['COMMENTS'] .= "Дата_покупки: {$date}\n";
                            }
                            if($gift != '')
                            {
                                $fields['COMMENTS'] .= "Подарок: {$gift}\n";
                            }
                        } else if (preg_match('~pervyi\.site~', $haystack)) {
                            $config_block_id = require Yii::getAlias("@modules") . '/parsing_mail/config/config.php';
                            $fields['COMMENTS'] = "";

                            $name = $this->get_string_between($mailContent, 'Name:', "\n");
                            $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Phone:', "\n"));
                            $date = $this->get_string_between($mailContent, 'Укажите_дату_сдачи_объекта:', "\n");
                            $number_rooms = $this->get_string_between($mailContent, 'Укажите_желаемое_количество_комнат:', "\n");
                            $block_id =$this->get_string_between($mailContent, 'Block ID:', "\n");
                            $request_id = $this->get_string_between($mailContent, 'Request ID:', "\n");
                            //                $utm_source = str_replace('=', '', $this->get_string_between($mailContent, 'utm_source', 'utm_medium'));
                            //                $utm_medium = str_replace('=', '', $this->get_string_between($mailContent, 'utm_medium', 'utm_campaign'));
                            //                $utm_campaign = str_replace('=', '', $this->get_string_between($mailContent, 'utm_campaign', 'utm_content'));
                            //                $utm_content = str_replace('=', '', $this->get_string_between($mailContent, 'utm_content', "\n"));
                            $fields['TITLE'] = "Заявка с Лендинга";
                            $fields['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                            $fields['PHONE'][0]['VALUE'] = mb_substr($fields['PHONE'][0]['VALUE'] ?? '', 0, 11);
                            $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                            $fields['NAME'] = $name;
                            if(isset($config_block_id[$block_id]))
                            {
                                $fields['UF_CRM_1647262108349'] = $config_block_id[$block_id]['object'] ?? '0';
                                $fields['COMMENTS'] .="{$config_block_id[$block_id]['comment']}\n" ?? '';
                            }
                            if($number_rooms != '')
                            {
                                $fields['COMMENTS'] .= "Укажите_желаемое_количество_комнат: {$number_rooms}\n";
                            }
                            if($date != '')
                            {
                                $fields['COMMENTS'] .= "Укажите_дату_сдачи_объекта: {$date}\n";
                            }
                            $fields['SOURCE_ID'] = 'UC_EJN31B';
                        }
                    }
                    elseif($sender == 'ads.notifications@vk.company')
                    {
                        $question = '';
                        $name = $this->get_string_between($mailContent, 'Имя:', "\n");
                        $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Телефон:', "\n"));

                        if(!$html)
                        {
                            $temp_str = explode("\n", strip_tags(html_entity_decode($mailContent)));
                        }
                        else
                        {
                            $temp_str = explode("\n", $mailContent);
                        }

                        $ques = true;
                        foreach ($temp_str as $str)
                        {
                            if(preg_match('~Вопрос:~',$str) && $ques)
                            {
                                $question .= "$str\n";
                                $ques = false;
                            }
                            elseif(preg_match('~Ответ:~',$str) && !$ques)
                            {
                                $question .= "$str\n";
                                $ques = true;
                            }
                        }
                        $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                        $fields_contact['NAME'] = $name;
                        $fields['UF_CRM_1647262108349'] = '0';
                        $fields['TITLE'] = 'Заявка VK Ads';
                        $fields['COMMENTS'] = $question;
                        $fields['SOURCE_ID'] = 'UC_8DE020';
                    }
                    elseif($sender == 'lidysts@yandex.ru')
                    {
                        if(!$html)
                        {
                            $temp_str = explode("\n", strip_tags(html_entity_decode($mailContent)));
                        }
                        else
                        {
                            $temp_str = explode("\n", $mailContent);
                        }
                        $phone = '';
                        $number_rooms = '';
                        $price = '';
                        $district = '';
                        $due_date = '';
                        $rooms_finish = '';
                        $fields['COMMENTS'] = "";

                        foreach ($temp_str as $str)
                        {
                            if(preg_match('~Телефон:~', $str))
                            {
                                $phone = trim(str_replace('Телефон:', '', $str));
                            }
                            elseif(preg_match('~Кол-во комнат~', $str))
                            {
    //                        $number_rooms = trim(str_replace('Кол-во комнат:', '', $str));
                                $fields['COMMENTS'] .= "$str\n";
                            }
                            elseif(preg_match('~Цена~', $str))
                            {
    //                        $price = trim(str_replace('Цена:', '', $str));
                                $fields['COMMENTS'] .= "$str\n";
                            }
                            elseif(preg_match('~Район~', $str))
                            {
    //                        $district = trim(str_replace('Район:', '', $str));
                                $fields['COMMENTS'] .= "$str\n";
                            }
                            elseif(preg_match('~Срок сдачи~', $str))
                            {
    //                        $due_date = trim(str_replace('Срок сдачи:', '', $str));
                                $fields['COMMENTS'] .= "$str\n";
                            }
                            elseif(preg_match('~Квартира с отделкой~', $str))
                            {
    //                        $rooms_finish = trim(str_replace('Квартира с отделкой', '', $str));
                                $fields['COMMENTS'] .= "$str\n";
                            }
                        }
                        $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                        $fields['UF_CRM_1647262108349'] = '0';
                        $fields['TITLE'] = 'Заявка с Каталога';
                    }
                    elseif($sender == 'wordpress@sts57.ru')
                    {
                        $fields['COMMENTS'] = "";
                        $name = preg_replace('~;~', '', $this->get_string_between($mailContent, 'Имя -', "\n"));
                        if(preg_match('~Ипотека~', $haystack)) {
                            $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Телефон -', "\n"));
                            if($phone == '') {
                                $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'телефона -', "\n"));
                            }
                        } elseif(preg_match('~Резервирование~', $haystack)) {
                            $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'телефона -', ";"));
                            $fields['COMMENTS'] .="Обьект -".$this->get_string_between($mailContent, 'Обьект -', "\n")."\n";
                            $fields['COMMENTS'] .=mb_substr($mailContent, mb_strpos($mailContent, "Ссылка на товар -"))."\n";
                        } else {
                            $phone = preg_replace('~[^0-9]~', '',mb_substr($mailContent, mb_strpos($mailContent, "Телефон -") + 9));
                        }

                        $fields['TITLE'] = "Заявка с Сайта";
                        $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                        $fields_contact['NAME'] = trim($name);
                        $fields['NAME'] = trim($name);
                        if (preg_match('~source=yd~', $mailContent)) {
                            $fields['SOURCE_ID'] = 'UC_UBXYUJ';
                        } else if (preg_match('~Запрос~', $haystack)) {
                            $fields['SOURCE_ID'] = 'UC_HZOJY3';
                        } else if (preg_match('~Старт продаж~', $haystack)) {
                            $fields['SOURCE_ID'] = 'UC_YLJBO8';
                        } else if (preg_match('~Ипотека~', $haystack)) {
                            $fields['SOURCE_ID'] = 'UC_V3G6Y1';
                        } else {
                            $fields['SOURCE_ID'] = 'UC_HZOJY3';
                        }
                    } elseif($sender == 'robot@marquiz.ru') {
                        $mailContent_0 = trim(preg_replace('/<div>/', "\r\n", $mail->textHtml));
                        $mailContent = trim(str_replace(['&nbsp;', '<br>'], "\n",$mailContent_0));
                        $mailContent = strip_tags(html_entity_decode($mailContent));

                        $mailContent = $this->getMail($mailContent);

                        $hasAnswer = false;
                        $waitAnswer = false;
                        foreach ($mailContent as $key => $itemMail) {
                            if (!preg_match('~Имя~', $itemMail)) {
                                unset($mailContent[$key]);
                            } else {
                                break;
                            }
                        }
                        foreach ($mailContent as $key => &$itemMail) {
                            if (preg_match('~Имя~', $itemMail)) {
                                $itemMail = "Имя |";
                            } else if (preg_match('~Телефон~', $itemMail)) {
                                $itemMail = "Телефон |";
                            } else if (preg_match('~Какую квартиру вы ищете\?~', $itemMail)) {
                                $itemMail = "Какую квартиру вы ищете? |";
                            } else if (preg_match('~В каком районе\?~', $itemMail)) {
                                $itemMail = "В каком районе? |";
                            } else if (preg_match('~Какая отделка вас интересует\?~', $itemMail)) {
                                $itemMail = "Какая отделка вас интересует? |";
                            } else if (preg_match('~Как скоро планируете покупку квартиры\?~', $itemMail)) {
                                $itemMail = "Как скоро планируете покупку квартиры? |";
                            } else if (preg_match('~Какой способ оплаты вам подходит\?~', $itemMail)) {
                                $itemMail = "Какой способ оплаты вам подходит? |";
                            }
                        }
                        $mailContent = str_replace("|\n", '', implode("\n", $mailContent));

                        $name = preg_replace('~;~', '', $this->get_string_between($mailContent, 'Имя ', "\n"));
                        $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Телефон ', "\n"));

                        $fields['UF_CRM_1720492314247'] = $this->get_string_between($mailContent, 'Какую квартиру вы ищете? ', "\n");
                        $fields['UF_CRM_1720492324469'] = $this->get_string_between($mailContent, 'В каком районе? ', "\n");
                        $fields['UF_CRM_1720492330297'] = $this->get_string_between($mailContent, 'Какая отделка вас интересует? ', "\n");
                        $fields['UF_CRM_1720492337308'] = $this->get_string_between($mailContent, 'Как скоро планируете покупку квартиры? ', "\n");
                        $fields['UF_CRM_1720492343132'] = $this->get_string_between($mailContent, 'Какой способ оплаты вам подходит? ', "\n");


                        $fields['TITLE'] = "Квиз";
                        $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                        $fields_contact['NAME'] = trim($name);
                        $fields['NAME'] = trim($name);

                        $fields['SOURCE_ID'] = 'UC_FGCEAY';
                        $fields['TRACE'] = '116';
                    }
                    else
                    {
                        echo "Fail sender\n";
                        continue;
                    }
                    //UC_8DE020 - Источник “VK Target”
                    //UC_V3G6Y1 - Источник “Заявка с сайта - Ипотека”
                    //UC_YLJBO8 - Источник “Заявка с сайта - Старт продаж”
                    //UC_HZOJY3 - Источник “Заявка с сайта - Запрос”
                    //UC_EJN31B - Источник “Заявка с лендинга”
                    //UC_UBXYUJ - Источник “Заявка с сайта - Я.Д.”
                    $fields['ASSIGNED_BY_ID'] = 10; //UNCOMMENT

                    $fields_contact['PHONE'][0]['VALUE'] = mb_substr($fields_contact['PHONE'][0]['VALUE'] ?? '', 0, 11);
                    $contact = $this->webhook->request("crm.duplicate.findbycomm", [
                        "entity_type" => "CONTACT",
                        "type" => "PHONE",
                        "values" => [$fields_contact['PHONE'][0]['VALUE']],
                    ]);

                    if(empty($contact))
                    {
                        $contact = $this->createContact($fields_contact);
                        $deal = [];
                    }
                    else
                    {
                        $contact = $contact['CONTACT'][0];
                        $deal = $this->webhook->request("crm.deal.list", ['filter' =>[
//                            'CATEGORY_ID' => 20,
                            'CLOSED' => 'N',
                            'CONTACT_ID' => $contact,
                        ]]);
                    }
                    $fields['CONTACT_ID'] = $contact;
                    $fields['CATEGORY_ID'] = 20;
                    $logger_new->save($contact, $contact, '$contact'); //!!!

                    if(!empty($deal))
                    {
                        $full_contact = $this->webhook->request("crm.contact.get", ['ID' => $contact]);
                        $logger_new->save($full_contact, $full_contact, '$full_contact'); //!!!

                        $text = "Поступила новая заявка: {$full_contact['LAST_NAME']} {$full_contact['NAME']} {$fields_contact['PHONE'][0]['VALUE']}";
                        $commands[] = $this->webhook->buildCommand("crm.timeline.comment.add", ['fields' => [
                            "ENTITY_ID" => $deal[0]['ID'],
                            "ENTITY_TYPE" => 'deal',
                            "COMMENT" => $text,
                        ]]);
                        $commands[] = $this->webhook->buildCommand('im.notify.personal.add', [
                            'USER_ID' => $full_contact['ASSIGNED_BY_ID'],
                            'MESSAGE' => "Поступили изменения в <a href='https://stsgrupp.bitrix24.ru/crm/deal/details/{$deal[0]['ID']}/'>{$deal[0]['TITLE']}</a>",
                        ]);
                        $this->webhook->batchRequest($commands);
                        unset($commands);
                    }
                    else
                    {
                        $deal = $this->createDeal($fields);
                        $logger_new->save($deal, $deal, '$deal'); //!!!
                    }

                }
                catch (\Exception $e)
                {
                    file_get_contents("https://api.telegram.org/bot1034272956:AAHbDVLSIxmQhiZtbUDl3HLgzfu77KbFByo/sendMessage?chat_id=449614227&text="."Произошла ошибка СТС ГРУПП парсер почты. $id_message - id письма");
                }
            }



        return true;
    }

    public function readMailTest()
    {
        $this->webhook = Bitrix::BX24init();
        $logger_new = DebugLogger::instance("mail"); //!!!
//        dd($this->mail_ids);
        dd(1);
        $this->mail_ids = [1872];
        foreach ($this->mail_ids as $num_key => $id_message)
        {
            try {
//                if($this->last_id >= $id_message)
//                    continue;
//
//                $this->setLastId($id_message);
//                $this->setLastMailbox();


                $log = '';

                $mail = $this->mailbox->getMail($id_message);

                $haystack = $mail->subject;
                $sender = $mail->fromAddress;
                $name = $mail->fromName;
                $mailContent = $mail->textPlain;
//                $log .= "ID сообщения $id_message\n";
//                $log .= "Тема $haystack\n";
//                $log .= "Отправитель $sender\n";
//                $log .= "Имя $name\n";
//                $logger_new->save($log, $log, '$log'); //!!!
                $html = false;
                if($mailContent == '')
                {
                    $mailContent_0 = trim(preg_replace('/<div>/', "\r\n", $mail->textHtml));
                    $mailContent = trim(str_replace(['&nbsp;', '<br>'], "\n",$mailContent_0));
                    $mailContent = strip_tags(html_entity_decode($mailContent));
                    $html = true;
                }
                else
                {
                    $mailContent = trim(preg_replace('/[\r\n]+/', "\r\n",strip_tags(html_entity_decode($mailContent))));
                    //                $mailContent = html_entity_decode($mailContent);
                }

                if($sender != 'noreply@tilda.ws' && $sender != 'ads.notifications@vk.company' && $sender != 'lidysts@yandex.ru' && $sender != 'wordpress@sts57.ru' && $sender != 'robot@marquiz.ru')
                {
                    echo "Fail sender\n";
                    continue;
                }

                $fields = [];
                $fields['SOURCE_ID'] = 'EMAIL'; //UNCOMMENT

                if($sender == 'noreply@tilda.ws')
                {
                    if (preg_match('~sts\.club~', $haystack)) {
                        $config_block_id = require Yii::getAlias("@modules") . '/parsing_mail/config/config.php';
                        $fields['COMMENTS'] = "";

                        $name = $this->get_string_between($mailContent, 'Name:', "\n");
                        $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Телефон:', "\n"));
                        if($phone == '') {
                            $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Phone:', "\n"));
                        }

                        $number_rooms = $this->get_string_between($mailContent, 'Нужно_комнат:', "\n");
                        $important = $this->get_string_between($mailContent, 'Важно:', "\n");
                        $date =$this->get_string_between($mailContent, 'Дата_покупки:', "\n");
                        $gift = $this->get_string_between($mailContent, 'Подарок:', "\n");
                        $block_id =$this->get_string_between($mailContent, 'Block ID:', "\n");

                        $fields['TITLE'] = "Квиз СТС";
                        $fields['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                        $fields['PHONE'][0]['VALUE'] = mb_substr($fields['PHONE'][0]['VALUE'] ?? '', 0, 11);
                        $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                        $fields['NAME'] = $name;
                        if(isset($config_block_id[$block_id]))
                        {
                            $fields['UF_CRM_1647262108349'] = $config_block_id[$block_id]['object'] ?? '0';
                            $fields['COMMENTS'] .="{$config_block_id[$block_id]['comment']}\n" ?? '';
                        }
                        if($number_rooms != '')
                        {
                            $fields['COMMENTS'] .= "Нужно_комнат: {$number_rooms}\n";
                        }
                        if($important != '')
                        {
                            $fields['COMMENTS'] .= "Важно: {$important}\n";
                        }
                        if($date != '')
                        {
                            $fields['COMMENTS'] .= "Дата_покупки: {$date}\n";
                        }
                        if($gift != '')
                        {
                            $fields['COMMENTS'] .= "Подарок: {$gift}\n";
                        }
                    } else if (preg_match('~pervyi\.site~', $haystack)) {
                        $config_block_id = require Yii::getAlias("@modules") . '/parsing_mail/config/config.php';
                        $fields['COMMENTS'] = "";

                        $name = $this->get_string_between($mailContent, 'Name:', "\n");
                        $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Phone:', "\n"));
                        $date = $this->get_string_between($mailContent, 'Укажите_дату_сдачи_объекта:', "\n");
                        $number_rooms = $this->get_string_between($mailContent, 'Укажите_желаемое_количество_комнат:', "\n");
                        $block_id =$this->get_string_between($mailContent, 'Block ID:', "\n");
                        $request_id = $this->get_string_between($mailContent, 'Request ID:', "\n");
                        //                $utm_source = str_replace('=', '', $this->get_string_between($mailContent, 'utm_source', 'utm_medium'));
                        //                $utm_medium = str_replace('=', '', $this->get_string_between($mailContent, 'utm_medium', 'utm_campaign'));
                        //                $utm_campaign = str_replace('=', '', $this->get_string_between($mailContent, 'utm_campaign', 'utm_content'));
                        //                $utm_content = str_replace('=', '', $this->get_string_between($mailContent, 'utm_content', "\n"));
                        $fields['TITLE'] = "Заявка с Лендинга";
                        $fields['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                        $fields['PHONE'][0]['VALUE'] = mb_substr($fields['PHONE'][0]['VALUE'] ?? '', 0, 11);
                        $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                        $fields['NAME'] = $name;
                        if(isset($config_block_id[$block_id]))
                        {
                            $fields['UF_CRM_1647262108349'] = $config_block_id[$block_id]['object'] ?? '0';
                            $fields['COMMENTS'] .="{$config_block_id[$block_id]['comment']}\n" ?? '';
                        }
                        if($number_rooms != '')
                        {
                            $fields['COMMENTS'] .= "Укажите_желаемое_количество_комнат: {$number_rooms}\n";
                        }
                        if($date != '')
                        {
                            $fields['COMMENTS'] .= "Укажите_дату_сдачи_объекта: {$date}\n";
                        }
                        $fields['SOURCE_ID'] = 'UC_EJN31B';
                    }
                }
                elseif($sender == 'ads.notifications@vk.company')
                {
                    $question = '';
                    $name = $this->get_string_between($mailContent, 'Имя:', "\n");
                    $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Телефон:', "\n"));

                    if(!$html)
                    {
                        $temp_str = explode("\n", strip_tags(html_entity_decode($mailContent)));
                    }
                    else
                    {
                        $temp_str = explode("\n", $mailContent);
                    }

                    $ques = true;
                    foreach ($temp_str as $str)
                    {
                        if(preg_match('~Вопрос:~',$str) && $ques)
                        {
                            $question .= "$str\n";
                            $ques = false;
                        }
                        elseif(preg_match('~Ответ:~',$str) && !$ques)
                        {
                            $question .= "$str\n";
                            $ques = true;
                        }
                    }
                    $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                    $fields_contact['NAME'] = $name;
                    $fields['UF_CRM_1647262108349'] = '0';
                    $fields['TITLE'] = 'Заявка VK Ads';
                    $fields['COMMENTS'] = $question;
                    $fields['SOURCE_ID'] = 'UC_8DE020';
                }
                elseif($sender == 'lidysts@yandex.ru')
                {
                    if(!$html)
                    {
                        $temp_str = explode("\n", strip_tags(html_entity_decode($mailContent)));
                    }
                    else
                    {
                        $temp_str = explode("\n", $mailContent);
                    }
                    $phone = '';
                    $number_rooms = '';
                    $price = '';
                    $district = '';
                    $due_date = '';
                    $rooms_finish = '';
                    $fields['COMMENTS'] = "";

                    foreach ($temp_str as $str)
                    {
                        if(preg_match('~Телефон:~', $str))
                        {
                            $phone = trim(str_replace('Телефон:', '', $str));
                        }
                        elseif(preg_match('~Кол-во комнат~', $str))
                        {
                            //                        $number_rooms = trim(str_replace('Кол-во комнат:', '', $str));
                            $fields['COMMENTS'] .= "$str\n";
                        }
                        elseif(preg_match('~Цена~', $str))
                        {
                            //                        $price = trim(str_replace('Цена:', '', $str));
                            $fields['COMMENTS'] .= "$str\n";
                        }
                        elseif(preg_match('~Район~', $str))
                        {
                            //                        $district = trim(str_replace('Район:', '', $str));
                            $fields['COMMENTS'] .= "$str\n";
                        }
                        elseif(preg_match('~Срок сдачи~', $str))
                        {
                            //                        $due_date = trim(str_replace('Срок сдачи:', '', $str));
                            $fields['COMMENTS'] .= "$str\n";
                        }
                        elseif(preg_match('~Квартира с отделкой~', $str))
                        {
                            //                        $rooms_finish = trim(str_replace('Квартира с отделкой', '', $str));
                            $fields['COMMENTS'] .= "$str\n";
                        }
                    }
                    $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                    $fields['UF_CRM_1647262108349'] = '0';
                    $fields['TITLE'] = 'Заявка с Каталога';
                }
                elseif($sender == 'wordpress@sts57.ru')
                {
                    $fields['COMMENTS'] = "";
                    $name = preg_replace('~;~', '', $this->get_string_between($mailContent, 'Имя -', "\n"));
                    if(preg_match('~Ипотека~', $haystack)) {
                        $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'Телефон -', "\n"));
                        if($phone == '') {
                            $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'телефона -', "\n"));
                        }
                    } elseif(preg_match('~Резервирование~', $haystack)) {
                        $phone = preg_replace('~[^0-9]~', '',$this->get_string_between($mailContent, 'телефона -', ";"));
                        $fields['COMMENTS'] .="Обьект -".$this->get_string_between($mailContent, 'Обьект -', "\n")."\n";
                        $fields['COMMENTS'] .=mb_substr($mailContent, mb_strpos($mailContent, "Ссылка на товар -"))."\n";
                    } else {
                        $phone = preg_replace('~[^0-9]~', '',mb_substr($mailContent, mb_strpos($mailContent, "Телефон -") + 9));
                    }

                    $fields['TITLE'] = "Заявка с Сайта";
                    $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                    $fields_contact['NAME'] = trim($name);
                    $fields['NAME'] = trim($name);
                    if (preg_match('~source=yd~', $mailContent)) {
                        $fields['SOURCE_ID'] = 'UC_UBXYUJ';
                    } else if (preg_match('~Запрос~', $haystack)) {
                        $fields['SOURCE_ID'] = 'UC_HZOJY3';
                    } else if (preg_match('~Старт продаж~', $haystack)) {
                        $fields['SOURCE_ID'] = 'UC_YLJBO8';
                    } else if (preg_match('~Ипотека~', $haystack)) {
                        $fields['SOURCE_ID'] = 'UC_V3G6Y1';
                    } else {
                        $fields['SOURCE_ID'] = 'UC_HZOJY3';
                    }
                }
                elseif($sender == 'robot@marquiz.ru')
                {
                    $mailContent_0 = trim(preg_replace('/<div>/', "\r\n", $mail->textHtml));
                    $mailContent = trim(str_replace(['&nbsp;', '<br>'], "\n",$mailContent_0));
                    $mailContent = strip_tags(html_entity_decode($mailContent));

                    $mailContent = $this->getMail($mailContent);

                    $hasAnswer = false;
                    $waitAnswer = false;
                    foreach ($mailContent as $key => $itemMail) {
                        if (!preg_match('~Имя~', $itemMail)) {
                            unset($mailContent[$key]);
                        } else {
                            break;
                        }
                    }
                    foreach ($mailContent as $key => &$itemMail) {
                        if (preg_match('~Имя~', $itemMail)) {
                            $itemMail = "Имя |";
                        } else if (preg_match('~Телефон~', $itemMail)) {
                            $itemMail = "Телефон |";
                        } else if (preg_match('~Какую квартиру вы ищете\?~', $itemMail)) {
                            $itemMail = "Какую квартиру вы ищете? |";
                        } else if (preg_match('~В каком районе\?~', $itemMail)) {
                            $itemMail = "В каком районе? |";
                        } else if (preg_match('~Какая отделка вас интересует\?~', $itemMail)) {
                            $itemMail = "Какая отделка вас интересует? |";
                        } else if (preg_match('~Как скоро планируете покупку квартиры\?~', $itemMail)) {
                            $itemMail = "Как скоро планируете покупку квартиры? |";
                        } else if (preg_match('~Какой способ оплаты вам подходит\?~', $itemMail)) {
                            $itemMail = "Какой способ оплаты вам подходит? |";
                        }
                    }
                    $mailContent = str_replace("|\n", '', implode("\n", $mailContent));

                    $name = preg_replace('~;~', '', $this->getStringBetween($mailContent, 'Имя ', "\n"));
                    $phone = preg_replace('~[^0-9]~', '',$this->getStringBetween($mailContent, 'Телефон ', "\n"));

                    $fields['UF_CRM_1720492314247'] = $this->getStringBetween($mailContent, 'Какую квартиру вы ищете? ', "\n");
                    $fields['UF_CRM_1720492324469'] = $this->getStringBetween($mailContent, 'В каком районе? ', "\n");
                    $fields['UF_CRM_1720492330297'] = $this->getStringBetween($mailContent, 'Какая отделка вас интересует? ', "\n");
                    $fields['UF_CRM_1720492337308'] = $this->getStringBetween($mailContent, 'Как скоро планируете покупку квартиры? ', "\n");
                    $fields['UF_CRM_1720492343132'] = $this->getStringBetween($mailContent, 'Какой способ оплаты вам подходит? ', "\n");


                    $fields['TITLE'] = "Квиз";
                    $fields_contact['PHONE'] = [['VALUE' => $phone, 'TYPE' => 'WORK']];
                    $fields_contact['NAME'] = trim($name);
                    $fields['NAME'] = trim($name);

                    $fields['SOURCE_ID'] = 'UC_FGCEAY';
                    $fields['TRACE'] = '116';
                }
                else
                {
                    echo "Fail sender\n";
                    continue;
                }


                //UC_8DE020 - Источник “VK Target”
                //UC_V3G6Y1 - Источник “Заявка с сайта - Ипотека”
                //UC_YLJBO8 - Источник “Заявка с сайта - Старт продаж”
                //UC_HZOJY3 - Источник “Заявка с сайта - Запрос”
                //UC_EJN31B - Источник “Заявка с лендинга”
                //UC_UBXYUJ - Источник “Заявка с сайта - Я.Д.”
                $fields['ASSIGNED_BY_ID'] = 10; //UNCOMMENT

                $fields_contact['PHONE'][0]['VALUE'] = mb_substr($fields_contact['PHONE'][0]['VALUE'] ?? '', 0, 11);
                $contact = $this->webhook->request("crm.duplicate.findbycomm", [
                    "entity_type" => "CONTACT",
                    "type" => "PHONE",
                    "values" => [$fields_contact['PHONE'][0]['VALUE']],
                ]);

                if(empty($contact))
                {
                    $contact = $this->createContact($fields_contact);
                    $deal = [];
                }
                else
                {
                    $contact = $contact['CONTACT'][0];
                    $deal = $this->webhook->request("crm.deal.list", ['filter' =>[
//                            'CATEGORY_ID' => 20,
                        'CLOSED' => 'N',
                        'CONTACT_ID' => $contact,
                    ]]);
                }

                $fields['CONTACT_ID'] = $contact;
                $fields['CATEGORY_ID'] = 20;
//                $logger_new->save($contact, $contact, '$contact'); //!!!

                if(!empty($deal))
                {
                    $full_contact = $this->webhook->request("crm.contact.get", ['ID' => $contact]);
//                    $logger_new->save($full_contact, $full_contact, '$full_contact'); //!!!

                    $text = "Поступила новая заявка: {$full_contact['LAST_NAME']} {$full_contact['NAME']} {$fields_contact['PHONE'][0]['VALUE']}";
                    $commands[] = $this->webhook->buildCommand("crm.timeline.comment.add", ['fields' => [
                        "ENTITY_ID" => $deal[0]['ID'],
                        "ENTITY_TYPE" => 'deal',
                        "COMMENT" => $text,
                    ]]);
                    $commands[] = $this->webhook->buildCommand('im.notify.personal.add', [
                        'USER_ID' => $full_contact['ASSIGNED_BY_ID'],
                        'MESSAGE' => "Поступили изменения в <a href='https://stsgrupp.bitrix24.ru/crm/deal/details/{$deal[0]['ID']}/'>{$deal[0]['TITLE']}</a>",
                    ]);
                    $this->webhook->batchRequest($commands);
                    unset($commands);
                }
                else
                {
                    $deal = $this->createDeal($fields);
//                    $logger_new->save($deal, $deal, '$deal'); //!!!
                }
            }
            catch (\Exception $e)
            {
                file_get_contents("https://api.telegram.org/bot1034272956:AAHbDVLSIxmQhiZtbUDl3HLgzfu77KbFByo/sendMessage?chat_id=449614227&text="."Произошла ошибка СТС ГРУПП парсер почты. $id_message - id письма");
            }
            sleep(1);
        }
        die;


        return true;
    }

    function getStringBetween($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return trim(substr($string, $ini, $len));
    }

    public function getMail($mailContent)
    {
        $temp = explode("\r\n", $mailContent);
        $newMail = [];
        foreach ($temp as $item) {
            if(trim($item) != '') {
                $newMail[] = trim($item);
            }
        }
        return $newMail;
    }

}