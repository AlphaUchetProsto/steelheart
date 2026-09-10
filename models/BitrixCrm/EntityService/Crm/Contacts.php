<?php

namespace app\models\BitrixCrm\EntityService\Crm;

use app\models\BitrixCrm\EntityService;

use app\models\BitrixCrm\Client\Client;
use app\models\BitrixCrm\Collection\CollectionContacts;
use app\models\BitrixCrm\Models\Crm\ContactModel;
use app\models\BitrixCrm\Models\BaseModel;
use Tightenco\Collect\Support\Collection;

class Contacts extends EntityService\BaseService
{
    protected $collectionClass = CollectionContacts::class;
    protected $itemClass = ContactModel::class;

    public function getOne(int $id)
    {
        $response = $this->request->request('crm.contact.get', ['ID' => $id]);

        return new $this->itemClass($response->getResponse());
    }

    public function getFields()
    {
        return $this->request->request('crm.contact.fields')->getResponse();
    }

    public function getList(array $params = [])
    {
        $response = $this->request->request('crm.contact.list', $params);
        
        return $this->createCollection($response->getResponse());
    }

    public function getByPhone(string $phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $response = $this->request->request('crm.duplicate.findbycomm', [
            'type' => 'PHONE',
            'values' => [$phone],
            'entity_type' => 'CONTACT',
        ]);

        if(!empty($response->getResponse())){
            return $this->getOne($response->getResponse()['CONTACT'][0]);
        }

        return null;
    }

    public function getByEmail(string $email)
    {
        $response = $this->request->request('crm.duplicate.findbycomm', [
            'type' => 'EMAIL',
            'values' => [$email],
            'entity_type' => 'CONTACT',
        ]);

        if(!empty($response->getResponse())){
            return $this->getOne($response->getResponse()['CONTACT'][0]);
        }

        return null;
    }

    public function add(ContactModel $contact)
    {
        if ($contact->getPhones()) {
            $commands['find_duplicate_phone'] = $this->request->buildCommand('crm.duplicate.findbycomm', [
                'type' => 'PHONE',
                'values' => $contact->getPhones(),
                'entity_type' => 'CONTACT',
            ]);
        }

        if ($contact->getEmails()) {
            $commands['find_duplicate_emails'] = $this->request->buildCommand('crm.duplicate.findbycomm', [
                'type' => 'EMAIL',
                'values' => $contact->getEmails(),
                'entity_type' => 'CONTACT',
            ]);
        }

        if (isset($commands)) {
            $response = $this->request->batchRequest($commands)->getResponse();
            $response = collect($response)->flatten(2);

            if ($response->isNotEmpty()) {
                $existContact = $this->getOne($response->get(0));
                $existContact->merge($contact);

                $this->update($existContact);

                return $existContact;
            }
        }

        $contact->setFieldValue('ID', $this->request->request('crm.contact.add', ['fields' => $contact->getFields()])->getResponse());

        return $contact;
    }

    public function update(BaseModel $contact)
    {
        return $this->request->request('crm.contact.update', ['ID' => $contact->getFieldValue('ID'), 'fields' => $contact->getFields()]);
    }

    public function multipleUpdate(CollectionContacts $collectionContacts)
    {
        $commands = new Collection();
        $client = new Client();

        foreach ($collectionContacts as $contact){
            $commands->push($client->api()->buildCommand('crm.contact.update', ['ID' => $contact->id, 'fields' => $contact->collectFieldValue()]));
        }

        return $client->api()->batchRequest($commands->toArray());
    }
}
