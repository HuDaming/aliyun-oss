<?php

namespace Hudm\AliyunOss\Tests;

use Hudm\AliyunOss\AliyunOss;
use PHPUnit\Framework\TestCase;

class AliyunOssTest extends TestCase
{
    public function testGetUploadPolicy()
    {
        $oss = new AliyunOss([
            'access_key_id' => 'test',
            'access_key_secret' => 'test',
            'bucket_name' => 'test',
            'endpoint' => 'test',
            'callback_url' => 'test',
            'dir' => 'test',
            'expire' => 30
        ]);

        $result = $oss->getUploadPolicy();

        $this->assertIsArray($result, 'Fail to assert getUploadPolicy.');
    }
}