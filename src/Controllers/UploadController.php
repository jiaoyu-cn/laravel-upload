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
     * @return
     */
    public function upload(Request $request,$param)
    {
        $result = app('jiaoyu.upload')->upload($param);
        return response()->json([
            'status' =>  $result['code'] > 0 ? 'error' : 'success',
            'message' => $result['message'],
            'data' => $result['data']
        ], $result['code']  > 0 ? 507 : 200);
    }

}
