<?php
namespace app\school\controller;
use app\school\controller\Base;
use think\Db;
/**
 * 班级管理
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年9月13日 下午3:53:35 
 * 类说明
 */
class Classes extends Base{
	
	public function index(){
		//获取班级类型
		$Type = model('Type');$Subtype = model('Subtype');
		$tId = $Type->where('type_name',10002)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
		if(empty($tId)){
			$this->error('请先设置数据字段参数，再执行此操作！');
		}
		$vo = $Subtype->where('parent_id',$tId)->where('flag',1)->field('subtype_code,subtype_name')->select();
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * 组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		if(input('cats_code') != " " && !empty(input('cats_code'))){
			$map['cats_code'] = input('cats_code');
		}
		return $map;
	}
	
	//列表
	public function getAllData(){
		$Type = model('Type');$Subtype = model('Subtype');$TeacherClass = model('TeacherClass');
		$Model = model(request()->controller());
		$map = $this->loadSeachCondition();
		$total = $Model->where($map)->count();// 查询满足要求的总记录数
		$page = json_decode($this->pageParam($total));
		$data = $Model->where($map)->limit($page->firstRow,$page->listRows)->order($page->sort)->select();
		$tId = $Type->where('type_name',10002)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
		foreach ($data as $key=>$val){
			$data[$key]['cats_name'] = $Subtype->where('subtype_code',$val['cats_code'])->where('parent_id',$tId)->value('subtype_name');
			//获取班主任与阿姨
			$data[$key]['teacherName'] = Db::view('TeacherClass','teacher_type')->view('Teachers','realname','TeacherClass.teacher_id = Teachers.id')
										->where('TeacherClass.flag',1)->where('TeacherClass.teacher_type',1)->where('classes_id',$val['id'])->value('realname');
			//获取班主任与阿姨
			$data[$key]['auntName'] = Db::view('TeacherClass','teacher_type')->view('Teachers','realname','TeacherClass.teacher_id = Teachers.id')
									->where('TeacherClass.flag',1)->where('TeacherClass.teacher_type',2)->where('classes_id',$val['id'])->value('realname');
			//获取班主任与阿姨
			$data[$key]['subTeacherName'] = Db::view('TeacherClass','teacher_type')->view('Teachers','realname','TeacherClass.teacher_id = Teachers.id')
									->where('TeacherClass.flag',1)->where('TeacherClass.teacher_type',3)->where('classes_id',$val['id'])->value('realname');
			//获取男女比例
			$data[$key]['count'] = Db::name('Childs')->where('flag',1)->where('classes_id',$val['id'])->where('school_id',session('school_info_id'))->where('status',1)->count();
		}
		$vo = $this->toJosnForGird($data,$page);
		return $vo;
	}
	
