# laravel-upload
基于Dropzone为laravel提供上传支持


[![image](https://img.shields.io/github/stars/jiaoyu-cn/laravel-upload)](https://github.com/jiaoyu-cn/laravel-upload/stargazers)
[![image](https://img.shields.io/github/forks/jiaoyu-cn/laravel-upload)](https://github.com/jiaoyu-cn/laravel-upload/network/members)
[![image](https://img.shields.io/github/issues/githen-cn/laravel-upload)](https://github.com/githen-cn/laravel-upload/issues)

## 安装

```shell
composer require githen/laravel-upload:^v1.0.0

# 迁移配置文件
php artisan vendor:publish --provider="Githen\LaravelUpload\UploadProvider"
```
## 配置文件说明
在`config/filesystem.php`中添加目录`public`配置项
```php
'localFile' => [
        'driver' => 'local',
        'root' => public_path(),
        'url' => config('app.url'), // 生成url前缀
        'permissions' => [
            'file' => [
                'public' => 0770,
                'private' => 0600,
            ],
            'dir' => [
                'public' => 0770,
                'private' => 0700,
            ],
        ],
    ],
```
生成`upload.php`上传配置文件
```php
return [
    /**
    |--------------------------------------------------------------------------
    | 文件上传配置
    |--------------------------------------------------------------------------
    |  '标识' => [
     *      'size' => 3, //文件大小上限  类型int 单位MB
     *      'ext' => ['png', 'jpg']  // 支持的文件类型，类型array
     *      'resize' => [200, 200]  // 若为图片类型，自动处理图片为200X200
     *      'path' => 'uploads/'. date('Y/m/d'),  // 文件实际存储目录
     * ]
     */
    'global' => [
        'tmp' => 'uploads/tmp', // 临时目录，在表单提交时再将文件移动到path目录
        'path' => 'uploads', // 实际存储目录
        'name' => 'file',  // 文件上传时文件对应的key
        'auth' => ['auth'],  // 上传controller加载的中间件
        'filesystem' => 'localFile', // 使用的文件系统
        'thumb' => 'thumb', // 图片类型缩略图名称，a.png => a_thumb.png
    ],

    'img' => [
        'size' => 3, // MB
        'ext' => ['.png', '.jpg', '.jpeg', '.zip'],
        'resize' => [200,200], // 生成缩略图，只有图片格式有效，不配置则不生成
        'path' => 'uploads/'. date('Y/m/d'),
    ]
];

```

## 初始化上传实例

自动发现功能将在6.0.0中删除，如果依赖此功能，需要手动关闭。默认生成的JS已处理，可忽略。
```javascript
Dropzone.autoDiscover = false; 
```

在`html`中引入`JS文件`
```html
<script src=".../dropzone.min.js"></script>
<script src="/app-assets/js/scripts/forms/uploadzone.js"></script>
```
进行实例化
```javascript
myDropzone = new uploadzone({
    dom: '#dpz-single-file',
    paramName: "{!! config('upload.global.name') !!}",
    acceptedFiles:"{!! implode(',', config('upload.img.ext')) !!}",
    
    // param为上传关联配置的标识 ，必填 
    // is_tmp 决定使用哪个目录，false时，使用path配置目录，true时使用tmp目录
    url:"{!! route('jiaoyu.upload',['param' => 'img', 'is_tmp'=>true]) !!}",
    csrf:true,
    
    // 成功回调，可通过此方法处理返回文件信息
    success:function (file){
        if (file.status === "success"){
            var response = JSON.parse(file.xhr.response);
        }
    },
    
    // 失败回调,以下为默认方法，如不修改可省略
    error:function (file, data){
        if (file.previewElement){
            let message = data;
            if (typeof data.message !== "undefined") message = data.message
            if (typeof message !== "string" && message.error) {
                message = message.error;
            }
            for (let node of file.previewElement.querySelectorAll(
                "[data-dz-errormessage]"
            )) {
                node.textContent = message;
            }
        }
    }
})
```

| 参数 | 名称                 | 说明                                                                | 备注                                                                                 |
|----|--------------------|-------------------------------------------------------------------|------------------------------------------------------------------------------------|
|  dom  | 实例化的DOM标识          | 必填                                                                |                                                                                    |
|  csrf  | POST提交时的csrf验证     | 非必填<br>true| false                                                                              |    |
|  acceptedFiles  | 允许上传文件后缀(.jpg,.png) | 默认为.zip                                                           |                                                                                    |
|  url  | 上传地址 | {!! route('jiaoyu.upload',['param' => 'img', 'is_tmp'=>true]) !!} | `param`:标识（以此标识从`upload.php`中获取配置信息）<br/>`is_tmp`:是否使用临时目录(`tmp`)，为false使用`path`目录 |
|  paramName  | 上传的属性名称  | 非必填，默认:`file`                                                     |                                                                                    |
|  chunkSize  | 分片大小  | 单位：MB,默认2MB                                                       |                                                                                    |
|  maxFiles  | 最多上传文件数  | 非必填，默认：1                                                          |                                                                                    |
|  maxFilesize  | 文件最大限制   | 非必填，单位：MB,默认10MB,                                                 |                                                                                    |
|  chunking  | 是否分片   |                                                                   |                                                                                    |
|  forceChunking  | 上传时显示文件详情，不可修改   |                                                                   |                                                                                    |
|  dictDefaultMessage  | 默认提示语   | 拖动文件至此处或点击上传                                                      |                                                                                    |
|  dictMaxFilesExceeded  | 超过限制上传数量提示语    | 您最多上传的文件数为 +   maxFiles                                           |                                                                                    |
|  dictResponseError  | 上传失败提示语  | 文件上传失败！                                                           |                                                                                    |
|  dictInvalidFileType  | 文件类型提示语   | 文件类型支持                                                            |                                                                                    |
|  dictFallbackMessage  | 兼容性提示语   | 浏览器不支持                                                            |                                                                                    |
|  dictFileTooBig  | 文件过大提示语   | 文件过大，最大支持 +  maxFilesize + MB                                     |                                                                                    |
|  dictRemoveFile  | 删除提示语     | 删除                                                                |                                                                                    |
|  dictCancelUpload  | 取消提示语   | 取消                                                                |                                                                                    |

## 临时目录迁移到正式目录
如果上传的文件是放在临时目录`tmp`下，则在实际业务中，需要进行迁移文件到正式目录。可执行以下操作完成迁移操作。
```php
//app('jiaoyu.move') 以单例形貌注入，执行迁移操作
// $from 文件地址
// $to    需要迁移到的目录
// 如果存在缩略图，一并迁移 
$result = call_user_func(app('jiaoyu.move'), $from, $to);

// 返回数据结构
[
    "code" => 0, // 0 成功 1失败
    "message" => "成功", // 执行说明
    "path" => '', // 新文件的目录，缩略图默认在文件名后加标识 _thumb
]
```
