<?php
namespace app\school\controller;
use app\school\controller\Base;
use think\Db;
/**
 * 学生控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月19日 上午10:16:19 
 * 类说明
 */
class Childs extends Base{
	public function index(){
		//获取班级类型
		$Type = model('Type');
		$Subtype = model('Subtype');
		$tId = $Type->where('type_name',10002)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
		if(empty($tId)){
			$this->error('请先设置班级类型，再执行此操作！');
		}
		$vo = $Subtype->where('parent_id',$tId)->where('flag',1)->field('subtype_code,subtype_name')->select();
		$this->assign('vo',$vo);
		
		return $this->fetch();
	}
	
	//列表
	public function getAllData(){
		$Model = model(request()->controller());
		$map = $this->loadSeachCondition();
		$total = $Model->where($map)->count();// 查询满足要求的总记录数
		$page = json_decode($this->pageParam($total));
		$data = $Model->where($map)->limit($page->firstRow,$page->listRows)->order($page->sort)->select();
		$vo = $this->toJosnForGird($data,$page);
		return $vo;
	}
	
	/**
	 * 获取班级名
	 * @return multitype:multitype:string
	 */
	public function getClassName(){
		$cats_code=input('post.id');
		$Class=model('Classes');
		if(!empty($cats_code) && $cats_code != " "){
			$classinfo=$Class->where('cats_code',$cats_code)->where('school_id',session('school_info_id'))->where('flag',1)->field('id,name')->select();
			if(empty($classinfo)){
				return $this->ajaxReturn(0,'此类型下没有具体班级',0);
			}else{
				return $this->ajaxReturn($classinfo,'',1);
			}
		}else{
			return $this->ajaxReturn(0,'不选择具体班级进行搜索',1);
		}
	}
	/**
	 * @authority 转校操作
	 * 如果有一个家长的2个孩子分别转校和毕业,那么以最后一个孩子的状态为准
	 */
	public function changeSchool(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$ids = request()->param($pk);
		if(!empty($ids)){
			$where[$pk] = array('in', explode(',', $ids));
			//查询学生是否绑定了班级
			$cvo = $Model->field('realname,classes_id')->where($where)->select();
			foreach ($cvo as $key => $value){
				//将绑定了班级的学生名字取出来
				if($value[classes_id] != 0){
					$childHasClass[] = $value['realname'];
				}
			}
			if(empty($childHasClass)){
				if(false !== $Model->save(array('status'=>-2),$where)){
					//将学生设置为毕业，修改家长的status状态
					//获取主家长id
					$cid['childs.id'] = array('in', explode(',', $ids));
					$pid = Db::view('childs',['id'=>'cid'])
					->view('parent_child',['id'=>'pcid'],'childs.id=parent_child.child_id')
					->view('parents',['id'=>'pid','type'],'parents.id=parent_child.parent_id')
					->where($cid)
					->select();
					foreach ($pid as $key=>$value){
						if($value['type'] == 1){
							$zid[]=$value['pid'];
						}
					}
					$zid = array_unique($zid);
					//查找主家长下有几个孩子
					//将只有一个孩子的家长,状态直接修改
					$parentChild=model('ParentChild');
					$Parent = model('Parents');
					$zidChild=$parentChild->where('parent_id','in',$zid)->where('flag',1)->group('parent_id')->having('count(child_id)=1')->select();
					if($zidChild != null){
						foreach ($zidChild as $key=>$value){
							$zidOneChild[]=$value['parent_id'];
						}
						$Parent->where('id','in',$zidOneChild)->update(array('status'=>-2));
						$Parent->where('parent_id','in',$zidOneChild)->update(array('status'=>-2));
					}
					//有多个孩子的家长,修改状态
					$zidChildMore=$parentChild->where('parent_id','in',$zid)->where('flag',1)->group('parent_id')->having('count(child_id)>1')->select();
					if($zidChildMore != null){
						foreach ($zidChildMore as $key=>$value){
							$zidMoreChild[]=$value['parent_id'];
						}
						//查找选中家长的孩子还在读的家长的id
						$zidMore = Db::view('childs',['id'=>'cid','status'=>'cstatus'])
						->view('parent_child',['id'=>'pcid'],'childs.id=parent_child.child_id')
						->view('parents',['id'=>'pid','parent_id'=>'zid'],'parents.id=parent_child.parent_id')
						->where('pid','in',$zidMoreChild)
						->where('cstatus','eq',1)
						->where('childs.flag',1)
						->where('parent_child.flag',1)
						->where('parents.flag',1)
						->select();
						//找学生状态没有1的情况下,才改变家长状态
						if($zidMore != null){
							foreach ($zidMore as $key=>$value){
								$zidNoChange[]=$value['pid'];
							}
							$zidNoChange = array_unique($zidNoChange);
							$Parent->where('id','in',$zidMoreChild)->where('id','not in',$zidNoChange)->update(array('status'=>-2));
							$Parent->where('parent_id','in',$zidMoreChild)->where('parent_id','not in',$zidNoChange)->update(array('status'=>-2));
						}else{
							$Parent->where('id','in',$zidMoreChild)->update(array('status'=>-2));
							$Parent->where('parent_id','in',$zidMoreChild)->update(array('status'=>-2));
						}
					}
					return $this->ajaxReturn(1,'操作成功',1);
				}else{
					return $this->ajaxReturn(0,'操作失败',0);
				}
			}else{
			 	$childnames = implode("，",$childHasClass);
			 	return $this->ajaxReturn(0,$childnames.'绑定了班级，请解绑班级后再操作',0);
			}
		}else{
			return $this->ajaxReturn(0,lang('没有选取数据'),0);
		}
	}
	/**
	 * @authority 毕业操作
	 * 如果有一个家长的2个孩子分别转校和毕业,那么以最后一个孩子的状态为准
	 */
	public function graduate(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$ids = request()->param($pk);
		if(!empty($ids)){
			$where[$pk] = array('in', explode(',', $ids));
			if(false !== $Model->save(array('status'=>-1),$where)){
				//将学生设置为毕业，修改家长的status状态
				//获取主家长id
				$cid['childs.id'] = array('in', explode(',', $ids));
				$pid = Db::view('childs',['id'=>'cid'])
				->view('parent_child',['id'=>'pcid'],'childs.id=parent_child.child_id')
				->view('parents',['id'=>'pid','type'],'parents.id=parent_child.parent_id')
				->where($cid)
				->select();
				foreach ($pid as $key=>$value){
					if($value['type'] == 1){
						$zid[]=$value['pid'];
					}
				}
				$zid = array_unique($zid);
				//查找主家长下有几个孩子
				//将只有一个孩子的家长,状态直接修改
				$parentChild=model('ParentChild');
				$Parent = model('Parents');
				$zidChild=$parentChild->where('parent_id','in',$zid)->where('flag',1)->group('parent_id')->having('count(child_id)=1')->select();
				if($zidChild != null){
					foreach ($zidChild as $key=>$value){
						$zidOneChild[]=$value['parent_id'];
					}
					$Parent->where('id','in',$zidOneChild)->update(array('status'=>-1));
					$Parent->where('parent_id','in',$zidOneChild)->update(array('status'=>-1));
				}
				//有多个孩子的家长,修改状态
				$zidChildMore=$parentChild->where('parent_id','in',$zid)->where('flag',1)->group('parent_id')->having('count(child_id)>1')->select();
				if($zidChildMore != null){
					foreach ($zidChildMore as $key=>$value){
						$zidMoreChild[]=$value['parent_id'];
					}
					//查找选中家长的孩子还在读的家长的id
					$zidMore = Db::view('childs',['id'=>'cid','status'=>'cstatus'])
					->view('parent_child',['id'=>'pcid'],'childs.id=parent_child.child_id')
					->view('parents',['id'=>'pid','parent_id'=>'zid'],'parents.id=parent_child.parent_id')
					->where('pid','in',$zidMoreChild)
					->where('cstatus','eq',1)
					->where('childs.flag',1)
					->where('parent_child.flag',1)
					->where('parents.flag',1)
					->select();
					//找学生状态没有1的情况下,才改变家长状态
					if($zidMore != null){
						foreach ($zidMore as $key=>$value){
							$zidNoChange[]=$value['pid'];
						}
						$zidNoChange = array_unique($zidNoChange);
						$Parent->where('id','in',$zidMoreChild)->where('id','not in',$zidNoChange)->update(array('status'=>-1));
						$Parent->where('parent_id','in',$zidMoreChild)->where('parent_id','not in',$zidNoChange)->update(array('status'=>-1));
					}else{
						$Parent->where('id','in',$zidMoreChild)->update(array('status'=>-1));
						$Parent->where('parent_id','in',$zidMoreChild)->update(array('status'=>-1));
					}
				}
				return $this->ajaxReturn(1,'操作成功',1);
			}else{
				return $this->ajaxReturn(0,'操作失败',0);
			}
			
		}else{
			return $this->ajaxReturn(0,lang('没有选取数据'),0);
		}
	}
	/**
	 * @authority 家长信息页
	 */
	public function parentInfo() {
		$id = request()->param('id');
		$vo = Db::view('parent_child','relation')
		->view('parents','realname,tel,id_card,type,sex,address','parents.id = parent_child.parent_id')
		->where('child_id',$id)
        ->where('parents.flag',1)
		->order('type asc')
		->select();
		$this->assign('vo',$vo);
		return $this->fetch('detailParent');
	}
	/**
	 * @authority 打印操作
	 */
	public function mimeograph() {
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		//查询年级名和班级名
		$classid=$vo['classes_id'];
		$classinfo = Db::view('childs','classes_id')
		->view('classes',['id'=>'cid','name'=>'class_name'],'classes.id=childs.classes_id')
		->view('subtype',['id'=>'sid','subtype_name'],'subtype.subtype_code=classes.cats_code')
		->where('childs.classes_id','eq',$classid)
		->find();
		if($classinfo){
			$vo['classname'] = $classinfo['subtype_name'].'-'.$classinfo['class_name'];
		}
		$this->assign('vo',$vo);
		//获取家长的信息
		$cid['childs.id'] = array('eq',$vo['id']);
		$pinfo = Db::view('childs',['id'=>'cid'])
		->view('parent_child',['id'=>'pcid','relation'],'childs.id=parent_child.child_id')
		->view('parents',['id'=>'pid','parent_id'=>'zid','id_card'=>'pid_card','realname'=>'pname','tel','address'],'parents.id=parent_child.parent_id')
		->where($cid)
        ->where('parents.flag',1)
        ->order('parents.create_time asc')
		->select();
		$this->assign('pinfo',$pinfo);
		return $this->fetch('printTagView');
	}
	/**
	 * @authority 详细页
	 */
	public function detailView1() {
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		//查询年级名和班级名
		$classid=$vo['classes_id'];
		$classinfo = Db::view('childs','classes_id')
		->view('classes',['id'=>'cid','name'=>'class_name'],'classes.id=childs.classes_id')
		->view('subtype',['id'=>'sid','subtype_name'],'subtype.subtype_code=classes.cats_code')
        ->view('type',['id'=>'tid'],'type.id = subtype.parent_id')
		->where('childs.classes_id','eq',$classid)
        ->where('type.reserve',session('school_info_id'))
		->find();
		if($classinfo){
			$vo['classname'] = $classinfo['subtype_name'].'-'.$classinfo['class_name'];
		}
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		//获取搜索关键字
		if(!empty(input('cats_code')) && input('cats_code') != ' '){
			if(!empty(input('classes_id'))){
				$map['classes_id'] = array("eq",input('classes_id'));
			}
		}
		if(!empty(input('realname'))){
			$map['realname'] = array("like","%".input('realname')."%");
		}
		if(!empty(input('code'))){
			$map['code'] = array("like","%".input('code')."%");
		}
		if(!empty(input('status')) && input('status') != ' '){
			$map['status'] = array("eq",input('status'));
		}else{
			$map['status'] = 1;
		}
		return $map;
	}
	
