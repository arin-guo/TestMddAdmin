<?php
namespace app\school\controller;
use app\school\controller\Base;
/**
 * 新闻控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年11月6日 下午16:16:19
 * 类说明
 */
class News extends Base{
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		//获取搜索关键字
		if(!empty(input('name'))){
			$map['title'] = array("like","%".input('title')."%");
		}
		if(!empty(input('type')) && input('type') != ' '){
			$map['type'] = array("eq",input('type'));
		}
		return $map;
	}
    /**
     * 上传图片
     * 保存头图
     */
    public function uploadImgHead(){
        $file = request()->file('image');
        $valid['size'] = 3097152;//3M
        $valid['ext'] = 'jpg,png,gif';
        $path = config('app_upload_path').'/uploads/news/';
        $info = $file->validate($valid)->rule('date')->move($path);
        if($info){
            $file_path = ltrim($path,config('app_upload_path'));
            return $this->ajaxReturn($file_path.$info->getSaveName(),'上传成功！',1);
        }else{
            return $this->ajaxReturn(0,$file->getError(),0);
        }
    }
    /**
     * 上传文件
     * 保存音频
     */
    public function uploadAudio(){
        $file = request()->file('inputAudio');
        $valid['size'] = 8097152;//8M
        $valid['ext'] = 'mp3,wma,wav,acc,mid';
        $sid = session('school_info_id');
        $time = time();
        $path = config('app_upload_path').'/uploads/audio/'.$sid.'/'.$time.'/';
        $info = $file->validate($valid)->move($path,'');
        if($info){
            $file_path = ltrim($path,config('app_upload_path'));
            return $this->ajaxReturn($file_path.$info->getSaveName(),'上传成功！',1);
        }else{
            return $this->ajaxReturn(0,$file->getError(),0);
        }
    }
    /**
     * 删除上传的音频文件
     *
     */
    public function deleteAudio(){
        $auaddress = request()->post('audioaddress');
        $path = config('app_upload_path').'/'.$auaddress;
        if(is_file($path)){
            unlink($path);
            return $this->ajaxReturn(1,'删除成功！',1);
        }else{
            return $this->ajaxReturn(0,'删除失败！',0);
        }
    }
	/**
	 * 上传图片
	 * @return multitype:multitype:string
	 * 用于百度编辑器
	 */
	public function uploadImg(){
		$file = request()->file('upfile');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/editor/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$reback['state'] = 'SUCCESS';
			$reback['url'] = ltrim($path,config('app_upload_path')).$info->getSaveName();
			return json_encode($reback);
		}else{
			$reback['state'] = 'ERROR';
			return json_encode($reback);
		}
	}
}