# laravel-upload
基于Dropzone为laravel提供上传支持


[![image](https://img.shields.io/github/stars/jiaoyu-cn/laravel-upload)](https://github.com/jiaoyu-cn/laravel-upload/stargazers)
[![image](https://img.shields.io/github/forks/jiaoyu-cn/laravel-upload)](https://github.com/jiaoyu-cn/laravel-upload/network/members)
[![image](https://img.shields.io/github/issues/githen-cn/laravel-upload)](https://github.com/githen-cn/laravel-upload/issues)

## 安装

```shell
composer require githen/laravel-upload

# 迁移配置文件
php artisan vendor:publish --provider="Githen\LaravelUpload\UploadProvider"
```
## 配置文件说明
在`config/filesystem.php`中添加目录`public`配置项
```php
'localFile' => [
        'driver' => 'local',
        'root' => public_path(),
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
     *       'ext' => ['png', 'jpg']  // 支持的文件类型，类型array
     * ]
     */

    'img' => [
        'size' => 3, // MB
        'ext' => ['png', 'jpg', 'jpeg'],
    ]
];
```

## 初始化上传实例

自动发现功能将在6.0.0中删除，如果依赖此功能，需要手动关闭。
```javascript
Dropzone.autoDiscover = false; 
```

在`html`中引入`JS文件`
```html
<script src=".../dropzone.min.js'"></script>
```
进行实例化
```javascript
$(function (){
    var uploadObject = new Dropzone('.dropzone',{
        paramName: "file", // 上传的属性名称
        maxFiles: 1,  // 上次最多上传文件数
        maxFilesize: 200, // 文件最大限制，200MB 
        forceChunking: true, // 强制分片 
        chunking: true, // 是否分片 
        chunkSize: 2 * 1024 * 1024, // 分片大小 2M
        acceptedFiles:'.zip', //文件后缀
        // 以下为汉化信息
        dictDefaultMessage: "拖动文件至此处或点击上传",
        dictMaxFilesExceeded: "您最多只能上传1个文件",
        dictResponseError: "文件上传失败！",
        dictInvalidFileType: "文件类型只能是*.zip",
        dictFallbackMessage: "浏览器不支持",
        dictFileTooBig: "文件过大，上传的文件最大支持.",
        dictRemoveFile: "删除",
        dictCancelUpload: "取消",

        init: function() {
            // 成功回调
            this.on("success", function(file) {
                if (file.status === "success"){
                    var response = JSON.parse(file.xhr.response);
                    $("#uploadfile").val(response.path+response.name);
                }
            });
            // 失败回调
            this.on("error", function (file, data){
                if (data.message !== undefined){
                    eolError(data.message);
                }
            });
        }
    });
})

```
