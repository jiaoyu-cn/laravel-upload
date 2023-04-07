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

        if (! in_array('.'.$uploadFile->getClientOriginalExtension(), $config['ext'])){
            return $this->message(1, '不支持的上传类型'.$uploadFile->getClientOriginalExtension());
        }
        if ($request->input('dzchunksize') > $config['size'] * 1048576){
            return $this->message(1, '文件大小超过上限：'. $config['size'].'MB');
        }

        // 分片上传中
        $fileName = md5($request->input('dzuuid')).'.'.$uploadFile->getClientOriginalExtension();

        // 检测存放目录
        $pathType = $request->input('is_tmp', false) ? 'tmp' : 'path';
        $path = ($config[$pathType] ?? config('upload.global.'.$pathType));

        // 初始化
        if (!Storage::disk('localFile')->exists($path)){
            Storage::disk('localFile')->putFileAs($path, $request->file(config('upload.global.name')), $fileName, 'public');
        }else{
            file_put_contents(app('path.public').DIRECTORY_SEPARATOR.ltrim($path.'/'.$fileName, DIRECTORY_SEPARATOR),
                $request->file('file')->get(), FILE_APPEND);
        }

        $data = [];
        if ($request->input('dzchunkindex') == $request->input('dztotalchunkcount') - 1) {
            $data = [
                'path' => '/'.$path.'/'.$fileName,
                'url' => app('url')->asset($path.'/'.$fileName),
                'filename' => $uploadFile->getClientOriginalName()
            ];
            // 图片类型生成缩略图
            if(Validator::make(['file' => new File(public_path($path.'/'.$fileName))], ['file' => 'image'])->passes() &&
                isset($config['resize']) && count($config['resize']) == 2
            ){
                $fileNameThumb = md5($request->input('dzuuid')).'_thumb.'.$uploadFile->getClientOriginalExtension();
                Image::make(public_path($path.'/'.$fileName))->resize($config['resize'][0], $config['resize'][1], function ($constraint){
                    $constraint->aspectRatio();   // 按比例调整图片大小
                    $constraint->upsize(); // 这里如果宽度不足 200 时，保持原来尺寸
                })->save(public_path($path.'/'.$fileNameThumb));

                $data['path_thumb'] = '/'.$path.'/'.$fileNameThumb;
                $data['url_thumb'] = app('url')->asset($path.'/'.$fileNameThumb);
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