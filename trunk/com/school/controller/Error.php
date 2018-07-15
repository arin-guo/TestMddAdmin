<?php
namespace app\school\controller;
use think\Controller;
/**
 * 空类
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2015年12月29日 下午3:17:32 
 * 类说明
 */
class Error extends Controller {
	//空模块  /abc
	public function _empty(){
		return $this->fetch(ROOT_PATH.'public/static/error/404.html');		
    }
}