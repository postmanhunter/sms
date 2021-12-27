<?php

namespace App\Helper;
use Ramsey\Uuid\Uuid;

class CreateUniqidHelper{
    public static function getUniqid($length=0){
        $uuid4 = Uuid::uuid4();
        $uuid_string = explode('-', $uuid4->toString());

        $uniqid = "{$uuid_string[0]}{$uuid_string[1]}{$uuid_string[2]}{$uuid_string[3]}";
        if ($length) {
            return mb_substr($uniqid,0,$length);
        }
        return $uniqid;
    }
}