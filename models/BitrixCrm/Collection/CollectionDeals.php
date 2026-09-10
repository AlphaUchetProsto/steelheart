<?php

namespace app\models\BitrixCrm\Collection;

class CollectionDeals extends BaseCollection
{
    public function __construct($items = [])
    {
        parent::__construct($items);
    }

    public function getContactIds()
    {
        return collect($this->items)->map(function ($item){
            return $item->getFieldValue('CONTACT_ID');
        })->whereNotNull()->values()->toArray();
    }

    public function setContacts(CollectionContacts $contacts)
    {
        foreach ($this->items as &$deal)
        {
            $contact = $contacts->filter(function ($item) use($deal){
                return $item->getId() == $deal->getContactId();
            });

            if($contact->isNotEmpty()) {
                $deal->setContact($contact->values()->get(0));
            }
        }

        return $this;
    }
}
