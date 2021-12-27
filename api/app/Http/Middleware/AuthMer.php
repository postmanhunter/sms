<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helper\RedisHelper;

class AuthMer
{
    use RedisHelper;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->redis = $this->getRedisInstance();
    
        //获取token
        $token = $request->header('token');
        
        $uid = $this->redis->get('front_'.$token);
        
        if(!$uid){
            throw new \Exception('未授权!请先登陆');
        }
        $request->merge(['mer_id' => $uid]);

        return $next($request);
    }
}
