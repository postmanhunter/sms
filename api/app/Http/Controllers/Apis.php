<?php

namespace App\Http\Controllers;


use App\Custom\iCommon;
use App\Custom\iRequest;
use App\Custom\iResponse;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Helper\RedisHelper;
use App\Helper\ClientIpHelper;

class Apis extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, iResponse, ValidatesRequests, iCommon,RedisHelper;


    protected $defaultModel;

    public function __construct(Request $request)
    {
        $this->redis = $this->getRedisInstance();

    }


    /**
     * Load Helper
     *
     * This function loads the specified helper file.
     *
     * @param mixed
     * @return    void
     */
    public function helper($helpers = array())
    {
        $arrFiles = is_array($helpers) ?: explode(",", $helpers);

        foreach ($arrFiles as $key => $value) {
            $ext_helper = public_path("helpers/" . $value) . "_helper.php";
            if (file_exists($ext_helper)) {
                include_once($ext_helper);
            }
        }
        return true;
    }
    // 数组缓存方法，默认缓存3秒
    public function cacheArray($key, $fn, $ttl = 3)
    {
        $cacheKey = 'cache:'.$key;
        $redis = $this->redis;
        $res = $redis->get($cacheKey);
        if ($res) {
            $res = json_decode($res, true);
        } else {
            $res = $fn();
            $redis->set($cacheKey, json_encode($res));
            $redis->expire($cacheKey, $ttl);
        }

        return $res;
    }

    /**
     * 将谷歌验证 加入通用方法
     *
     * 对谷歌验证码进行验证
     * 如验证不正常 直接抛出错误
     */
    public function authorize($verify, $google_code)
    {

        $checkGoogle = iRequest::CheckGoogleAuthenticator($verify, $google_code);
        if (false === $checkGoogle) {
            return false;
        }
        return $checkGoogle;

    }
    /**
     * 获取访问ip
     */
    protected static function getIP()
    {
        ClientIpHelper::config(['proxyIPs' => true]);
        return ClientIpHelper::get();
    }
}
