<?php

namespace Hudm\AliyunOss;

use Illuminate\Support\ServiceProvider;

class AliyunOssServiceProvider extends ServiceProvider
{
    /**
     * 是否延迟预加载
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // 单例绑定服务
        $this->app->singleton(AliyunOss::class, function () {
            return new AliyunOss();
        });

        $this->app->alias(AliyunOss::class, 'aliyunOss');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/aliyun-oss.php' => config_path('aliyun-oss.php'), // 发布配置文件
        ]);
    }

    public function provides()
    {
        return [AliyunOss::class, 'aliyunOss'];
    }
}
