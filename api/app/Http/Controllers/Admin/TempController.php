<?php
namespace App\Http\Controllers\Admin;
use App\User;
use App\Models\Admin\TempModel;
use App\Http\Controllers\Apis;
use App\Http\Requests\Admin\TempRequest;

class TempController extends Apis{
    public function __construct(){
        
    }
    /**
     * 获取用户信息
     */
    public function getTempList(TempRequest $request){
        return $this->response(TempModel::getTempList($request));
    }
    /**
     * 添加或者更新数据
     */
    public function addOrUpdate(TempRequest $request){
        if(TempModel::addOrUpdate($request)){
            return $this->response(200000,'操作成功');
        }
        return $this->response(400000,'操作失败');
    }
    /**
     * 删除数据
     */
    public function delTemp(TempRequest $request){
        if(TempModel::delTemp($request->id)){
            return $this->response(200000,'操作成功');
        }
        return $this->response(400000,'操作失败');
    }
}