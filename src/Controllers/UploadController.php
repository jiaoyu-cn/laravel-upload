<?php
namespace Githen\LaravelUpload\Controllers;

use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class UploadController extends \App\Http\Controllers\Controller
{
    public function __construct()
    {
        $this->middleware(config('upload.gloabal.auth'));
    }

    /**
     * 文件上传
     * @return void
     */
    public function upload(Request $request,$param)
    {
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
        if (! in_array('.'.$extendsion, $config['ext'])){
            return $this->message(1, '不支持的上传类型'.$extendsion);
        }
        if ($request->input('dztotalfilesize') > $config['size'] * 1048576){
            return $this->message(1, '文件大小超过上限：'. $config['size'].'MB');
        }

        // 分片上传中
        $fileName = md5($request->input('dzuuid')).'.'.$extendsion;

        // 检测存放目录
        $pathType = $request->input('is_tmp', false) ? 'tmp' : 'path';
        $path = ($config[$pathType] ?? config('upload.global.'.$pathType));

        // 初始化
        $uploadObject = Storage::disk(config('upload.gloabal.filesystem','localFile'));
        if (!$uploadObject->exists($path)){
            $uploadObject->putFileAs($path, $request->file(config('upload.global.name')), $fileName, 'public');
        }else{
            file_put_contents($uploadObject->path($path.'/'.$fileName),
                $request->file('file')->get(), FILE_APPEND);
        }

        $data = [];
        if ($request->input('dzchunkindex') == $request->input('dztotalchunkcount') - 1) {
            $data = [
                'path' => '/'.trim($path.'/'.$fileName, DIRECTORY_SEPARATOR),
                'url' =>$uploadObject->url($path.'/'.$fileName),
                'filename' => $uploadFile->getClientOriginalName(),
                'uid' => $request->input('dzuuid'),
            ];
            // 图片类型生成缩略图
            if(Validator::make(['file' => new File($uploadObject->path($path.'/'.$fileName))], ['file' => 'image'])->passes()){
                // 原图压缩
                if (isset($config['resize']) && count($config['resize']) == 2){
                    Image::make($uploadObject->path($path.'/'.$fileName))->resize($config['resize'][0], $config['resize'][1], function ($constraint){
                        $constraint->aspectRatio();   // 按比例调整图片大小
                        $constraint->upsize(); // 这里如果宽度不足 时，保持原来尺寸
                    })->save($uploadObject->path($path.'/'.$fileName));
                }

                // 生成缩略图
                if (isset($config['thumb_resize']) && count($config['thumb_resize']) == 2){
                    $fileNameThumb = md5($request->input('dzuuid')).'_'.config('upload.gloabal.thumb', 'thumb').'.'.$extendsion;
                    Image::make($uploadObject->path($path.'/'.$fileName))->resize($config['thumb_resize'][0], $config['thumb_resize'][1], function ($constraint){
                        $constraint->aspectRatio();   // 按比例调整图片大小
                        $constraint->upsize(); // 这里如果宽度不足 200 时，保持原来尺寸
                    })->save($uploadObject->path($path.'/'.$fileNameThumb));
                    $data['path_'.config('upload.gloabal.thumb', 'thumb')] = '/'.trim($path.'/'.$fileNameThumb, DIRECTORY_SEPARATOR);
                    $data['url_'.config('upload.gloabal.thumb', 'thumb')] = $uploadObject->url($path.'/'.$fileNameThumb);
                }
            }
        }

        return $this->message(0, '上传成功', $data);
    }

    private function message($code, $message, $data = [])
    {
        return response()->json([
            'status' => $code > 0 ? 'error' : 'success',
            'message' => $message,
            'data' => $data
        ], $code > 0 ? 507 : 200);
    }

}