	/**
	 * @authority 新增
	 */
	public function add(){
		//获取班级类型
		$Type = model('Type');$Subtype = model('Subtype');$Teachers = model('Teachers');$TeacherClass = model('TeacherClass');
		$tId = $Type->where('type_name',10002)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
		if(empty($tId)){
			$this->error('请先设置数据字段参数，再执行此操作！');
		}
		$vo['type'] = $Subtype->where('parent_id',$tId)->where('flag',1)->field('subtype_code,subtype_name')->select();
		//获取所有教师与带班阿姨
		$ids = $TeacherClass->where('school_id',session('school_info_id'))->where('flag',1)->value('GROUP_CONCAT(teacher_id)');
		$map['flag'] = 1;
		$map['is_job'] = 1;
		$map['school_id'] = session('school_info_id');
		//阿姨可以带多个班级
		$map['cats_code'] = 10002;
		$vo['aunt'] = $Teachers->where($map)->field('id,realname')->select();
		if(!empty($ids)){
			$map['id'] = array('not in',$ids);
		}
		$map['cats_code'] = 10001;
		$vo['teacher'] = $Teachers->where($map)->field('id,realname')->select();
		$map['cats_code'] = 10003;
		$vo['subTeacher'] = $Teachers->where($map)->field('id,realname')->select();
		$this->assign('id',input('id'));
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * @authority 新增方法
	 */
	public function insert(){
		$Model = model(request()->controller());$TeacherClass = model('TeacherClass');
		$data = request()->param();
		$result = $Model->allowField(true)->save($data);
		if($result){
			//把教师与跟班阿姨绑定班级
			$tdata[0]['classes_id'] = $Model->id;
			$tdata[0]['teacher_id'] = input('teacher_id');
			$tdata[0]['school_id'] = session('school_info_id');
			$tdata[0]['teacher_type'] = 1;
			//保育阿姨为可选
			if(!empty(input('aunt_id'))){
				$tdata[1]['classes_id'] =  $Model->id;
				$tdata[1]['teacher_id'] = input('aunt_id');
				$tdata[1]['school_id'] = session('school_info_id');
				$tdata[1]['teacher_type'] = 2;
			}
			if(!empty(input('subTeacer_id'))){
				$data[2]['classes_id'] = $Model->id;
				$data[2]['teacher_id'] = input('subTeacer_id');
				$data[2]['school_id'] = session('school_info_id');
				$data[2]['teacher_type'] = 3;
			}
			$TeacherClass->isUpdate(false)->saveAll($tdata);
			return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
		}
	}
	
	/**
	 * 检查班号
	 */
	public function checkClassNo(){
		$Model = model(request()->controller());
		$map['name'] = input('param');
		$map['school_id'] = session('school_info_id');
		$map['cats_code'] = input('cats_code');
		if(input('type') == 1){
			$map['id'] = array('neq',input('id'));
		}
		$map['flag'] = 1;
		$count = $Model->where($map)->count();
		if($count){
			return $this->ajaxReturn(0,'字段值已存在，请更换！',0);
		}else{
			return $this->ajaxReturn(1,'',1);
		}
	}
	
	
	/**
	 * 检查教师是否绑定
	 */
	public function checkTeacher(){
		$TeacherClass = model('TeacherClass');
		$value = input('param');
		$map['school_id'] = session('school_info_id');
		$map['teacher_id'] = $value = input('param');
		$map['flag'] = 1;
		if(!empty(input('classes_id'))){
			$map['classes_id'] = array('neq',input('classes_id'));
		}
		$count = $TeacherClass->where($map)->count();
		if($count){
			return $this->ajaxReturn(0,'每位班主任只能绑定一个班级，请选择其他教职工！','n');
		}else{
			return $this->ajaxReturn(1,'','y');
		}
	}
	
	/**
	 * 绑定老师
	 */
	public function teacher(){
		$Teachers = model('Teachers');$TeacherClass = model('TeacherClass');
        //获取岗位分类
        $Type = model('Type');$Subtype = model('Subtype');
        $tId = $Type->where('type_name',10001)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
        if(empty($tId)){
            $this->error('请先设置数据字段参数，再执行此操作！');
        }
        $typename = $Subtype->where('parent_id',$tId)->where('subtype_code','in',[10001,10002,10003])->where('flag',1)->field('subtype_code,subtype_name')->select();
		//获取所有教师与带班阿姨
		$ids = $TeacherClass->where('school_id',session('school_info_id'))->where('classes_id','neq',input('id'))->where('flag',1)->value('GROUP_CONCAT(teacher_id)');
		$map['flag'] = 1;
		$map['is_job'] = 1;
		$map['school_id'] = session('school_info_id');
		//一个阿姨可以绑定多个班级
		$map['cats_code'] = 10002;
		$vo['aunt'] = $Teachers->where($map)->field('id,realname')->select();
		//班主任和任课老师只能绑定一个班级
		if(!empty($ids)){
			$map['id'] = array('not in',$ids);
		}
		$map['cats_code'] = 10001;
		$vo['teacher'] = $Teachers->where($map)->field('id,realname')->select();
		$map['cats_code'] = 10003;
		$vo['subTeacher'] = $Teachers->where($map)->field('id,realname')->select();
		//获取当前班级绑定的老师
		$vo['chk_teacher_id'] = $TeacherClass->where('school_id',session('school_info_id'))->where('classes_id',input('id'))->where('flag',1)->where('teacher_type',1)->value('teacher_id');
		$vo['chk_aunt_id'] = $TeacherClass->where('school_id',session('school_info_id'))->where('classes_id',input('id'))->where('flag',1)->where('teacher_type',2)->value('teacher_id');
		$vo['chk_subteacher_id'] = $TeacherClass->where('school_id',session('school_info_id'))->where('classes_id',input('id'))->where('flag',1)->where('teacher_type',3)->value('teacher_id');
		$this->assign('id',input('id'));
		$this->assign('vo',$vo);
        $this->assign('typename',$typename);
		return $this->fetch();
	}
	
	/**
	 * 绑定班级老师
	 * 每个班主任只能被绑定一个班级
	 * 跟班阿姨可不选或多选
	 */
	public function buildClassTeacher(){
		$TeacherClass = model('TeacherClass');
//		if(empty(input('teacher_id'))){
//			return $this->ajaxReturn(0,'参数错误！',0);
//		}
		//判断班主任是否已经被选择过
		$count = $TeacherClass->where('school_id',session('school_info_id'))->where('classes_id','neq',input('classes_id'))->where('flag',1)->where('teacher_id',input('teacher_id'))->count();
		if($count != 0){
			return $this->ajaxReturn(0,'每位班主任只能绑定一个班级，请关闭页面重试！',0);
		}
		//删除本班全部的老师
		$TeacherClass->where('classes_id',input('classes_id'))->delete();
		$data[0]['classes_id'] = input('classes_id');
		$data[0]['teacher_id'] = input('teacher_id');
		$data[0]['school_id'] = session('school_info_id');
		$data[0]['teacher_type'] = 1;
		//保育阿姨为可选
		if(!empty(input('aunt_id'))){
			$data[1]['classes_id'] = input('classes_id');
			$data[1]['teacher_id'] = input('aunt_id');
			$data[1]['school_id'] = session('school_info_id');
			$data[1]['teacher_type'] = 2;
		}
		if(!empty(input('subTeacer_id'))){
			$data[2]['classes_id'] = input('classes_id');
			$data[2]['teacher_id'] = input('subTeacer_id');
			$data[2]['school_id'] = session('school_info_id');
			$data[2]['teacher_type'] = 3;
		}
		$result = $TeacherClass->isUpdate(false)->saveAll($data);
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
		} else {
			return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
		}
	}
	
