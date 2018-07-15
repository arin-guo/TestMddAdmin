<?php
namespace app\school\controller;
use app\school\controller\Base;
use think\Exception;
/**
 * 后台账户管理
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年3月22日 下午4:11:59 
 * 类说明
 */
class Member extends Base{
	
	/**
	 * 组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['use_type'] = 2;
		$map['is_admin'] = array('neq',1);
		$map['school_id'] = session('school_info_id');
		if(!empty(input('realname'))){
			$map['realname'] = array("like","%".input('realname')."%");
		}
		return $map;
	}
	
	/**
	 * @authority 新增
	 */
	public function add(){
		$Model = model('AuthGroup');
		$map['status'] = 1;
		$map['use_type'] = 2;
		$map['school_id'] = session('school_info_id');
		$vo = $Model->where($map)->select();
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * @authority 新增方法
	 */
	public function insert(){
		$Model = model('Member');$AuthGroupAccess = model('AuthGroupAccess');
		$data = request()->param();
		$data['school_id'] = session('school_info_id');
		$result = $Model->allowField(true)->save($data);
		if($result){
			$groups = explode(",",$data['groups']);
			$accData = Array();$i = 0;
			foreach ($groups as $val){
				$accData[$i]['uid'] = $Model->id;
				$accData[$i]['group_id'] = $val;
				$i ++;
			}
			$AuthGroupAccess->saveAll($accData);
			return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
		}
		
	}
	
	/**
	 * @authority 修改
	 */
	public function edit(){
		$Model = model('Member');$AuthGroup = model('AuthGroup');$AuthGroupAccess = model('AuthGroupAccess');
		$vo = $Model::get(input('id'));
		//获取所有权限角色
		$map['status'] = 1;
		$map['use_type'] = 2;
		$map['school_id'] = session('school_info_id');
		$groupList = $AuthGroup->where($map)->select();
		//获取当前用户拥有权限
		$map2['uid'] = input('id');
		$userGroups = $AuthGroupAccess->where($map2)->value("group_concat(group_id)");
		$userGroups = explode(',',$userGroups);
		$this->assign('groupList',$groupList);
		$this->assign('userGroups',$userGroups);
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * @authority 更新方法
	 */
	public function update() {
		$Model = model('Member');$AuthGroupAccess = model('AuthGroupAccess');
		$pk = $Model->getPk();
		$data = request()->param();
		$where['id'] = $data['id'];
		$result = $Model->allowField(true)->save($data,$where);
		if($result !== false){
			$groups = explode(",",$data['groups']);
			$accData = Array();$i = 0;
			foreach ($groups as $val){
				$accData[$i]['uid'] = $data['id'];
				$accData[$i]['group_id'] = $val;
				$i ++;
			}
			//删除旧数据
			$map['uid'] = $data['id'];
			$AuthGroupAccess->where($map)->delete();
			$AuthGroupAccess->saveAll($accData);
			return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
	}
	
	/**
	 * @authority 逻辑删除
	 */
	public function logicDel(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$ids = request()->param($pk);
		$is_admin = $Model->where('id',$ids)->value('is_admin');
		if($is_admin == 1){
			return $this->ajaxReturn(0,"该账号为超级管理员，无法删除！",0);
		}
		if(!empty($ids)){
			$where[$pk] = array('in', explode(',', $ids));
			if(false !== $Model->save(array('flag'=>2),$where)){
				return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
			}else{
				return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
			}
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
		}
	}
	
	/**
	 * @authority 修改密码
	 */
	public function editPwd(){
		$Model = model('Member');
		$where['id'] = input('id');
		$vo = $Model->where($where)->find();
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * 检查用户名是否重复
	 */
	public function checkValue(){
		$Model = model(request()->controller());
		$value = input('param');$field = input('name');
		$map[$field] = $value;
		if(input('type') == 1){
			$map[$Model->getPk()] = array('neq',input($Model->getPk()));
		}
		if(input('flag') != -1){
			$map['flag'] = 1;
		}
        $map['use_type'] = 2;
		$count = $Model->where($map)->count();
		if($count){
			return $this->ajaxReturn(0,'用户名已存在，请更换！','n');
		}else{
			return $this->ajaxReturn(1,'','y');
		}
	}
	
	/**
	 * @authority 上传图片
	 */
	public function uploadImg(){
		$file = request()->file('image');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = '/uploads/headimg/';
		$info = $file->validate($valid)->rule('date')->move(ROOT_PATH.'public'.$path);
		if($info){
			return $this->ajaxReturn($path.$info->getSaveName(),'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}
}