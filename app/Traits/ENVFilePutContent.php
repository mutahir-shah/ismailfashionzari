<?php
namespace App\Traits;

trait ENVFilePutContent{

    public function dataWriteInENVFile($key, $value)
    {
        $path = app()->environmentFilePath();
        $content = file_get_contents($path);

        if ($key === 'DB_PASSWORD') {
            $value = '"' . $value . '"';
        }

        $pattern = "/^{$key}=.*$/m";
        $replace = $key . '=' . $value;

        $content = preg_replace($pattern, $replace, $content);

        file_put_contents($path, $content);
    }

}
