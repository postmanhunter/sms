<?php
namespace App\Helper;
class CryptHelper{
    public static function setPass($pass)
    {
        $authcode = 'rCt52pF2cnnKNB3Hkp';
        $pass = "###" . md5(md5($authcode . $pass));
        return $pass;
    }
}