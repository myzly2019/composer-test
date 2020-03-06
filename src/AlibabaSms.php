<?php


namespace App\Http\Server\Sms\Ali;


use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use App\Models\Log\LogSms;

class AlibabaSms
{
    public $accessKey;
    public $accessSecret;
    public $signName;

    public function __construct()
    {
        $this->accessKey = config('sms.ali.access_key');
        $this->accessSecret = config('sms.ali.access_secret');
        $this->signName = config('sms.ali.sign_name');
    }

    // 发送短信验证码

    /**
     * @param       $phone  int 手机号码
     * @param       $sign_name  string  短信签名
     * @param       $template_code  int 验证码
     * @param array $template_param array   模板参数
     * @param array $extra
     *
     * @return bool|mixed|string
     * @throws ClientException
     */
    public function send_sms_code($phone, $template_code, $template_param = [], $extra = [], $sign_name = '')
    {
        AlibabaCloud::accessKeyClient($this->accessKey, $this->accessSecret)
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $options = [
                'query' => [
                    'RegionId' => "cn-hangzhou",
                    'PhoneNumbers' => $phone,
                    'SignName' => $sign_name ? $sign_name : $this->signName,
                    'TemplateCode' => $template_code,
                    'TemplateParam' => json_encode($template_param, JSON_UNESCAPED_UNICODE),
                ],
            ];

            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options($options)
                ->request();

            $return['code'] = $result['Code'];
            $return['message'] = $result['Message'];

            # 记录短信发送日志
            $this->log($options, $extra, $return);

            if ($result->Code != 'OK') {
                return $return['message'];
            }

            return true;
        } catch (ClientException $e) {
            # 记录短信发送日志
            $this->log($options, $extra, $e->getErrorMessage());
            return $e->getErrorMessage();
        } catch (ServerException $e) {
            # 记录短信发送日志
            $this->log($options, $extra, $e->getErrorMessage());
            return $e->getErrorMessage();
        }
    }

    protected function log($options, $extra, $return)
    {
        $options['query']['TemplateParam'] = json_decode($options['query']['TemplateParam'], true);
        $options['extra'] = $extra;

        LogSms::query()->create([
            'message' => json_encode($options, JSON_UNESCAPED_UNICODE),
            'type' => $extra['type'],
            'return' => json_encode($return, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
