<?php
namespace Githen\LaravelUpload;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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
        // 文件移动
        $this->app->singleton('jiaoyu.move', function (){
            return function ($from, $to){
                $uploadObject = Storage::disk(config('upload.global.filesystem', 'localFile'));
                // 检测文件是否存在
                if (!$uploadObject->exists($from)){
                    return ['code' => 1, 'message' => '上传文件不存在'];
                }

                // 获取文件名称
                $fileInfo = pathinfo($from);

                // 移动文件
                $uploadObject->move($from, $to.'/'.$fileInfo['basename']);

                // 检测是否存在附件
                $thumbPath = $fileInfo['dirname'] . '/';
                $thumbFile = $fileInfo['filename'] .'_'.config('upload.gloabal.thumb', 'thumb').'.'.$fileInfo['extension'];
                if ($uploadObject->exists($thumbPath.$thumbFile)){
                    $uploadObject->move($thumbPath.$thumbFile, $to.'/'.$thumbFile);
                }

                return ['code' => 0, 'message' => '移动成功', 'path' => $to.'/'.$fileInfo['basename']];
            };

        });

        // 请求路由
        Route::middleware('web')->post( 'jiaoyu/upload/{param}', '\Githen\LaravelUpload\Controllers\UploadController@upload')
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
        $this->publishes([__DIR__.'/js/uploadzone.js' => public_path('app-assets/js/scripts/forms/uploadzone.js')]);
    }

}
