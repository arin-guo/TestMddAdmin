<?php
namespace app\school\controller;
use app\school\controller\Base;
/**
 * banner图控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月14日 上午10:16:19 
 * 类说明
 */
class Banners extends Base{
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		//获取搜索关键字
		if(!empty(input('title'))){
			$map['title'] = array("like","%".input('title')."%");
		}
		if(!empty(input('is_on')) && input('is_on') != ' '){
			$map['is_on'] = array("eq",input('is_on'));
		}
		return $map;
	}
	/**
	 * @authority 新增
	 */
	public function add(){
		$News = model('News');
		$newsList = $News->where('flag',1)->where('school_id',session('school_info_id'))->order('create_time desc')->select();
		$this->assign('news',$newsList);
		return $this->fetch();
	}
	
	
	/**
	 * @authority 修改
	 */
	public function edit(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$News = model('News');
		$newsList = $News->where('flag',1)->where('school_id',session('school_info_id'))->order('create_time desc')->select();
		$this->assign('news',$newsList);
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	//上传图片
	public function uploadImg(){
		$file = request()->file('image');
		$valid['size'] = 3097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/banners/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path,config('app_upload_path'));
			return $this->ajaxReturn($file_path.$info->getSaveName(),'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}
}