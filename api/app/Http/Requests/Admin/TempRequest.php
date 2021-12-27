<?php
namespace App\Http\Requests\Admin;
use App\Custom\iRequest;

class TempRequest extends iRequest{
    public function check_add_or_update_temp(){
        return [
            'temp_id' => "required",
            'temp_content' => "required"
        ];
    }
    public function check_del_temp(){
        return [
            "id" => "required|integer"
        ];
    }
}