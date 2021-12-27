<?php

namespace App\Models\Admin;

use App\Models\Model;

class ServiceModel extends Model
{
    protected $table = 'service';
    //禁止create,update自动添加created_at和updated_at
    public $timestamps = false;

    public function __construct()
    {
        parent::__construct($this);
    }
    public static function getList(){
        $data = self::get();
        if($data){
            foreach($data as &$val){
                !empty($val['params']) && $val['params'] = json_decode($val['params']);
            }
        }
        return $data;
    }
    public static function addService($request){
        $data = [
            'service_name'=>$request->service_name,
            'created_at' => date('Y-m-d H:i:s')
        ];
        return self::insert($data);
    }
    public static function addParams($request){
        $data = [
            'params'=>json_encode($request->params),
        ];
        return self::where('id',$request->id)->update($data);
    }
    public static function getService($id){
        return self::where('id',$id)->first();
    }
}