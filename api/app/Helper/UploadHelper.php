<?php

namespace App\Helper;
require_once __DIR__ . '/../../vendor/autoload.php';
use OSS\OssClient;
use OSS\Core\OssException;

class UploadHelper
{
    public function index()
    {
        if(!request()->hasFile('file')){
            return '';
        }
        $accessKeyId = config('oss.access_key_id');
        $accessKeySecret = config('oss.access_key_secret');
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = config('oss.endpoint');
        // 设置存储空间名称。
        $bucket= config('oss.bucket');
        // 设置文件名称。
        $microtime = floor(microtime(true)*1000);
        $object = 'download/'.date('Ymd').'/'.$microtime.'_.png';

        
        // <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt。
        $filePath = request()->file('file');

        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

            $ossClient->uploadFile($bucket, $object, $filePath);
            return $object;
        } catch(OssException $e) {
            dd($e->getMessage());
            return;
        }
    }
    public function delete($file)
    {
        if(empty($file)){
            return false;
        }
        $accessKeyId = config('oss.access_key_id');
        $accessKeySecret = config('oss.access_key_secret');
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = config('oss.endpoint');
        // 设置存储空间名称。
        $bucket= config('oss.bucket');
        $object = $file;
        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->deleteObject($bucket, $object);
            return true;
        } catch(OssException $e) {
            return false;
        }
    }
}