	/**
	 * @authority 修改
	 */
	public function edit(){
		$Model = model(request()->controller());$Type = model('Type');$Subtype = model('Subtype');
		//获取班级类型
		$tId = $Type->where('type_name',10002)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
		if(empty($tId)){
			$this->error('请先设置数据字段参数，再执行此操作！');
		}
		$cats['type'] = $Subtype->where('parent_id',$tId)->where('flag',1)->field('subtype_code,subtype_name')->select();
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$this->assign('cats',$cats);
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * 学生列表
	 */
	public function studentList(){
		$this->assign('id',input('id'));
		return $this->fetch();
	}
	
	/**
	 * 获取学生列表数据
	 */
	public function getAllStudentData(){
		$Child = model('Childs');
		$map['classes_id'] = input('get.id');
		$map['school_id'] = session('school_info_id');
		$map['flag'] = 1;
		$total = $Child->where($map)->count();// 查询满足要求的总记录数
		$page = json_decode($this->pageParam($total));
		$data = $Child->where($map)->limit($page->firstRow,$page->listRows)->order($page->sort)->select();
		$vo = $this->toJosnForGird($data,$page);
		return $vo;
	}
	
	/**
	 * @authority 
	 */
	public function logicDel(){
		$Child = model('Childs');$Model = model(request()->controller());$TeacherClass = model('TeacherClass');
		$id = request()->param('id');
		//判断当前班级是否有学生
		$count = $Child->where('flag',1)->where('classes_id',$id)->where('school_id',session('school_info_id'))->count();
		if($count != 0){
			return $this->ajaxReturn(0,"当前班级还有在读的学生，请操作学生为毕业或转校后再继续操作",0);
		}
		if(!empty($id)){
			$where['id'] = array('in', explode(',', $id));
			if(false !== $Model->save(array('flag'=>2),$where)){
				//删除后需要解绑老师与保育阿姨
				$TeacherClass->where('classes_id',$id)->delete();
				return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
			}else{
				return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
			}
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
		}
	}
	
	/**
	 * 自动分班
	 */
	public function autoClass(){
		//获取班级类型
		$Type = model('Type');$Subtype = model('Subtype');
		$tId = $Type->where('type_name',10002)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
		if(empty($tId)){
			$this->error('请先设置数据字段参数，再执行此操作！');
		}
		$vo = $Subtype->where('parent_id',$tId)->where('flag',1)->field('subtype_code,subtype_name')->select();
		//获取无班级学生
		$Child = model('Childs');
		$data = $Child->where('flag',1)->where('classes_id',0)->where('school_id',session('school_info_id'))->where('status',1)->field('id,realname,sex,code')->select();
		$this->assign('data',$data);
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * 根据班级类型获取班级列表
	 */
	public function getClassByCatscode(){
		$Class = model('Classes');
		$data = $Class->where('cats_code',input('cats_code'))->where('school_id',session('school_info_id'))->where('flag',1)->field('id,name')->select();
		return $this->ajaxReturn($data,"获取成功！",1);
	}
	
	/**
	 * 根据班级Id获取学生
	 */
	public function getChildByClassId(){
		$Child = model('Childs');
		$data = $Child->where('flag',1)->where('classes_id',input('class_id'))->where('school_id',session('school_info_id'))->where('status',1)->field('id,realname,sex,code')->select();
		return $this->ajaxReturn($data,"获取成功！",1);
	}
	
	/**
	 * 设置学生为无班级状态
	 */
	public function setClildClassId(){
		$Child = model('Childs');
		if(empty(input('ids'))){
			return $this->ajaxReturn(0,'参数错误！',0);
		}
		$result = $Child->where('id','in',input('ids'))->where('school_id',session('school_info_id'))->setField('classes_id',input('classId'));
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
		} else {
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
	}
	
	/**
	 * 直播设备
	 */
	public function camera(){
		$ClassLive = model('ClassLive');
		$vo = $ClassLive->where('flag',1)->where('class_id',input('id'))->find();
		$this->assign('vo',$vo);
		$this->assign('id',input('id'));
		return $this->fetch();
	}
	
	/**
	 * 更新设备信息
	 */
	public function updateCamera(){
		$ClassLive = model('ClassLive');
		$param = request()->param();
		if(empty($param['class_id'])){
			return $this->ajaxReturn(0,'参数错误！',0);
		}
		$data['class_id'] = input('class_id');
		$data['is_voice'] = input('is_voice');
		$data['open_time'] = input('open_time');
		$data['close_time'] = input('close_time');
		if(empty($param['id'])){
			$result = $ClassLive->save($data);
		}else{
			$result = $ClassLive->save($data,['id'=>input('id')]);
		}
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
		} else {
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
	}
	
	
}