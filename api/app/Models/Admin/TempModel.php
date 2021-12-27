<?php

namespace App\Models\Admin;

use App\Models\Model;
use App\Models\Admin\ServiceModel;
use \Exception;

class TempModel extends Model{
    protected $table = 'temp';

    //禁止create,update自动添加created_at和updated_at
    public $timestamps = false;
    public function __construct(){
        parent::__construct($this);
    }

    /**
     * 获取数据列表
     */
    public static function getTempList($request){
        $Temp = self::where(function($query) use($request){
            !empty($request->service_id) && $query->where("service_id",$request->service_id);
        })->get()->toArray();
        $service = ServiceModel::getList()->toArray();
        $data = [];
        foreach($service as $val){
            $data[$val['id']] = $val['service_name'];
        }
        
       foreach($Temp as &$v){
            $v['params'] = json_decode($v['param'],true);
            $v['service_name'] = $data[$v['service_id']];
       }    
        return $Temp;
    }
    /**
     * 添加或者更细数据
     */
    public static function addOrUpdate($request){
        $Temp = [
            'temp_id' => $request->temp_id,
            'temp_content' => $request->temp_content,
            'service_id' => $request->service_id,
            'param' => json_encode($request->params)
        ];
        if ($request->id) {
            if(self::where('temp_id',$request->temp_id)->where('id','!=',$request->id)->exists()){
                throw new \Exception('当前模板id已经存在');
            }
            return self::where('id',$request->id)->update($Temp);
        }else{
            if(self::where('temp_id',$request->temp_id)->exists()){
                throw new \Exception('当前模板id已经存在');
            }
            $Temp['created_at'] = date('Y-m-d H:i:s');
            return self::insert($Temp);
        }
    }
    /**
     * 删除
     */
    public static function delTemp($id){
        return self::where('id',$id)->delete();
    }
    public static function getTemp($temp_id){
        return self::where('temp_id',$temp_id)->first();
    }
}