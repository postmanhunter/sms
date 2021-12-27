<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Apis;

class IndexController extends Apis{
    public function index()
    {
        return view('front.index');
    }
    /**
     * 
     */
}