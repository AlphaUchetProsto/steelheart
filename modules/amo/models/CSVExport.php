<?php

namespace app\modules\amo\models;

class CSVExport
{
    private $resource;

    public function __construct()
    {
        $this->resource = fopen(\Yii::getAlias('@app') . '/web' . '/temp/export_notes/' . uniqid() .  '.csv', 'w+');
        $this->put(["ID сделки", "Примечание"]);
    }

    public function put(array $data)
    {
        fputcsv($this->resource, $data);
    }

    public function close()
    {
        fclose($this->resource);
    }
}