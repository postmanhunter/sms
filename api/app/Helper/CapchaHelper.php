<?php

namespace App\helper;

class CapchaHelper{
    /**
     * 获取验证码相关信息
     */
    public static function getCheckStr()
    {
        return app('captcha')->create('default', true);
    }
    /**
     * 验证验证码
     */
    public static function checkCapcha($code,$key)
    {
        return captcha_api_check($code, $key);
    }
}