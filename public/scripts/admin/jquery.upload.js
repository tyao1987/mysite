/*
 * MMFile.js 基于HTML5 的 文件上传插件
*/

var MMFILE = {
	fileInput: null,				//html file控件
	dragDrop: null,					//拖拽敏感区域
	upButton: null,					//提交按钮
	cancelButton: null,             //全部取消按钮
	url: "",						//ajax地址
	imgroot:"",						//图片路径
	fileFilter: [],					//过滤后的文件数组
	filter: function(files) {		//选择文件组的过滤方法
		return files;	
	},
	onSelect: function() {},		//文件选择后
	onDelete: function() {},		//文件删除后
	onDragOver: function() {},		//文件拖拽到敏感区域时
	onDragLeave: function() {},		//文件离开到敏感区域时
	onProgress: function() {},		//文件上传进度
	onSuccess: function() {},		//文件上传成功时
	onFailure: function() {},		//文件上传失败时,
	onComplete: function() {},		//文件全部上传完毕时
	onCancelUpload:function() {},   //取消上传时
	CheckInput: function(){},		//检查是否有上传文件名称待编辑状态中
	hideUploadBtn:function(){},		//隐藏上传按钮.
	
	/* 开发参数和内置方法分界线 */
	
	//文件拖放
	funDragHover: function(e) {
		e.stopPropagation();
		e.preventDefault();
		this[e.type === "dragover"? "onDragOver": "onDragLeave"].call(e.target);
		return this;
	},
	//获取选择文件，file控件或拖放
	funGetFiles: function(e) {
		// 取消鼠标经过样式
		this.funDragHover(e);
				
		// 获取文件列表对象
		var files = e.target.files || e.dataTransfer.files;
		//继续添加文件
		this.fileFilter = this.fileFilter.concat(this.filter(files));
		this.funDealFiles();
		return this;
	},
	
	//选中文件的处理与回调
	funDealFiles: function() {
		for (var i = 0, file; file = this.fileFilter[i]; i++) {
			//增加唯一索引值
			file.index = i;
		}
		//执行选择回调
		this.onSelect(this.fileFilter);
		return this;
	},
	
	//删除对应的文件
	funDeleteFile: function(fileDelete) {
		var arrFile = [];
		for (var i = 0, file; file = this.fileFilter[i]; i++) {
			if (file != fileDelete) {
				arrFile.push(file);
			} else {
				this.onDelete(fileDelete);	
			}
		}
		if (arrFile.length == 0){
			this.hideUploadBtn();
		}
		this.fileFilter = arrFile;
		return this;
	},
	
	//删除全部文件
	funDeleteAllFile:function(){
		var arrFile = [];
		for(var i = 0, file; file = this.fileFilter[i];i++){
			this.onDelete(file);
		}
		this.fileFilter = arrFile;
		this.onCancelUpload();
		return this;
	},
	
	//文件上传
	funUploadFile: function() {
		var self = this;
//		for (var i = 0, file; file = this.fileFilter[i]; i++) {
		for(fileObj in this.fileFilter){
			var file = this.fileFilter[fileObj];
			var i = file.index;
			(function(file) {
				var xhr = new XMLHttpRequest();
				if (xhr.upload) {
					var name=jQuery("#uploadList_"+i).find(".showTxt").html();
					// 上传中
					xhr.upload.addEventListener("progress", function(e) {
						self.onProgress(file, e.loaded, e.total);
					}, false);
		
					// 文件上传成功或是失败
					xhr.onreadystatechange = function(e) {
						if (xhr.readyState == 4) {
							var result = eval("("+xhr.responseText+")");
							self.funDeleteFile(file);
							if ((xhr.status == 200) && (result.status == 1)) {
								self.onSuccess(name, result);
							} else {
								self.onFailure(name, result);		
							}
							if (!self.fileFilter.length) {
								//全部完毕
								self.onComplete();	
							}
						}
					};
					
					
					var cover = jQuery("#cover").is(':checked');

					//file obj.
					form = new FormData();
					form.append('filename', name);
					form.append('file',file);
					form.append('imgroot',self.imgroot);
					form.append('cover',cover);
		
					// 开始上传
					xhr.open("POST", self.url, true);
					xhr.send(form);
				}	
			})(file);	
		}	
			
	},
	
	init: function() {
		var self = this;
		
		if (this.dragDrop) {
			this.dragDrop.addEventListener("dragover", function(e) { self.funDragHover(e); }, false);
			this.dragDrop.addEventListener("dragleave", function(e) { self.funDragHover(e); }, false);
			this.dragDrop.addEventListener("drop", function(e) { self.funGetFiles(e); }, false);
		}
		
		//文件选择控件选择
		if (this.fileInput) {
			this.fileInput.addEventListener("change", function(e) { self.funGetFiles(e); }, false);	
		}
		
		//上传按钮提交
		if (this.upButton) {
			this.upButton.addEventListener("click", function(e) { 
				if (!self.CheckInput()) 
					return false;
				self.funUploadFile(e); 
			}, false);	
		} 
		
		//全部取消按钮
		if (this.cancelButton){
			this.cancelButton.addEventListener("click", function(e) { self.funDeleteAllFile(); }, false);	
		}
	}
};