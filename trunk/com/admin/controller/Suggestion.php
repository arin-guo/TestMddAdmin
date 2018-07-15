<?php
namespace app\admin\controller;
use app\admin\controller\Base;
/**
 * 意见反馈
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年8月7日 下午4:13:11 
 * 类说明
 */
class Suggestion extends Base{
	/**
	 * 组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = 0;//非0为园长信箱
		if(!empty(input('tel'))){
			$map['tel'] = array("like","%".input('tel')."%");
		}
		return $map;
	}
}