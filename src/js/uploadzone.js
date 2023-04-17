// 封装dropzone实例化
function uploadzone(setting) {
    if (typeof (setting.dom) == 'undefined') {
        console.log('未指定处理DOM元素');
        return;
    }

    // 元素名称
    this.dom = setting.dom;
    this.csrf = setting.csrf == undefined ? true : setting.csrf;

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
    csrf:true,

    // 事件列表
    event:['error', 'success', 'removedfile','maxfilesexceeded'],

    // 配置信息
    config:{
        url:'',
        paramName: "file", // The name that will be used to transfer the file
        maxFiles: 1,
        maxFilesize: 10, // MB
        disablePreviews:false,
        chunking: true,
        chunkSize: 2 * 1024 * 1024,
        acceptedFiles:'',
        dictDefaultMessage: "拖动文件至此处或点击上传",
        dictMaxFilesExceeded: "您最多上传的文件数为",
        dictResponseError: "文件上传失败！",
        dictInvalidFileType: "文件类型支持",
        dictFallbackMessage: "浏览器不支持",
        dictFileTooBig: "文件过大，最大支持",
        dictRemoveFile: "删除",
        dictCancelUpload: "取消",
        previewsContainer:null,
        previewTemplate:null,
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
        // 修改文件基值
        config.filesizeBase = 1024;
        config.forceChunking = true;

        // 处理一些展示
        config.dictInvalidFileType += config.acceptedFiles;
        config.dictFileTooBig += config.maxFilesize+'MB';
        config.dictMaxFilesExceeded += config.maxFiles+'个';

        //
        if (typeof config.previewsContainer == "undefined"){
            delete config.previewsContainer;
        }

        if (typeof config.previewTemplate == "undefined"){
            delete config.previewTemplate;
        }

        // csrf处理
        if (this.csrf){
            config.headers = {
                'X-XSRF-TOKEN': $.cookie('XSRF-TOKEN')
            };
        }

        let that = this;
        config.init = function() {
            for (let event of Object.keys(that.configInit)) {
                this.on(event, that.configInit[event]);
            }
        };
        Dropzone.autoDiscover = false;
        this.dropzone = new Dropzone(this.dom, config);
    },
    getConfig:function (){
        return this.config;
    },
}
