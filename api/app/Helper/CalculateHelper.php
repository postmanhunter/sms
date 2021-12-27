<?php

namespace App\helper;

class CalculateHelper{
    /**
     * 两数除法取两位小数
     */
    public static function dividerKeepTow($num, $dividered)
    {
        if ($dividered == 0) {
            return 100;
        }
        if ($num == 0) {
            return 0;
        }
        return ((int)($num/$dividered*100));
    }   
}