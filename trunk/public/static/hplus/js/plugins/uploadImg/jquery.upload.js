/**
 * 上传插件
 * @author chenlisong1021@163.com
 */
(function($) {
	var noop = function(){ return true; };
	var frameCount = 0;
	
	$.uploadDefault = {
		url: '',
		fileName: 'filedata',
		dataType: 'json',
		params: {},
		acceptType:"image/*",//可上传的类型
		onSend: noop,
		onSubmit: noop,
		onComplate: noop
	};

	$.upload = function(options) {
		var opts = $.extend(jQuery.uploadDefault, options);
		if (opts.url == '') {
			return;
		}
		
		var canSend = opts.onSend();
		if (!canSend) {
			return;
		}
		
		var frameName = 'upload_frame_' + (frameCount++);
		var iframe = $('<iframe style="position:absolute;top:-9999px" />').attr('name', frameName);
		var form = $('<form method="post" style="display:none;" enctype="multipart/form-data" />').attr('name', 'form_' + frameName);
		form.attr("target", frameName).attr('action', opts.url);
		
		// form中增加数据域
		var formHtml = '<input type="file" name="' + opts.fileName + '" onchange="onChooseFile(this)" accept="'+opts.acceptType+'">';
		for (key in opts.params) {
			formHtml += '<input type="hidden" name="' + key + '" value="' + opts.params[key] + '">';
		}
		form.append(formHtml);

		iframe.appendTo("body");
		form.appendTo("body");
		
		form.submit(opts.onSubmit);
		
		// iframe 在提交完成之后
		iframe.load(function() {
			var contents = $(this).contents().get(0);
			var data = $(contents).find('body').text();
			if(data == ""){
				return false;
			}
			if ('json' == opts.dataType) {
				data = window.eval('(' + data + ')');
			}
			opts.onComplate(data);
			setTimeout(function() {
				iframe.remove();
				form.remove();
			}, 5000);
		});
		
		// 文件框
		var fileInput = $('input[type=file][name=' + opts.fileName + ']', form);
		fileInput.click();
	};
	
	//组装地址
	$.getImgHref = function(t){
		var imghref = "";var href = "";
		$(t).find(".done-up-img").each(function(im,ts){
			href = $(ts).find('input.sub_img').val();
			imghref += href + "|";
		})
		imghref = imghref.substring(0,imghref.length -1);
		$(t).find("#photo").val(imghref);
	}
	
	//上传创建
	$.appendImg = function(t,data){
		var url = data.data;
		$thumb = $(t).parents(".file-up");
		var shtml = '<section class="file-box done-up-img">';
		    shtml += '<span class="up-span"></span>';
		    shtml += '<img class="close-upimg" src="/static/hplus/js/plugins/uploadImg/img/close.png" onclick="removeImg(this)">';
		    shtml += '<a class="fancybox" href="'+data+'" title="点击放大图片"><img class="up-img" src="'+data+'"></a>';
		    shtml += '<p class="img-name-p">上传成功</p>';
		    shtml += '<input type="hidden" class="sub_img" value="'+data+'"/>';
		    shtml += '</section>';
		//判断最多能上传多少张
		var data_num = $(t).parents(".file-up").attr("data-num");
		if(data_num == 1) {
			$thumb.prepend(shtml);
		}else if(data_num > 1) {
			$thumb.prepend(shtml);
		}
		var has_data_num = $(t).parents(".file-up").find(".done-up-img").length;
		//达到上传限制张数时，隐藏上传按钮
		if(has_data_num >= data_num){
			$(t).parents(".file-box").hide();
		}
		//组装地址
		$.getImgHref($thumb);
		resizeView();
		//绑定事件
		$(t).parents(".file-up").find(".done-up-img").bind('mouseover',function(){
			$(this).children(".close-upimg").show(); 
		    $(this).addClass("close-upimg-border");
		})
		$(t).parents(".file-up").find(".done-up-img").bind('mouseout',function(){
			$(this).children(".close-upimg").hide();  
			$(this).removeClass("close-upimg-border");
		})
	}
})(jQuery);
//自适应
function resizeView() {
	$(".file-up").width("100%");
}
$(window).bind('resize', function () {
	resizeView()
});

//删除图片
function removeImg(t){
	$ts = $(t).parents(".file-up");
	//显示上传按钮
	$(t).parents(".file-up").find(".file-box:last").show();
	$(t).parent("section").remove();
	$.getImgHref($ts);
	
}
// 选中文件, 提交表单(开始上传)
var onChooseFile = function(fileInputDOM) {
	var form = $(fileInputDOM).parent();
	form.submit();
};