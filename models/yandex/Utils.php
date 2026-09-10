<?php

namespace app\models\yandex;

use Tightenco\Collect\Support\Collection;

class Utils
{
    public function getFiles(string $dir, bool $recursive = true, bool $include_folders = false )
    {
        $files = [];

        if(!is_dir($dir)){
            return $files;
        }

        $dir = rtrim( $dir, '/\\' );

        foreach( glob( "$dir/{,.}[!.,!..]*", GLOB_BRACE ) as $file )
        {
            if(is_dir($file) ){
                if($include_folders)
                {
                    $files[] = $file;
                }

                if($recursive){
                    $files = array_merge($files, $this->getFiles($file, $recursive, $include_folders));
                }
            } else {
                $files[] = $file;
            }
        }

        return $files;
    }

    public function createArchive(string $fileName, string $dir = null)
    {
        if(is_null($dir)){
            $dir = \Yii::getAlias('@app');
        }

        $files = $this->getFiles($dir);
        $nameMainFolder = mb_substr($dir, mb_strripos($dir, '/'));

        $zip = new  \ZipArchive();
        $zip->open($fileName, (\ZipArchive::CREATE | \ZipArchive::OVERWRITE));

        foreach ($files as $file)
        {
            if(mb_strpos($file, '/logs/') === false){
                $pathWithoutMainFolder = mb_substr($file, mb_strpos($file, $nameMainFolder) + mb_strlen($nameMainFolder) + 1);
                $zip->addFile($file, $pathWithoutMainFolder);
            }
        }

        $zip->close();
    }
}