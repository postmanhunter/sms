<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Apis;


class WebController extends Apis{
    public function getInfo(){
        $data = [
            'upload_url' => env('APP_URL').'/api/upload',
        ];
        return $this->response($data);
    }
}