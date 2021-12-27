<?php
namespace App\Http\Requests\Admin;
use App\Custom\iRequest;

class ServiceRequest extends iRequest
{
    public function check_add_service(){
        return [
            'service_name' => 'required'
        ];
    }
    public function check_add_params(){
        return [
            'params' => 'required',
            'id' => 'required',
        ];
    }
}