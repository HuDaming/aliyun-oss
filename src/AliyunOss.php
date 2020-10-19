<?php

namespace Hudm\AliyunOss;

use DateTime;
use Hudm\AliyunOss\Exceptions\AliyunOssException;

class AliyunOss
{
    protected $id;

    protected $key;

    protected $bucketName;

    protected $endpoint;

    protected $host;

    protected $callbackUrl;

    protected $dir;

    /**
     * 设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
     *
     * @var int
     */
    protected $expire;

    public function __construct(array $config = [])
    {
        $this->id = $config['access_key_id'];
        $this->key = $config['access_key_secret'];

        // $host的格式为 bucketName.endpoint，请替换为您的真实信息。
        $this->host = 'http://' . $config['bucket_name'] . '.' . $config['endpoint'];

        $this->callbackUrl = $config['callback_url'];
        $this->dir = $config['dir'];
        $this->expire = $config['expire'];
    }

    public function getUploadPolicy(string $op = 'tmp')
    {
        $callbackParam = [
            'callbackUrl' => $this->callbackUrl,
            'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => "application/x-www-form-urlencoded"
        ];
        $callbackString = json_encode($callbackParam);
        $base64CallbackBody = base64_encode($callbackString);

        $now = time();
        $expire = $this->expire;
        $end = $now + $expire;
        $expiration = $this->gmtIso8601($end);

        // 最大文件大小
        $condition = [0 => 'content-length-range', 1 => 0, 2 => 1048576000];
        $conditions[] = $condition;

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = [0 => 'starts-with', 1 => '$key', 2 => $this->dir];
        $conditions[] = $start;

        $policyParams = ['expiration' => $expiration, 'conditions' => $conditions];
        $policy = json_encode($policyParams);
        $base64Policy = base64_encode($policy);
        $string2Sign = $base64Policy;
        $signature = base64_encode(hash_hmac('sha1', $string2Sign, $this->key, true));

        $response = array();
        $response['access_id'] = $this->id;
        $response['host'] = $this->host;
        $response['policy'] = $base64Policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64CallbackBody;
        $response['dir'] = $this->dir . '/' . $op;  // 这个参数是设置用户上传文件时指定的前缀。

        return $response;
    }

    public function uploadCallback()
    {
        // 1.获取OSS的签名header和公钥url header
        $authorizationBase64 = "";
        $pubKeyUrlBase64 = "";

        /*
         * 注意：如果要使用HTTP_AUTHORIZATION头，你需要先在apache或者nginx中设置rewrite，以apache为例，修改
         * 配置文件/etc/httpd/conf/httpd.conf(以你的apache安装路径为准)，在DirectoryIndex index.php这行下面增加以下两行
            RewriteEngine On
            RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
        */
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL'])) {
            $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
        }

        if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '') {
            throw new AliyunOssException('签名不能为空');
        }

        // 2.获取OSS的签名
        $authorization = base64_decode($authorizationBase64);

        // 3.获取公钥
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);
        if ($pubKey == "") {
            throw new AliyunOssException('公钥获取失败');
        }

        // 4.获取回调body
        $body = file_get_contents('php://input');

        // 5.拼接待签名字符串
        $authStr = '';
        $path = $_SERVER['REQUEST_URI'];
        $pos = strpos($path, '?');
        if ($pos === false) {
            $authStr = urldecode($path) . "\n" . $body;
        } else {
            $authStr = urldecode(substr($path, 0, $pos)) . substr($path, $pos, strlen($path) - $pos) . "\n" . $body;
        }

        // 6.验证签名
        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
        if ($ok == 1) {
            header("Content-Type: application/json");
            return ["Status" => "Ok"];
        } else {
            throw new AliyunOssException('签名验证失败');
        }
    }

    /**
     * 获取 iso8601 格式时间
     *
     * @param $time
     * @return string
     * @throws \Exception
     */
    protected function gmtIso8601($time)
    {
        $dtStr = date("c", $time);
        $myDatetime = new DateTime($dtStr);
        $expiration = $myDatetime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);

        return $expiration . "Z";
    }
}