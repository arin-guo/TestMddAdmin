<?php
namespace app\admin\controller;
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
		$this->assign('adminTitle',getFields('app_title'));
		return $this->fetch();
	}
	
	/**
	 * 验证登录
	 */
	public function checkLogin(){
		$Member = model("Member");
		if(!captcha_check(input('post.code'))){
			return $this->ajaxReturn(0,'验证码错误！',0);
		}
		$where['username'] = input('post.username');
		$where['school_id'] = 0;
		$userInfo = $Member->where($where)->find();
		if (!$userInfo || $userInfo['password'] != md5(input('post.password'))) {
			return $this->ajaxReturn(0,'帐号或密码错误！',0);
		}
		//判断用户是否被锁定
		if($userInfo['lock'] != 1){
			return $this->ajaxReturn(0,'用户被锁定！',0);
		}
		$data['logintime'] = time();
		$data['loginip'] = request()->ip();
		$result = $Member->where($where)->update($data);
		Session::set('user_admin_id',$userInfo['id']);
		Session::set('user_admin_username',$userInfo['username']);
		Session::set('user_admin_realname',$userInfo['realname']);
		Session::set('user_admin_headimg',$userInfo['headimg']);
		Session::set('user_is_admin',$userInfo['is_admin']);
		//记录日志
		writeAdminLog(1);
		if($result !== false){
			return $this->ajaxReturn(url('admin/Index/index'),'登录成功！',1);
		}else{
			return $this->ajaxReturn(0,'登录失败！',0);
		}
	}
	
	//执行退出操作方法
	public function logout(){
		//写入行为日志
		writeAdminLog(-1);
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