<?php
namespace app\school\controller;
/**
 * 
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年3月9日 上午10:28:31 
 * 类说明
 */
class Index extends Base{
	
    public function index(){
    	return $this->fetch();
    }
    
    Public function main(){
    	return $this->fetch();
    }
    
    public function editPwd(){
    	return $this->fetch();
    }
    
    /**
     * 修改密码
     */
    public function updatePwd() {
    	$Model = model('Member');
    	$where['id'] = input('id');
    	$data['password'] = input('password');
    	$vo = $Model::get(input('id'));
    	if(input('oldpassword') != $vo['password']) {
    		return $this->ajaxReturn(0,"旧密码错误，请检查后重新提交",0);
    	}
    	if(false !== $Model->save($data,$where)) {
    		return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
    	} else {
    		return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
    	}
    }
    
}