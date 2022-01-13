<?php
namespace App\Http\Sms;

require_once __DIR__.'/../../../vendor/autoload.php';
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Market\V20191010\MarketClient;
use TencentCloud\Market\V20191010\Models\GetUsagePlanUsageAmountRequest;

class Remainnum
{
    public static function query($params)
    {
        // $params = [
        //     'SecretId' => 'AKIDnu113086qmwmgpu4qfqdy4ysim83ghsa2ur',
        //     'SecretKey' => '6mi8m0T3Y47eijo39cCf3lC5nzsbtG66l4U1zI4B',
        //     'InstanceId' => 'market-2sg18mz3o'
        // ]; 
        try {
            $cred = new Credential($params["SecretId"], $params["SecretKey"]);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("market.tencentcloudapi.com");
              
            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new MarketClient($cred, "", $clientProfile);
        
            $req = new GetUsagePlanUsageAmountRequest();
            $params = array(
                'InstanceId' => $params['InstanceId']
            );
            $req->fromJsonString(json_encode($params));
        
            $resp = $client->GetUsagePlanUsageAmount($req);
        
            dd($resp->toJsonString());
        } catch (TencentCloudSDKException $e) {
            dd($e->getMessage());
        }
    }
}
