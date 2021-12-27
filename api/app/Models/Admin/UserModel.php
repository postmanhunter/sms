<?php

namespace App\Models\Admin;

use App\Models\Model;

class UserModel extends Model{
   
    protected $table = 'users';
    //禁止create,update自动添加created_at和updated_at
    public $timestamps = false;

    public function __construct(){
        parent::__construct($this);
    }

    // //修改用户名验证方式
    // public function findForPassport($username)
    // {
    //     return $this->where('name', $username)->where('status',1)->first();
    // }

    // //修改passport的秘钥验证
    // public function validateForPassportPasswordGrant($password)
    // {
    //     return CryptHelper::setPass($password) == $this->password;
    // }


    // public function getAuthIdentifier() {
    //     return $this->id;
    // }

    // //token验证字段用户表的ID
    // public function getAuthIdentifierName(){
    //     return 'id';
    // }
    public static function getUserByName($name){
        return self::where('name',$name)->select('id','password')->first();
    }

    public static function getUserInfo($id){
        $userInfo = self::where('id',$id)->select('id','name','avatar','email')->first();
        $userInfo->avatar = env('APP_URL').'/'.$userInfo->avatar;
        return $userInfo;
    }
}