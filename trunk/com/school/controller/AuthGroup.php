<?php
namespace app\school\controller;
use app\school\controller\Base;
/**
 * 权限组
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年3月23日 上午10:06:56 
 * 类说明
 */
class AuthGroup extends Base{
	
	/**
	 * 组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['use_type'] = 2;
		$map['school_id'] = session('school_info_id');
		$map['status'] = array('>=',0);
		return $map;
	}
	
	/**
	 * @authority 配置权限
	 */
	public function conf(){
		$Model = model('AuthRule');$GroupModel = model('AuthGroup');
		$map['status'] = 1;
		$map['type'] = 1;
		$map['use_type'] = 2;
		$groupWhere['id'] = input('id');
		$groupWhere['use_type'] = 2;
		$groupWhere['status'] = array('neq',-1);
		$result = $GroupModel->where($groupWhere)->find();
		$rules = explode(',',$result['rules']);
		$vo = $Model->where($map)->order('seq asc')->select();
		//查询出层级关系数据
		foreach ($vo as $key=>$val){
			$map['type'] = 2;
			$map['parent_id'] = $val['id'];
			$sub_menu = $Model->where($map)->order('seq asc')->select();
			$vo[$key]['sub_menu'] = $sub_menu;
			//判断是否拥有该权限
			foreach($sub_menu as $subKey=>$subVal){
				if(in_array($subVal["id"],$rules)) {
					$vo[$key]["sub_menu"][$subKey]["isCheck"] = 0;
				}else{
					$vo[$key]["sub_menu"][$subKey]["isCheck"] = 1;
				}
			}
		}
		$this->assign('id',input('id'));
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
}