<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Apis;


class UploadController extends Apis{
    public function index(){
        try{
            if (request()->hasFile('file')&&request()->file('file')->isValid()) {
                $file=request()->file('file');

                $allowed_extensions = ["xlsx"];

                if (!in_array($file->getClientOriginalExtension(), $allowed_extensions)) {
                    throw new \Exception('请上传xlsx文件');
                } else {
                    $destinationPath = 'storage/uploads/'; //public 文件夹下面建 storage/uploads 文件夹
                    $extension = $file->getClientOriginalExtension();
                    $fileName=md5(time().rand(1, 1000)).'.'.$extension;
                    $file->move($destinationPath, $fileName);
                    $filePath = $destinationPath.$fileName;
                    return $this->response(['url'=>$filePath]);
                }
            } else {
                return '';
            }
        }catch(\Exception $e){
            return $e->getMessage();
        }
        
    }
}
