<?php
namespace app\school\controller;
use app\school\controller\Base;
use Think\Db;
/**
 * app版本控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月14日 上午10:16:19 
 * 类说明
 */
class Teachers extends Base{
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		//获取搜索关键字
		if(!empty(input('realname'))){
			$map['realname'] = array("like","%".input('realname')."%");
		}
		if(!empty(input('tel'))){
			$map['tel'] = array("like","%".input('tel')."%");
		}
		if(!empty(input('is_job')) && input('is_job') != ' '){
			$map['is_job'] = array("eq",input('is_job'));
		}else{
			$map['is_job'] = 1;
		}
		return $map;
	}
	
	//显示在职员工列表
	public function getAllData(){
		$Subtype = model('Subtype');
		$Model = model(request()->controller());
		$map = $this->loadSeachCondition();
		$total = $Model->where($map)->count();// 查询满足要求的总记录数
		$page = json_decode($this->pageParam($total));
		$data = $Model->where($map)->limit($page->firstRow,$page->listRows)->order($page->sort)->select();
        //教职工分类名称
        $Type = model('Type');
        $tId = $Type->where('type_name',10001)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
        if(empty($tId)){
            $this->error('请先设置数据字段参数，再执行此操作！');
        }
		foreach ($data as $key=>$val){
		    $data[$key]['cats_name'] = $Subtype->where('parent_id',$tId)->where('subtype_code',$val['cats_code'])->value('subtype_name');
		}
		$vo = $this->toJosnForGird($data,$page);
		return $vo;
	}
	//设置员工离职状态(教职工绑定班级,则不可离职)
	public function teacherLeave(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$data = request()->param();
		$data['is_job'] = 2;
		$data['quit_time'] = time();
		$data['update_time'] = time();
		$where[$pk] = $data[$pk];
		//查询教师班级绑定表中是否有老师信息
		$Tclass=model('teacher_class');
		$tmap['flag']=1;
		$tmap['teacher_id']=$data['id'];
		$tcnum=$Tclass->where($tmap)->count();
		if($tcnum){
			return $this->ajaxReturn(0,'教职工已绑定班级，请解绑后再操作',0);
		}else{
			$result = $Model->allowField(true)->save($data,$where);
			if($result !== false){
				return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
			}else{
				return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
			}
		}
	}

	//打开增加员工页面
	public function add(){
		//获取岗位分类
		$Type = model('Type');$Subtype = model('Subtype');
		$tId = $Type->where('type_name',10001)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
		if(empty($tId)){
			$this->error('请先设置数据字段参数，再执行此操作！');
		}
		$vo = $Subtype->where('parent_id',$tId)->where('flag',1)->field('subtype_code,subtype_name')->select();
		$this->assign('vo',$vo);
		return $this->fetch();
	}

	//增加新员工
	public function insert(){
		$Model = model(request()->controller());
		$data = request()->param();
		$data['password'] = md5($data['password']);
		$result = $Model->allowField(true)->save($data);
		if($result){
			return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
		}
	}

	//修改员工信息
	public function edit(){
		//获取岗位分类
		$Type = model('Type');$Subtype = model('Subtype');
		$tId = $Type->where('type_name',10001)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
		if(empty($tId)){
			$this->error('请先设置数据字段参数，再执行此操作！');
		}
		$votype = $Subtype->where('parent_id',$tId)->where('flag',1)->field('subtype_code,subtype_name')->select();
		$this->assign('votype',$votype);
		//获取教职工信息
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$this->assign('vo',$vo);
		return $this->fetch();
	}

	//查看教职工详细信息
	public function detailView() {
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		//获取岗位分类
		$scode['subtype_code'] = $vo['cats_code'];
		$subtype = model('subtype');
		$typename = $subtype->field('subtype_name')->where($scode)->find();
		$this->assign('vo',$vo);
		$this->assign('typename',$typename);
		// var_dump($vo['subtype_name']);
		// exit;
		return $this->fetch();
	}

	//逻辑删除教职工信息(如果绑定班级则不可删除)
	public function logicDel(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$ids = request()->param($pk);
		//查询教师班级绑定表中是否有老师信息
		$Tclass=model('teacherClass');
		$tmap['flag']=1;
		$tmap['teacher_id']=$ids;
		$tcnum=$Tclass->where($tmap)->count();
		if($tcnum){
			return $this->ajaxReturn(0,'教职工已绑定班级，请解绑后再操作',0);
		}else{
			if(!empty($ids)){
				$where[$pk] = array('in', explode(',', $ids));
				if(false !== $Model->save(array('flag'=>2,'update_time'=>time()),$where)){
					return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
				}else{
					return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
				}
			}else{
				return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
			}
		}
	}

	//上传图片
	public function uploadImg(){
		$file = request()->file('image');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/teacher/headimg/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path,config('app_upload_path'));
			return $this->ajaxReturn($file_path.$info->getSaveName(),'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}
	
	/**
	 * 检查字段是否可用
	 * 全局唯一，部分学校
	 */
	public function checkValueForAll(){
		$Model = model(request()->controller());
		$value = input('param');$field = input('name');
		$map[$field] = $value;
		if(input('type') == 1){
			$map[$Model->getPk()] = array('neq',input($Model->getPk()));
		}
		if(input('flag') != -1){
			$map['flag'] = 1;
		}
		$count = $Model->where($map)->count();
		if($count){
			return $this->ajaxReturn(0,'字段值已存在，请更换！','n');
		}else{
			return $this->ajaxReturn(1,'','y');
		}
	}
}