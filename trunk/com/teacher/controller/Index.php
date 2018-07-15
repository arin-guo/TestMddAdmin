<?php
namespace app\teacher\controller;
use app\teacher\controller\Base;
use think\Db;
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
    	$is_open = Db::name('Classes')->where('id',session("user_teacher_class_id"))->value('is_open');
    	$this->assign('is_open',$is_open);
    	return $this->fetch();
    }
    
    public function editPwd(){
    	return $this->fetch();
    }
    
    /**
     * 修改密码
     */
    public function updatePwd() {
    	$Model = model('Teachers');
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
    
    /**
     * @authority 修改方法
     */
    public function updateClassStatus(){
    	$Model = model('Classes');
    	$result = $Model->allowField(true)->save(['is_open'=>input('is_open')],['id'=>session('user_teacher_class_id')]);
    	if($result !== false){
    		return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
    	}else{
    		return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
    	}
    }
    
}