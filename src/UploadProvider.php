<?php
namespace Githen\LaravelUpload;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Facades\Image;

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
        // 注册本身为上传实例
        $this->app->singleton('jiaoyu.upload', function (){
            return $this;
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

    /**
     * 文件移动
     * @param $from
     * @param $to
     * @return array
     */
    public function move($from, $to):array
    {
        $uploadObject = Storage::disk(config('upload.global.filesystem', 'localFile'));
        // 检测文件是否存在
        if (!$uploadObject->exists($from)){
            return $this->message(1, '上传文件不存在');
        }

        // 获取文件名称
        $fileInfo = pathinfo($from);

        // 移动文件
        $uploadObject->move($from, $to.'/'.$fileInfo['basename']);

        // 检测是否存在附件
        $thumbPath = $fileInfo['dirname'] . '/';
        $thumbFile = $fileInfo['filename'] .'_'.config('upload.global.thumb', 'thumb').'.'.$fileInfo['extension'];
        if ($uploadObject->exists($thumbPath.$thumbFile)){
            $uploadObject->move($thumbPath.$thumbFile, $to.'/'.$thumbFile);
        }

        return ['code' => 0, 'message' => '移动成功', 'path' => $to.'/'.$fileInfo['basename']];
    }

    /**
     * 删除文件
     * @param $file
     * @return array
     */
    public function delete($file):array
    {
        $uploadObject = Storage::disk(config('upload.global.filesystem', 'localFile'));
        // 检测文件是否存在
        if ($uploadObject->exists($file)){
            $uploadObject->delete($file);
        }

        // 获取文件名称
        $fileInfo = pathinfo($file);

        // 检测是否存在附件
        $thumbPath = $fileInfo['dirname'] . '/';
        $thumbFile = $fileInfo['filename'] .'_'.config('upload.global.thumb', 'thumb').'.'.$fileInfo['extension'];
        if ($uploadObject->exists($thumbPath.$thumbFile)){
            $uploadObject->delete($thumbPath.$thumbFile);
        }

        return $this->message(0, '删除成功');
    }

    /**
     * 上传文件
     * @param $param
     * @return array
     */
    public function upload($param)
    {
        $request =  app('request');

        // 获取上传文件配置类型
        if (!$config = config('upload.'.$param, [])){
            return $this->message(1, "获取上传文件配置失败：".$param);
        }

        // 检测类型及大小
        $uploadFile = $request->file(config('upload.global.name'));
        if (!$uploadFile->isValid()){
            return $this->message(1, '上传失败');
        }

        $extendsion = strtolower($uploadFile->getClientOriginalExtension());
        if (!empty($config['ext']) && !in_array('.'.$extendsion, $config['ext'])){
            return $this->message(1, '不支持的上传类型'.$extendsion);
        }
        $tmpSize = $request->input('dztotalfilesize', $request->input('size'));
        if ($tmpSize > $config['size'] * 1048576){
            return $this->message(1, '文件大小超过上限：'. $config['size'].'MB');
        }

        // 分片上传中
        $tmpUid = $request->input('dzuuid', $request->input('uuid', microtime(true).rand(0, 1000)));
        $fileName = md5($tmpUid).'.'.$extendsion;

        // 检测存放目录
        $pathType = $request->input('is_tmp', false) ? 'tmp' : 'path';
        $path = ($config[$pathType] ?? config('upload.global.'.$pathType));

        // 初始化
        $uploadObject = Storage::disk(config('upload.global.filesystem','localFile'));
        if (!$uploadObject->exists($path)){
            $uploadObject->putFileAs($path, $request->file(config('upload.global.name')), $fileName, 'public');
        }else{
            file_put_contents($uploadObject->path($path.'/'.$fileName),
                $request->file(config('upload.global.name'))->get(), FILE_APPEND);
        }

        $data = [];
        $tmpChunks =  $request->input('dztotalchunkcount', $request->input('chunks', 1));
        $tmpChunkIndex =  $request->input('dzchunkindex', $request->input('chunk', 0));
        if ($tmpChunkIndex == $tmpChunks - 1) {
            $data = [
                'path' => '/'.trim($path.'/'.$fileName, DIRECTORY_SEPARATOR),
                'url' =>$uploadObject->url($path.'/'.$fileName),
                'filename' => $uploadFile->getClientOriginalName(),
                'uid' => $tmpUid,
            ];
            // 图片类型生成缩略图
            if(Validator::make(['file' => new File($uploadObject->path($path.'/'.$fileName))], ['file' => 'image'])->passes()){
                $memoryLimit = config('upload.global.memory_limit');
                if (!empty($memoryLimit)){
                    ini_set('memory_limit', $memoryLimit);
                }
                // 原图压缩
                if (isset($config['resize']) && count($config['resize']) == 2){
                    Image::make($uploadObject->path($path.'/'.$fileName))->resize($config['resize'][0], $config['resize'][1], function ($constraint){
                        $constraint->aspectRatio();   // 按比例调整图片大小
                        $constraint->upsize(); // 这里如果宽度不足 时，保持原来尺寸
                    })->save($uploadObject->path($path.'/'.$fileName));
                }

                // 生成缩略图
                if (isset($config['thumb_resize']) && count($config['thumb_resize']) == 2){
                    $fileNameThumb = md5($request->input('dzuuid')).'_'.config('upload.global.thumb', 'thumb').'.'.$extendsion;
                    Image::make($uploadObject->path($path.'/'.$fileName))->resize($config['thumb_resize'][0], $config['thumb_resize'][1], function ($constraint){
                        $constraint->aspectRatio();   // 按比例调整图片大小
                        $constraint->upsize(); // 这里如果宽度不足 200 时，保持原来尺寸
                    })->save($uploadObject->path($path.'/'.$fileNameThumb));
                    $data['path_'.config('upload.global.thumb', 'thumb')] = '/'.trim($path.'/'.$fileNameThumb, DIRECTORY_SEPARATOR);
                    $data['url_'.config('upload.global.thumb', 'thumb')] = $uploadObject->url($path.'/'.$fileNameThumb);
                }
            }
        }

        return $this->message(0, '上传成功', $data);
    }

    /**
     * 返回数据结构
     * @param $code
     * @param $message
     * @param $data
     * @return array
     */
    private function message($code, $message, $data = []): array
    {
        return ['code' => $code, 'message' => $message,'data' => $data];
    }

}
