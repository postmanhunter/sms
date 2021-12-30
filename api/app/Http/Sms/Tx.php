<?php
namespace App\Http\Sms;
require_once __DIR__.'/../../../vendor/autoload.php';
// 导入对应产品模块的client
use TencentCloud\Sms\V20210111\SmsClient;
// 导入要请求接口对应的Request类
use TencentCloud\Sms\V20210111\Models\SendSmsRequest;
use TencentCloud\Sms\V20210111\Models\PullSmsSendStatusRequest;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Credential;
// 导入可选配置类
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;

class Tx{
    public function send($message,$params){
        try {
            /* 必要步骤：
             * 实例化一个认证对象，入参需要传入腾讯云账户密钥对secretId，secretKey。
             * 这里采用的是从环境变量读取的方式，需要在环境变量中先设置这两个值。
             * 你也可以直接在代码中写死密钥对，但是小心不要将代码复制、上传或者分享给他人，
             * 以免泄露密钥对危及你的财产安全。
             * CAM密匙查询: https://console.cloud.tencent.com/cam/capi*/
            // dd($params);
            $cred = new Credential($params['SecretId'], $params['SecretKey']);
        
            $client = new SmsClient($cred, "ap-guangzhou");
            // 实例化一个 sms 发送短信请求对象,每个接口都会对应一个request对象。
            $req = new SendSmsRequest();
        
            /* 填充请求参数,这里request对象的成员变量即对应接口的入参
             * 你可以通过官网接口文档或跳转到request对象的定义处查看请求参数的定义
             * 基本类型的设置:
             * 帮助链接：
             * 短信控制台: https://console.cloud.tencent.com/smsv2
             * sms helper: https://cloud.tencent.com/document/product/382/3773 */
        
            /* 短信应用ID: 短信SdkAppId在 [短信控制台] 添加应用后生成的实际SdkAppId，示例如1400006666 */
            $req->SmsSdkAppId = $params['SmsSdkAppId'];
            /* 短信签名内容: 使用 UTF-8 编码，必须填写已审核通过的签名，签名信息可登录 [短信控制台] 查看 */
            $req->SignName = $params['sign'];
            /* 短信码号扩展号: 默认未开通，如需开通请联系 [sms helper] */
            $req->ExtendCode = "";
            /* 下发手机号码，采用 E.164 标准，+[国家或地区码][手机号]
             * 示例如：+8613711112222， 其中前面有一个+号 ，86为国家码，13711112222为手机号，最多不要超过200个手机号*/
            $mobile = '+86'.trim($message[0]);
            $req->PhoneNumberSet = array($mobile);

            /* 国际/港澳台短信 SenderId: 国内短信填空，默认未开通，如需开通请联系 [sms helper] */
            $req->SenderId = "";
            /* 用户的 session 内容: 可以携带用户侧 ID 等上下文信息，server 会原样返回 */
            $req->SessionContext = "";
            /* 模板 ID: 必须填写已审核通过的模板 ID。模板ID可登录 [短信控制台] 查看 */
            $req->TemplateId = $params['temp_id'];
            /* 模板参数: 若无模板参数，则设置为空*/
            $paramters = [];
            $index = 1;
            foreach($params['temp_params'] as $v){
                $paramters[] = (string)$message[$index++];
            }
            $req->TemplateParamSet = $paramters;
        
        
            // 通过client对象调用SendSms方法发起请求。注意请求方法名与请求对象是对应的
            // 返回的resp是一个SendSmsResponse类的实例，与请求对象对应
            $resp = $client->SendSms($req);
            // 输出json格式的字符串回包
            $data =  json_decode($resp->toJsonString(),true);
            if($data['SendStatusSet'][0]['Code']==='Ok'){
                return [[
                    'status' => 1,
                    'message' => '',
                    'RequestId' => $data['RequestId']
                ],1];
            }else{
                return [[
                    'status' => 2,
                    'message' => $data['SendStatusSet'][0]['Message'],
                    'RequestId' => $data['RequestId']
                ],1];
            }
        }
        catch(TencentCloudSDKException $e) {
            
            return [[
                'status' => 2,
                'message' => $e->getMessage(),
                'RequestId' => ''
            ],1];
        }
    }
}