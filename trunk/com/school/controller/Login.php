<?php
namespace app\school\controller;
use think\Controller;
use think\Request;
use think\Session;
use think\Db;
use think\Log;
/**
 * 
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年3月9日 上午10:35:20 
 * 类说明
 */
class Login extends Controller{
	
	Public function index(){
		$this->assign('adminTitle','学校后台管理');
		return $this->fetch();
	}
	
	/**
	 * 验证登录
	 */
	public function checkLogin(){
		$User = model("Member");$School = model('Schools');
		if(!captcha_check(input('post.code'))){
			return $this->ajaxReturn(0,'验证码错误！',0);
		}
		$where['username'] = input('post.tel');
		$where['school_id'] = array('neq',0);
		$userInfo = $User->where($where)->find();
		if (!$userInfo || $userInfo['password'] != md5(input('post.password'))) {
			return $this->ajaxReturn(0,'帐号或密码错误！',0);
		}
		//判断用户是否被锁定
		if($userInfo['lock'] != 1){
			return $this->ajaxReturn(0,'用户被锁定！',0);
		}
		$sinfo = $School->where('id',$userInfo['school_id'])->find();
		if(empty($sinfo)){
			return $this->ajaxReturn(0,'未找到您的学校！',0);
		}
		Session::set('user_school_id',$userInfo['id']);
		Session::set('user_school_tel',$userInfo['username']);
		Session::set('user_school_realname',$userInfo['realname']);
		Session::set('user_school_photo',$userInfo['headimg']);
		Session::set('user_school_is_admin',$userInfo['is_admin']);
		session('schoolInfo',$sinfo);
		session('school_info_id',$sinfo['id']);
		if($result !== false){
			return $this->ajaxReturn(url('school/Index/index'),'登录成功！',1);
		}else{
			return $this->ajaxReturn(0,'登录失败！',0);
		}
	}
	
	//执行退出操作方法
	public function logout(){
		session::clear();
		$this->redirect('Login/index');
	}
	
	/**
	 * 返回
	 * @param string $data
	 * @param string $info
	 * @param number $status
	 * @return multitype:string number
	 */
	public function ajaxReturn($data= array(),$info='',$status=0){
		$result = array(
				'data' => $data,
				'info' => $info,
				'status' => $status
		);
		return json($result);
	}
}