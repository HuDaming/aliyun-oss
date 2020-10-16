<?php

namespace Hudm\AliyunOss;

class AliyunOss
{
    public function __construct(array $config = [])
    {
    }

    public function getUploadPolicy()
    {
        return 'policy data';
    }

    public function uploadCallback()
    {
        return 'ok';
    }
}