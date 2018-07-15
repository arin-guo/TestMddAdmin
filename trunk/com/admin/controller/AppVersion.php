<?php
namespace app\admin\controller;
use app\admin\controller\Base;
/**
 * app版本控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月12日 上午10:16:19 
 * 类说明
 */
class AppVersion extends Base{
	
	/**
	 * 组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		//搜索条件
		if(!empty(input('version'))){
			$map['version'] = array("like","%".input('version')."%");
		}
		if(!empty(input('type')) && input('type') != ' '){
			$map['type'] = array("like","%".input('type')."%");
		}
		if(!empty(input('use_type')) && input('use_type') != ' '){
			$map['use_type'] = array("like","%".input('use_type')."%");
		}
		return $map;
	}
	/**
	 * @authority 新增
	 */
	public function add(){
		$fileList = $this->getFileList();
		$this->assign('file',$fileList);
		return $this->fetch();
	}
	
	// 添加新版本
	// 添加前需要把type和use_type相同的版本的status修改成0
	public function insert(){
		$Model = model(request()->controller());
		$data = request()->post();
		if($data['type'] == 2){
			$data['url'] = "";
		}
		//将type和use_type相同的版本status修改成0
		$status['status'] = 0;
		$status['update_time'] = time();
		$Model->where('type',$data['type'])->where('use_type',$data['use_type'])->update($status);
		//添加新版本信息
		$result = $Model->allowField(true)->save($data);
		if($result){
			return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
		}
	}
	
	/**
	 * @authority 修改
	 */
	public function edit(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$fileList = $this->getFileList();
		$this->assign('vo',$vo);
		$this->assign('file',$fileList);
		return $this->fetch();
	}
	
	/**
	 * @authority 修改方法
	 */
	public function update(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$data = request()->post();
		if($data['type'] == 2){
			$data['url'] = "";
		}
		$where[$pk] = $data[$pk];
		$result = $Model->allowField(true)->save($data,$where);
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
	}
	
	/**
	 * 获取文件列表
	 */
	public function getFileList(){
		$dir = config('app_upload_path').'/uploads/static/app/';
		$data['dir'] = substr($dir,strlen(config('app_upload_path')),strlen($dir)-strlen(config('app_upload_path')));
		if(!file_exists($dir)){
			$data['filename'] = [];
			return $data;
		}
		$handler = opendir($dir);
		while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
			if ($filename != "." && $filename != ".."){
				$nameArray = explode(".", $filename);
				if($nameArray[count($nameArray) - 1] == "apk"){
					$data['filename'][] = $filename;
				}
				
			}
		}
		ksort($data);
		closedir($handler);
		return $data;
	}
}