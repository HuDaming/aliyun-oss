<?php

namespace Hudm\AliyunOss;

use DateTime;

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

    public function getUploadPolicy()
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
        $response['dir'] = $this->dir;  // 这个参数是设置用户上传文件时指定的前缀。

        return $response;
    }

    public function uploadCallback()
    {
        return 'ok';
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