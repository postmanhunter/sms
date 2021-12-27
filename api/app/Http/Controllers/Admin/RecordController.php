<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Apis;
use App\Http\Requests\Admin\RecordRequest;
use App\Models\Admin\RecordModel;

class RecordController extends Apis
{
    public function __construct()
    {
    }
    public function getList(RecordRequest $request){
        return $this->response(RecordModel::getList($request));
    }
}