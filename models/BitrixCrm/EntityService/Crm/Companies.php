<?php

namespace app\models\BitrixCrm\EntityService\Crm;

use app\models\BitrixCrm\Client\Client;
use app\models\BitrixCrm\Collection\BaseCollection;
use app\models\BitrixCrm\Collection\CollectionCompanies;
use app\models\BitrixCrm\Models\Crm\CompanyModel;
use app\models\BitrixCrm\Models\Crm\ContactModel;
use app\models\BitrixCrm\Models\BaseModel;
use Tightenco\Collect\Support\Collection;
use app\models\BitrixCrm\EntityService\BaseService;

class Companies extends BaseService
{
    protected $collectionClass = BaseCollection::class;
    protected $itemClass = BaseModel::class;

    public function get(int $id)
    {
        $response = $this->request->request('crm.company.get', ['ID' => $id]);

        return new $this->itemClass($response->getResponse());
    }

    public function getOneWithCompany(int $id, BaseModel $contactModel = null)
    {
        if(is_null($contactModel)){
            $contactModel = new ContactModel();
        }

        $commands['get_company'] = $this->request->buildCommand('crm.company.get', ['ID' => $id]);
        $commands['get_contacts'] = $this->request->buildCommand('crm.contact.list', ['filter' => ['COMPANY_ID' => '$result[get_company][ID]']]);

        ['result' => $data] = $this->request->batchRequest($commands);

        $contactServiceModel = (new Contacts())->setItemModel($contactModel);

        $companyModel = new $this->itemClass;
        $companyModel->setContacts($contactServiceModel->createCollection([]));

        if(!empty($data['get_contacts'])){
            $companyModel->setContacts($contactServiceModel->createCollection($data['get_contacts']));
        }

        return $companyModel->fromArray($data['get_company']);
    }

    public function getFields()
    {
        return $this->request->request('crm.company.fields');
    }

    public function list(array $params = [])
    {
        $response = $this->request->request('crm.company.list', $params);

        return $this->createCollection($response->getResponse());
    }

    public function getByPhone(string $phone)
    {
        $class = $this->itemClass;
        $contactModel = new $class;

        $phone = preg_replace('/[^0-9]/', '', $phone);

        $data = $this->request->request('crm.duplicate.findbycomm', [
            'type' => 'PHONE',
            'values' => [$phone],
            'entity_type' => 'COMPANY',
        ]);

        if(!empty($data)){
            return $this->getOne($data['COMPANY'][0]);
        }

        return $contactModel;
    }

    public function getByEmail(string $email)
    {
        $class = $this->itemClass;
        $contactModel = new $class;

        $data = $this->request->request('crm.duplicate.findbycomm', [
            'type' => 'EMAIL',
            'values' => [$email],
            'entity_type' => 'COMPANY',
        ]);

        if(!empty($data)){
            return $this->getOne($data['COMPANY'][0]);
        }

        return $contactModel;
    }

    public function update(BaseModel $company)
    {
        $client = new Client();

        return $client->api()->request('crm.company.update', ['ID' => $company->id, 'fields' => $company->collectFieldValue()]);
    }

    public function multipleUpdate(CollectionCompanies $collectionCompanies)
    {
        $commands = new Collection();
        $client = new Client();

        foreach ($collectionCompanies as $company){
            $commands->push($client->api()->buildCommand('crm.company.update', ['ID' => $company->id, 'fields' => $company->collectFieldValue()]));
        }

        return $client->api()->batchRequest($commands->toArray());
    }
}
