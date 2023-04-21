<?php

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
    'global' => [
        'tmp' => '/uploads/tmp', //
        'path' => '/uploads',
        'name' => 'file',
        'auth' => ['auth'],
        'filesystem' => 'localFile',
        'thumb' => 'thumb',

    ],

    'img' => [
        'size' => 10, // MB
        'ext' => ['.png', '.jpg', '.jpeg'],
        'resize' => [400,500],
        'thumb_resize' => [200,200],
        'path' => '/uploads/'. date('Y/m/d'),
    ]
];
