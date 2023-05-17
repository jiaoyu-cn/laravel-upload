class uploadzone {
    // 容器标识
    #dom;

    // csrf
    #csrf

    // 上传实例类
    dropzone = {};

    // 监听事件
    #event = ['error', 'success', 'addedfile', 'removedfile', 'maxfilesexceeded', 'uploadprogress','queuecomplete'];

    // 默认事件
    #configEvent = {
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
    };

    /**
     * 构造函数
     * @param setting
     */
    constructor(setting) {
        if (typeof (setting.dom) == 'undefined') {
            console.log('未指定处理DOM元素');
            return undefined;
        }

        this.initConfig(setting);
    }

    /**
     * 获取配置信息
     * @returns object
     */
    getConfig(){
        return this.config;
    }

    /**
     * 获取实例类
     * @returns {*}
     */
    getDropzone(){
        return this.dropzone;
    }

    /**
     * 初始化函数值
     * @param setting
     */
    initConfig(setting){
        this.#dom = setting.dom;
        this.#csrf = setting.csrf == undefined ? true : setting.csrf;
        this.config = this.getConfigDefault();

        // 设置配置信息
        for (let param of Object.keys(this.config)) {
            if (typeof setting[param] == 'undefined'){
                continue;
            }
            this.config[param] = setting[param];
        }

        // 设置事件
        for (let param of this.#event) {
            if (typeof setting[param] == 'undefined'){
                continue;
            }
            this.#configEvent[param] = setting[param];
        }

        this.#init();
    }

    #init(){
        // 修改文件基值
        this.config.filesizeBase = 1024;
        this.config.forceChunking = true;

        // 处理一些展示
        this.config.dictInvalidFileType += this.config.acceptedFiles;
        this.config.dictFileTooBig += this.config.maxFilesize+'MB';
        this.config.dictMaxFilesExceeded += this.config.maxFiles+'个';

        //
        if (typeof this.config.previewsContainer == "undefined"){
            delete this.config.previewsContainer;
        }

        if (typeof this.config.previewTemplate == "undefined"){
            delete this.config.previewTemplate;
        }

        // csrf处理
        if (this.#csrf){
            this.config.headers = {
                'X-XSRF-TOKEN': $.cookie('XSRF-TOKEN')
            };
        }

        let that = this;
        this.config.init = function() {
            for (let event of Object.keys(that.#configEvent)) {
                this.on(event, that.#configEvent[event]);
            }
        };
        Dropzone.autoDiscover = false;

        this.dropzone = new Dropzone(this.#dom, this.config);
    }

    getConfigDefault() {
        return {
            url:'',
            paramName: "file", // The name that will be used to transfer the file
            maxFiles: 1,
            maxFilesize: 10, // MB
            disablePreviews:false,
            chunking: true,
            chunkSize: 2 * 1024 * 1024,
            acceptedFiles:'',
            addRemoveLinks:true,
            thumbnailWidth:120,
            thumbnailHeight:120,
            dictDefaultMessage: "拖动文件至此处或点击上传",
            dictMaxFilesExceeded: "您最多上传的文件数为",
            dictResponseError: "文件上传失败！",
            dictInvalidFileType: "文件类型支持",
            dictFallbackMessage: "浏览器不支持",
            dictFileTooBig: "文件过大，最大支持",
            dictRemoveFile: "删除",
            dictCancelUpload: "取消",
            dictCancelUploadConfirmation: "确定要取消上传吗?",
            previewsContainer:undefined,
            previewTemplate:undefined,
        };
    }



}
