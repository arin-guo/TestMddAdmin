<?php
namespace app\school\controller;
use app\school\controller\Base;
/**
 * 餐饮模块控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月28日 上午10:16:19 
 * 类说明
 */
class Cookbook extends Base{
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
			$map['name'] = array("like","%".input('name')."%");
		}
		if(!empty(input('type')) && input('type') != ' '){
			$map['type'] = array("eq",input('type'));
		}	
		return $map;
	}
	/**
	 * @authority 逻辑删除
	 */
	public function logicDel(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$ids = request()->param($pk);
		if(!empty($ids)){
			//查询该菜肴是否在未来的菜谱里
			$cookdate=model('CookbookDate');
			$dateterm['cookbook_id']=$ids;
			$dateterm['flag']=1;
			$dateterm['school_id']=session('school_info_id');
			$dateterm['day_time']=array('egt',date('Y-m-d',time()));
			$dateinfo=$cookdate->where($dateterm)->field('day_time')->select();
			if($dateinfo){
				foreach ($dateinfo as $val){
					$day[] = $val['day_time'];
				}
				$day = implode('，',$day);
				return $this->ajaxReturn(0,$day.'绑定了该菜肴，请删除该日期下的菜肴后再进行操作',0);
			}else{
				$where[$pk] = array('in', explode(',', $ids));
				if(false !== $Model->save(array('flag'=>2),$where)){
					return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
				}else{
					return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
				}
			}
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
		}
	}
	/**
	 * 上传图片
	 * @return multitype:multitype:string
	 */
	public function uploadImg(){
		$file = request()->file('image');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/cookbook/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path,config('app_upload_path'));
			return $this->ajaxReturn($file_path.$info->getSaveName(),'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}
}