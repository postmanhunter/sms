<?php
namespace App\Http\Requests\Admin;
use App\Custom\iRequest;

class LoginRequest extends iRequest{
    //登录参数验证
    public function check_login(){
        return [
            'username' => 'required',
            'password' => 'required'
        ];
    }
    //刷新访问令牌参数验证
    public function check_refresh_token(){
        return [
            'refresh_token' => 'required|string'
        ];
    }
    /**
     * 商户登陆
     */
    public function check_mer_login(){
        return [
            'username' => 'required|string|max:20',
            'password' => 'required|string|max:20',
            'capcha' => 'required|string|max:10',
            'google' => "max:10",
            'key' => "required|string|max:100"
        ];
    }
    
}
