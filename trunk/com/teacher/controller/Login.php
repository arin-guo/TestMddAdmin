<?php
namespace app\teacher\controller;
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
		$User = model("Teachers");
		if(!captcha_check(input('post.code'))){
			return $this->ajaxReturn(0,'验证码错误！',0);
		}
		$where['tel'] = input('post.tel');
		$userInfo = $User->where($where)->find();
		if (!$userInfo || $userInfo['password'] != md5(input('post.password'))) {
			return $this->ajaxReturn(0,'帐号或密码错误！',0);
		}
		//判断老师是否有班级
		$classes_id = Db::view('teacher_class')->where('teacher_id',$userInfo['id'])->where('flag',1)->where('teacher_type',1)->value('classes_id');
        $classes_idr = Db::view('teacher_class')->where('teacher_id',$userInfo['id'])->where('flag',1)->where('teacher_type',3)->value('classes_id');
		if(empty($classes_id) && empty($classes_idr)){
			return $this->ajaxReturn(0,'您还未被分配班级，请联系园长',0);
		}else{
		    if(empty($classes_id)){
		        $classes_id = $classes_idr;
            }
        }
		Session::set('user_teacher_class_id',$classes_id);
		Session::set('user_teacher_id',$userInfo['id']);
		Session::set('school_info_id',$userInfo['school_id']);
		Session::set('user_teacher_tel',$userInfo['tel']);
		Session::set('user_teacher_realname',$userInfo['realname']);
		Session::set('user_teacher_photo',$userInfo['photo']);
		if($result !== false){
			return $this->ajaxReturn(url('teacher/Index/index'),'登录成功！',1);
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