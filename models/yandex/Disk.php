<?php

namespace app\models\yandex;

use app\models\yandex\client\Request;
use Tightenco\Collect\Support\Collection;

class Disk
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get()
    {
        $response = $this->request->get('disk');

        return $response;
    }

    public function resources(string $path, array $fields = [])
    {
        $response = $this->request->get('disk/resources', ['path' => $path, 'fields' => implode(',', $fields)]);

        return $response;
    }

    public function publish(string $path)
    {
        $response = $this->request->put('disk/resources/publish', ['path' => $path]);

        return $response;
    }

    public function getFiles(array $query = [])
    {
        $listFiles = [];

        if(!isset($query['path'])){
            $query['path'] = '/';
        }

        if(!isset($query['offset'])){
            $query['offset'] = 0;
        }

        ['_embedded' => $response] = $this->request->get('disk/resources', $query);

        $listFiles = array_merge($listFiles, $response['items']);

        if(!empty($response['items']) && $response['offset'] < $response['total']){
            $query['offset'] = $response['offset'] + $response['limit'];
            $listFiles = array_merge($listFiles, $this->getFiles($query));
        }

        return $listFiles;
    }

    public function mkdir(string $path)
    {
        $response = $this->request->put('disk/resources', ['path' => $path]);

        return $response;
    }

    public function rmdir(string $path, bool $permanently = false)
    {
        $response = $this->request->delete('disk/resources', ['path' => $path, 'permanently' => $permanently]);

        return $response;
    }

    public function isExistFile(string $fileName, string $path = '/')
    {
        $filteredFiles = array_filter($this->getFiles(['path' => $path]), function ($item) use($fileName) {
            return $item['name'] == $fileName;
        });

        $filteredFiles = array_values($filteredFiles);

        return isset($filteredFiles[0]);
    }

    public function upload(string $path, string $url)
    {
        $response = $this->request->post('disk/resources/upload', [
            'path' => $path,
            'url' => $url,
        ]);

        return $response;
    }

    public function getStatusUpload(string $operationId)
    {
        $response = $this->request->get("disk/operations/{$operationId}");

        if($response['status'] == 'in-progress'){
            sleep(10);
            return  $this->getStatusUpload($operationId);
        }

        return $response;
    }

    public function download(string $path)
    {
        $response = $this->request->get("disk/resources/download", [
            'path' => $path,
        ]);

        return $response;
    }
}