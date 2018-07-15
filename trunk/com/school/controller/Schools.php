<?php
namespace app\school\controller;
use app\school\controller\Base;
/**
 * 学校管理
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年3月16日 下午3:17:59 
 * 类说明
 */
class Schools extends Base{
	
	/**
	 * 首页
	 */
	public function index(){
		$this->assign('vo',session('schoolInfo'));
		return $this->fetch();
	}
	
	/**
	 * @authority 修改方法
	 */
	public function update(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$data = request()->param();
		$where[$pk] = $data[$pk];
		$result = $Model->allowField(true)->save($data,$where);
		if($result !== false){
			//重置session里的学校信息
			session('schoolInfo',$Model::get($data[$pk]));
			return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
	}	
	
	/**
	 * 上传图片类
	 * @return multitype:multitype:string
	 */
	public function uploadImg(){
		$file = request()->file('image');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/school/logo/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path.$info->getSaveName(),config('app_upload_path'));
			return $this->ajaxReturn($file_path,'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}
}