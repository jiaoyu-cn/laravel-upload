<?php
namespace Githen\LaravelUpload;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * 自动注册服务
 */
class UploadProvider extends ServiceProvider
{
    /**
     * 服务注册
     * @return void
     */
    public function register()
    {
        // 发布文件
        $this->updateFile();
    }

    /**
     * 服务启动
     * @return void
     */
    public function boot()
    {
//        dd(Route::middleware('web'));
//        dd(Route::POST( 'jiaoyu/upload', '\Githen\LaravelUpload\Controllers\UploadController@upload'));
        Route::middleware('web')->post( 'jiaoyu/upload/{param}', '\Githen\LaravelUpload\Controllers\UploadController@upload')
            ->name('jiaoyu.upload'); // 文件上传
        Route::middleware('web')->get( 'jiaoyu/upload/{param}', '\Githen\LaravelUpload\Controllers\UploadController@upload')
            ->name('jiaoyu.upload'); // 文件上传
    }

    /**
     * 发布文件
     * @return void
     */
    private function updateFile()
    {
        // 发布配置文件
        $this->publishes([__DIR__.'/config/upload.php' => config_path('upload.php')]);
//        $this->publishes([__DIR__.'/js/uploadzone.js' => public_path('app-assets/js/scripts/forms/uploadzone.js')]);
    }

}