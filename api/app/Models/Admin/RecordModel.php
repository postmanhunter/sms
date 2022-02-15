<?php

namespace App\Models\Admin;

use App\Models\Model;
use App\Models\Admin\ServiceModel;
use \Exception;

class RecordModel extends Model
{
    protected $table = 'record';

    //禁止create,update自动添加created_at和updated_at
    public $timestamps = false;
    public function __construct()
    {
        parent::__construct($this);
    }
    public function add($data){
        return $this->insertGetId($data);
    }
    public function updateSms($id, $status){
        $this->where('id',$id)->update(['status'=>$status]);
    }
    public static function updateRe($sid,$update){
        self::where('request_id',$sid)->update($update);
    }
    public static function getList($request){
        $data = self::where(function($query) use($request){
            !empty($request->mobile) && $query->where('mobile',$request->mobile);
            !empty($request->status) && $query->where('status',$request->status);
            !empty($request->time[0]) && $query->where('created_at','>=',date('Y-m-d H:i:s',strtotime($request->time[0])));
            !empty($request->time[1]) && $query->where('created_at','<=',date('Y-m-d H:i:s',strtotime($request->time[1])));
        })->orderBy('id','desc')->paginate($request->limit)->toArray(); 
        $service = ServiceModel::getList()->toArray();
        $data1 = [];
        foreach($service as $val){
            $data1[$val['id']] = $val['service_name'];
        }
        // var_dump($data);die;
   
        foreach($data['data'] as &$val){
            $val['service_name'] = $data1[$val['service_id']];
        }
        return $data;
    }
    public static function countNum($send_id) {
        return self::where('send_id',$send_id)->where('status',3)->count();
    }
}