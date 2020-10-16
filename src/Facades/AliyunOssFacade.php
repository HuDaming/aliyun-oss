<?php

namespace Hudm\AliyunOss\Facades;

use Hudm\AliyunOss\AliyunOss;
use Illuminate\Support\Facades\Facade;

class AliyunOssFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AliyunOss::class;
    }
}