<?php
namespace app\school\controller;
use app\school\controller\Base;
use think\Db;
use csv\Csv;
/**
 * 行为日志
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年3月21日 上午10:03:47 
 * 类说明
 */
class MemberLog extends Base{
	
	
	/**
	 * 组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['use_type'] = 2;
		$map['school_id'] = session('school_info_id');
		return $map;
	}
	
	/**
	 * 删除全部数据
	 * 同时重置ID为1
	 */
	public function delAll(){
		$sql = 'TRUNCATE table '.config('database.prefix').'member_log';
		if(false !== Db::execute($sql)){
			return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
		}
	}
	
	/**
	 * 导出Csv
	 */
	public function outCsv(){
		$csv = new Csv();
		$model = model('MemberLog');
		$map = $this->loadSeachCondition();
		$data = $model->where($map)->column('id,username,realname,title,url,behavior,userip,from_unixtime(create_time) as create_time');
		$head = array('ID','帐号','真实姓名','触发菜单','触发路径','行为','操作IP','操作时间');
		$csv->export_csv($data,$head,'行为日志');
	}
}