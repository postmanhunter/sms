<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Apis;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Admin\ServiceModel;

class ServiceController extends Apis{
    
    public function getList(){
        return $this->response(ServiceModel::getList());
    }
    public function addService(ServiceRequest $request){
        if(ServiceModel::addService($request)){
            return $this->response(200000,'操作成功');
        }
        return $this->response(400000,'操作失败');
    }
    public function addParams(ServiceRequest $request){
        if(ServiceModel::addParams($request)){
            return $this->response(200000,'操作成功');
        }
        return $this->response(400000,'操作失败');
    }
    public function getBaseService(){
        return $this->response(ServiceModel::getBaseService());
    }
}
