<?php

namespace App\Models\Admin;

use App\Models\Model;

class SendModel extends Model
{
    protected $table = 'send_list';

    //禁止create,update自动添加created_at和updated_at
    public $timestamps = false;
    public function __construct()
    {
        parent::__construct($this);
    }
    public static function getList($request){
        $data = self::where(function($query) use($request){
            !empty($request->service) && $query->where('service',$request->service);
            !empty($request->status) && $query->where('status',$request->status);
            !empty($request->time[0]) && $query->where('created_at','>=',date('Y-m-d H:i:s',strtotime($request->time[0])));
            !empty($request->time[1]) && $query->where('created_at','<=',date('Y-m-d H:i:s',strtotime($request->time[1])));
        })->orderBy('id','desc')->paginate($request->limit)->toArray(); 
        
        return $data;
    } 
    public static function add($insert){
        return self::insertGetId($insert);
    }
    public function addOne($id){
        self::where('id',$id)->increment('finish');
    }
}