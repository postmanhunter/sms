<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Apis;
use App\Http\Requests\Admin\SendRequest;
use App\Http\Sms\Qiniu;
use App\Models\Admin\ServiceModel;
use App\Models\Admin\TempModel;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Helper\RabbitmqHelper;
use App\Models\Admin\RecordModel;

class SendController extends Apis
{
    public function __construct()
    {
    }
    public function send(SendRequest $request){
        $file = $request->file;
        // $file = 'storage/uploads/664bdb3db075d6a89ede207b2c32f3bf.xlsx';
        $public = public_path();
        $path = $public.'/'.$file;

        $reader = new Xlsx();
        $spreadsheet = $reader->load($path);
    
        //需要发送的人的信息
        $sheetData = $spreadsheet->getActiveSheet()->ToArray();
        // dd($sheetData);
        $temp = 1;
        $service_params = ServiceModel::getService($request->service_id);
        $param = json_decode($service_params->params,true);
       
        //获取模板参数
        $temp_params = TempModel::getTemp($request->temp_id);
        $temp_param = json_decode($temp_params->param,true);
    
        $params = [];
        foreach($param as $val){
            $val1 = json_decode($val,true);
            $params[$val1['title']] = $val1['value'];
        }
        foreach($temp_param as $v){
            $v1 = json_decode($v,true);
            $params['temp_params'][] = $v1['title'];
        }
        $params['temp_id'] = $request->temp_id;
        $params['service_id'] = $service_params->id;
        $class = '';
        switch($request->service_id){
            //七牛云
            case 1:
                $class = new Qiniu(); 
                break;
            default;
                $temp =-1;
        }
        if($temp == -1){
            return $this->response(400000,'当前服务商还未对接');
        }else{
            $rabbitInstance = RabbitmqHelper::getInstance(); 
            $delay = 1;
            foreach($sheetData as $v_data){
                $data = [
                    'message' =>$v_data,
                    'params' =>$params,
                ];
                $rabbitInstance->pushDelayMsg($data,'sms_push',$delay);
                $delay += $request->time;
            }
           return $this->response([]);
        }
    }
}