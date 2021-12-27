<?php
namespace App\Helper;

class UploadHelper{
    /**
     * layim文件上传
     */
    public function index(){
        if(request()->hasFile('file')&&request()->file('file')->isValid()){
            $file=request()->file('file');

            $allowed_extensions = ["png", "jpg", "gif",'jpeg'];

            if (!in_array($file->getClientOriginalExtension(), $allowed_extensions)) {
                throw new \Exception('不允许上次该类型图片');
            }else{
                $destinationPath = 'storage/uploads/'; //public 文件夹下面建 storage/uploads 文件夹
                $extension = $file->getClientOriginalExtension();
                $fileName=md5(time().rand(1,1000)).'.'.$extension;
                $file->move($destinationPath,$fileName);
                $filePath = $destinationPath.$fileName;
                return $filePath;
            }
        }else{
            return '';
        }
    }
    public function delete($url){
        
    }
}