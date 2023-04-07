// 封装dropzone实例化
function uploadzone(setting) {
    if (typeof (setting.dom) == 'undefined') {
        console.log('未指定处理DOM元素');
        return;
    }

    // 元素名称
    this.dom = setting.dom;

    // csrf处理
    if (typeof setting['csrf'] != 'undefined'){
        this.csrf = setting['csrf'];
    }

    // 设置配置信息
    for (let param of Object.keys(this.config)) {
        if (typeof setting[param] == 'undefined'){
            continue;
        }
        this.config[param] = setting[param];
    }

    // 设置事件
    for (let param of this.event) {
        if (typeof setting[param] == 'undefined'){
            continue;
        }
        this.configInit[param] = setting[param];
    }

    this.init();

    return this.dropzone;

}

uploadzone.prototype = {
    // 实例类
    dropzone:undefined,

    // csrf处理
    csrf:'',

    // 事件列表
    event:['error', 'success'],

    // 配置信息
    config:{
        url:'',
        paramName: "file", // The name that will be used to transfer the file
        maxFiles: 1,
        maxFilesize: 200, // MB
        forceChunking: true,
        chunking: true,
        chunkSize: 2 * 1024 * 1024,
        acceptedFiles:'',
        dictDefaultMessage: "拖动文件至此处或点击上传",
        dictMaxFilesExceeded: "您最多上传的文件数为",
        dictResponseError: "文件上传失败！",
        dictInvalidFileType: "文件类型支持",
        dictFallbackMessage: "浏览器不支持",
        dictFileTooBig: "文件过大，上传的文件最大支持",
        dictRemoveFile: "删除",
        dictCancelUpload: "取消",
    },

    // 事件绑定
    configInit:{
        'success':function(file) {
            if (file.status === "success"){
                var response = JSON.parse(file.xhr.response);
            }
        },
        'error': function (file, data){
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
    },

    init: function () {

        let config = this.config;
        // 处理一些展示
        config.dictInvalidFileType += config.acceptedFiles;
        config.dictFileTooBig += config.maxFilesize;
        config.dictMaxFilesExceeded += config.maxFiles;

        // csrf处理
        if (this.csrf.length > 0){
            config.headers = {
                'X-CSRF-TOKEN':this.csrf
            };
        }

        let that = this;
        config.init = function() {
            for (let event of Object.keys(that.configInit)) {
                this.on(event, that.configInit[event]);
            }
        };

        this.dropzone = new Dropzone(this.dom, config);
    },
    getConfig:function (){
        return this.config;
    },
}
