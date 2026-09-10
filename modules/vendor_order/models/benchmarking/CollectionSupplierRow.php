<?php

namespace app\modules\vendor_order\models\benchmarking;

use Tightenco\Collect\Support\Collection;

class CollectionSupplierRow extends Collection
{
    public function save()
    {
        foreach ($this->items as $row)
        {
            $row->save();
        }

        return true;
    }
}