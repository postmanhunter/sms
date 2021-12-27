<?php

namespace App\Http\Controllers\Admin;
use App\Models\Admin\UserModel;
use App\Http\Controllers\Apis;
use App\Http\Requests\Admin\UserRequest;

class UserController extends Apis{
    public function __construct(){
        
    }
    /**
     * 获取用户信息
     */
    public function getInfo(UserRequest $request){
        $uid = $request->uid;
        return $this->response(UserModel::getUserInfo($uid));
    }
}