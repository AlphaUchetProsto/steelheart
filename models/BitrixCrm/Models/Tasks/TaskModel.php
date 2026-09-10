<?php

namespace app\models\BitrixCrm\Models\Tasks;

use app\models\BitrixCrm\Models\BaseModel;
use Tightenco\Collect\Support\Collection;
use function Symfony\Component\String\u;

class TaskModel extends BaseModel
{
    public function getResponsibleId()
    {
        return $this->fields->get('responsible')['id'];
    }

    public function getResponsibleName()
    {
        return $this->fields->get('responsible')['name'];
    }

    public function getResponsibleLink()
    {
        return $this->fields->get('responsible')['link'];
    }

    public function getResponsibleIcon()
    {
        return $this->fields->get('responsible')['icon'];
    }

    public function getCreatorId()
    {
        return $this->fields->get('creator')['id'];
    }

    public function getCreatorName()
    {
        return $this->fields->get('creator')['name'];
    }

    public function getCreatorLink()
    {
        return $this->fields->get('creator')['link'];
    }

    public function getCreatorIcon()
    {
        return $this->fields->get('creator')['icon'];
    }

    public function getLink()
    {
        return '/workgroups/group/' . $this->fields->get('groupId') . '/tasks/task/view/' . $this->fields->get('id') . '/';
    }

    public function getUfCrmId(string $entityTypeCode)
    {
        $collectionEntity = collect($this->getFieldValue('ufCrmTask'))->filter(function ($item) use($entityTypeCode){
            return u($item)->containsAny("{$entityTypeCode}_");
        });

        if($collectionEntity->isNotEmpty())
        {
            $collectionEntity = $collectionEntity->map(function ($item) {
                return u($item)->after('_')->toString();
            });
        }

        return  $collectionEntity->toArray();
    }

    public function getTitle()
    {
        $crmItems = $this->getUfCrmId('CO');

        if(!empty($crmItems))
        {
            return u($this->getFieldValue('title'))->after(':')->trim()->toString();
        }

        return $this->getFieldValue('title');
    }
}