	/**
	 * 更改状态
	 */
	public function updateChildCode() {
		$Childs = model('Childs');
		if(empty(input('id'))){
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
		if(empty(input('code'))){
			return $this->ajaxReturn(0,"学号为空！",0);
		}
		//判断学号是否重复
		$map['code'] = input('code');
		$map['school_id'] = session('school_info_id');
		$map['id'] = array('neq',input('id'));
		$map['flag'] = 1;
		$count = $Childs->where($map)->count();
		if($count != 0){
			return $this->ajaxReturn(0,'学号已重复！',0);
		}
		if (false !== $Childs->where('id',input('id'))->setField('code',input('code'))) {
			return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
		} else {
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
	}
	
	/**
	 * @authority 逻辑删除
	 */
	public function logicDel(){
		$Child = model('Childs');
		$childId = input('id');
		//判断孩子是否为无班级状态
		$cinfo = $Child->where('flag',1)->where('id',$childId)->find();
		if(empty($cinfo)){
			return $this->ajaxReturn(0,'未找到孩子信息！',0);
		}
		if(!empty($cinfo['classes_id'])){
			return $this->ajaxReturn(0,'请先解除孩子的班级状态再尝试删除！',0);
		}
		//删除所有孩子的关系
		$Child->where('id',$childId)->setField('flag',2);
		$result = Db::name('ParentChild')->where('child_id',$childId)->where('flag',1)->setField('flag',2);
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
		}
	}
	
	/**
	 * 上传图片类
	 * @return multitype:multitype:string
	 */
	public function uploadImg(){
		$file = request()->file('image');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/child/headimg/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path.$info->getSaveName(),config('app_upload_path'));
			return $this->ajaxReturn($file_path,'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}

	/**
	 * 导出excel视图页面
	 * @return multitype:multitype:string
	 */
	public function export(){
        //获取班级类型
        $Type = model('Type');
        $Subtype = model('Subtype');
        $tId = $Type->where('type_name',10002)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
        if(empty($tId)){
            $this->error('请先设置班级类型，再执行此操作！');
        }
        $vo = $Subtype->where('parent_id',$tId)->where('flag',1)->field('subtype_code,subtype_name')->select();
        $this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * 导出excel
	 */
	public function exportExcel1(){
		$xlsName  = "学生列表";
		$xlsCell  = array(
				array('id','ID'),
				array('unique_code','识别码'),
				array('realname','学生姓名'),
				array('en_name','英文名'),
				array('code','学号'),
				array('id_card','身份证'),
				array('sex','性别'),
				array('age','年龄'),
				array('birthday','生日'),
				array('household','地址'),
				array('ethnicity','民族'),
				array('hobby','爱好'),
				array('body_situation','身体特殊情况'),
				array('allergy_situation','过敏情况'),
				array('status','状态'),
				array('remark','备注'),
				array('create_time','创建时间')
		);
		$xlsModel = model('childs');
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		if(!empty(input('status'))){
			$map['status'] = input('status');
		}
        if(!empty(input('cats_code')) && input('cats_code') != ' '){
            if(!empty(input('classes_id'))){
                $map['classes_id'] = array("eq",input('classes_id'));
            }
        }
		$xlsData  = $xlsModel->where($map)->order('create_time desc')->field('id,realname,unique_code,en_name,code,id_card,sex,age,birthday,household,ethnicity,hobby,body_situation,allergy_situation,status,remark,create_time')->select();
		foreach ($xlsData as $key=>$val){
			$xlsData[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
			switch ($val['sex']) {
				case 1:$xlsData[$key]['sex'] = '男';break;
				case 2:$xlsData[$key]['sex'] = '女';break;
			}
			switch ($val['status']) {
				case 1:$xlsData[$key]['status'] = '正常';break;
				case -1:$xlsData[$key]['status'] = '毕业';break;
				case -2:$xlsData[$key]['status'] = '转学';break;
			}
		}
		exportExcel($xlsName,$xlsCell,$xlsData);
	}
}