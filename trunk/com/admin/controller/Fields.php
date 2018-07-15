<?php
namespace app\admin\controller;
use app\admin\controller\Base;
/**
 * 自定义字段
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年3月16日 下午3:17:59 
 * 类说明
 */
class Fields extends Base{
	
	/**
	 * 首页
	 */
	public function index(){
		$Fields = model('Fields');
		$where['school_id'] = 0;
		$data = $Fields->where('flag = 1 and parent_id = 0')->where($where)->order('id asc')->select();
		foreach ($data as $key=>$val){
			$data[$key]['subList'] = (array)$Fields->where('flag = 1 and parent_id = '.$val['id'])->where($where)->select();
		}
		$this->assign('data',$data);
		return $this->fetch();
	}
	
	/**
	 * @authority 新增
	 */
	public function add(){
		$this->assign('parent_id',input('parent_id'));
		return $this->fetch();
	}
	
}