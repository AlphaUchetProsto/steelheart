<?php

namespace app\modules\processor_post\models\db;

use yii\db\ActiveRecord;

class Deals extends ActiveRecord
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;


    /**
     * @return string название таблицы, сопоставленной с этим ActiveRecord-классом.
     */
    public static function tableName()
    {
        return '{{deals}}';
    }

    public static function getDb()
    {
        // использовать компонент приложения "db2"
        return \Yii::$app->db2;
    }

    public function addRow($items)
    {
        $row = new static();
        $row->id = trim($items[0] ?? '');
        $row->title = trim($items[1] ?? '');
        $row->company = trim($items[2] ?? '');
        $row->contact = trim($items[3] ?? '');
        $row->company_contact = trim($items[4] ?? '');
        $row->assigned_by_id = trim($items[5] ?? '');
        $row->stage_id = trim($items[6] ?? '');
        $row->category = trim($items[7] ?? '');
        $row->budget = trim($items[8] ?? '');
        $row->creation_date = trim($items[9] ?? '');
        $row->created_by = trim($items[10] ?? '');
        $row->changed_date = trim($items[11] ?? '');
        $row->changed_by = trim($items[12] ?? '');
        $row->tags = trim($items[13] ?? '');
        $row->next_task = trim($items[14] ?? '');
        $row->closing_date = trim($items[15] ?? '');
        $row->client_needs = trim($items[16] ?? '');
        $row->only_availability = trim($items[17] ?? '');
        $row->spare_part = trim($items[18] ?? '');
        $row->contract_number = trim($items[19] ?? '');
        $row->account_number = trim($items[20] ?? '');
        $row->timing = trim($items[21] ?? '');
        $row->full_payment = trim($items[22] ?? '');
        $row->prepayment_amount = trim($items[23] ?? '');
        $row->prepayment_date = trim($items[24] ?? '');
        $row->payment_type = trim($items[25] ?? '');
        $row->vat = trim($items[26] ?? '');
        $row->additional_payment_amount = trim($items[27] ?? '');
        $row->additional_payment_date = trim($items[28] ?? '');
        $row->purchase = trim($items[29] ?? '');
        $row->delivery = trim($items[30] ?? '');
        $row->commissions = trim($items[31] ?? '');
        $row->margin = trim($items[32] ?? '');
        $row->minus = trim($items[33] ?? '');
        $row->tc = trim($items[34] ?? '');
        $row->shipment_supplier = trim($items[35] ?? '');
        $row->cargo_code = trim($items[36] ?? '');
        $row->deal_closing = trim($items[37] ?? '');
        $row->deal_source = trim($items[38] ?? '');
        $row->logistician = trim($items[39] ?? '');
        $row->supplier = trim($items[40] ?? '');
        $row->invoice_supplier = trim($items[41] ?? '');
        $row->contry_supplier = trim($items[42] ?? '');
        $row->purchase_date = trim($items[43] ?? '');
        $row->supplier_term = trim($items[44] ?? '');
        $row->system = trim($items[45] ?? '');
        $row->source = trim($items[46] ?? '');
        $row->source_type = trim($items[47] ?? '');
        $row->source_url = trim($items[48] ?? '');
        $row->openstat_source = trim($items[49] ?? '');
        $row->fbclid = trim($items[50] ?? '');
        $row->yclid = trim($items[51] ?? '');
        $row->gclid = trim($items[52] ?? '');
        $row->gclientid = trim($items[53] ?? '');
        $row->from_ = trim($items[54] ?? '');
        $row->openstat_ad = trim($items[55] ?? '');
        $row->utm_term = trim($items[56] ?? '');
        $row->utm_source = trim($items[57] ?? '');
        $row->utm_medium = trim($items[58] ?? '');
        $row->utm_campaign = trim($items[59] ?? '');
        $row->utm_content = trim($items[60] ?? '');
        $row->openstat_campaign = trim($items[61] ?? '');
        $row->utm_referrer = trim($items[62] ?? '');
        $row->_ym_uid = trim($items[63] ?? '');
        $row->_ym_counter = trim($items[64] ?? '');
        $row->roistat = trim($items[65] ?? '');
        $row->referrer = trim($items[66] ?? '');
        $row->openstat_service = trim($items[67] ?? '');
        $row->contact_job = trim($items[68] ?? '');
        $row->contact_work_email = trim($items[69] ?? '');
        $row->contact_personal_email = trim($items[70] ?? '');
        $row->contact_other_email = trim($items[71] ?? '');
        $row->contact_work_phone = trim($items[72] ?? '');
        $row->contact_work_direct_phone = trim($items[73] ?? '');
        $row->contact_mobile_phone = trim($items[74] ?? '');
        $row->contact_fax = trim($items[75] ?? '');
        $row->contact_home_phone = trim($items[76] ?? '');
        $row->contact_other_phone = trim($items[77] ?? '');
        $row->contact_inn_pasport = trim($items[78] ?? '');
        $row->contact_technology = trim($items[79] ?? '');
        $row->contact_address = trim($items[80] ?? '');
        $row->contact_system = trim($items[81] ?? '');
        $row->contact_source = trim($items[82] ?? '');
        $row->contact_source_url = trim($items[83] ?? '');
        $row->contact_source_type = trim($items[84] ?? '');
        $row->note_first = trim($items[85] ?? '');
        $row->note_second = trim($items[86] ?? '');
        $row->note_third = trim($items[87] ?? '');
        $row->note_fourth = trim($items[88] ?? '');
        $row->note_fifth = trim($items[89] ?? '');
        $row->save();
    }

}