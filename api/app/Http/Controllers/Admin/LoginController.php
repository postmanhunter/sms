<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Apis;
use App\Http\Requests\Admin\LoginRequest;
use App\Helper\CryptHelper;
use App\Helper\CreateUniqidHelper;
use App\Models\Admin\UserModel;
use Exception;

class LoginController extends Apis{
    /**
     * 密码授权登录获取token
     * 2019-10-13
     * author hunter
     */
    public function login(LoginRequest $request){
        try{
            $user = UserModel::getUserByName($request->username);
            
            if(!$user){
                throw new Exception('用户不存在'); 
            }
            if($user->password != CryptHelper::setPass($request->password)){
                throw new Exception('密码不正确'); 
            }
            $token = CreateUniqidHelper::getUniqid();
            $ttl = env('LOGIN_TTL') ?? 24 * 3600;
            $key = 'store_user_'.$user->id;
            
            //先判断是否有当前key缓存
            if($this->redis->exists($key)){
                $for_token = $this->redis->get($key);
                $this->redis->del($key);
                $this->redis->del('main_'.$for_token);
            }
            $this->redis->setex('main_'.$token,$ttl,$user->id);
            $this->redis->setex($key,$ttl,$token);
            return $this->response(['token'=>$token]);
        }catch(Exception $e){
            throw new Exception($e->getMessage()); 
        }
        
    }
    /**
     * 刷新已经授权的token
     * 2019-10-13
     * author hunter
     */
    public function refreshToken(LoginRequest $request){
        $http = new \GuzzleHttp\Client();

        $response = $http->post(getenv('APP_URL').'/oauth/token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => 2,
                'client_secret' => 'zB6EZsTIpM8VXCE2DgJex111I5qPDwSvDmxnvhVH',
                'refresh_token' => $request->refresh_token,
                'scope' => '*',
            ],
        ]);
        $data =  json_decode($response->getBody(), true);
        return $this->response($data);
    }
    /**
     * 退出登陆
     */
    public function logOut(LoginRequest $request){
        $uid = $request->uid;
        $key = $key = 'store_user_'.$uid;
        $token = $request->header('token');
        $this->redis->del($key);
        $this->redis->del('main_'.$token);
        return $this->response([]);
    }
}
