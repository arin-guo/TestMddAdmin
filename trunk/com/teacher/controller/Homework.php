<?php
namespace app\teacher\controller;
use app\teacher\controller\Base;
use think\Db;
/**
 * 作业控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月30日 上午10:16:19 
 * 类说明
 */
class Homework extends Base{
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag']=1;
        $map['school_id'] = session('school_info_id');
		//获取教师的班级id
		$tid = session('user_teacher_id');
		$cid=Db::view('teacher_class')
		->where('teacher_id',$tid)
		->where('flag',1)
		->where('teacher_type',1)
		->find();
        $cidr=Db::view('teacher_class')
            ->where('teacher_id',$tid)
            ->where('flag',1)
            ->where('teacher_type',3)
            ->find();
		if(empty($cid) && empty($cidr)){
			return $this->err('您还未被分配班级，请联系园长');
		}else{
		    if(empty($cid)){
		        $cid = $cidr;
            }
        }
		$map['class_id']=$cid['classes_id'];
		//获取搜索关键字
		if(!empty(input('title'))){
			$map['title'] = array("like","%".input('title')."%");
		}
		return $map;
	}
	/**
	 * @authority 新增方法
	 */
	public function insert(){
		$Model = model(request()->controller());
		$data = request()->param();
		//获取教师的班级id
		$tid = $data['teacher_id'];
		$cid=Db::view('teacher_class')
		->where('teacher_id',$tid)
		->where('flag',1)
		->where('teacher_type',1)
		->find();
        $cidr=Db::view('teacher_class')
            ->where('teacher_id',$tid)
            ->where('flag',1)
            ->where('teacher_type',3)
            ->find();
		if(empty($cid) && empty($cidr)){
			return $this->ajaxReturn(0,'您还未被分配班级，请联系园长',0);
		}else {
		    if(empty($cid)){
		        $cid = $cidr;
            }
		}
		//检查班级有没有学生
        $child = model('childs');
        $childid=$child->where('flag',1)->where('status',1)->where('classes_id',$cid['classes_id'])->value('GROUP_CONCAT(id)');
		if($childid != 0){
            Db::startTrans();
            try{
                $data['school_id'] = session('school_info_id');
                $data['class_id']=$cid['classes_id'];
                $Model->allowField(true)->isUpdate(false)->save($data);
                //获取到班级的所有学生id,然后批量添加待提交数据
                $hwchild=model('homework_child');
                $childid=explode(',',$childid);
                foreach($childid as $key=>$val){
                    $hwcid[$key]['child_id'] = $val;
                    $hwcid[$key]['homework_id'] = $Model->id;
                    $hwcid[$key]['flag'] = 1;
                    $hwcid[$key]['status'] =0;
                }
                $hwchild->saveAll($hwcid);
                Db::commit();
                return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
            }catch(Exception $e){
                Db::roolback();
                return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
            }
        }else{
            return $this->ajaxReturn(0,'您的班级暂无学生',0);
        }

	}
	/**
	 * @authority 详细
	 */
	public function detailView() {
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$homeworkimg = explode('|',$vo['img']);
		$this->assign('homeworkimg',$homeworkimg);
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	/**
	 * @authority 获取学生作业列表
	 */
	public function getChildAllData(){
		$Model = model('homework_child');
		//$map = $this->loadSeachCondition();
		$map['homework_id'] = input('id');
		$map['flag'] = 1;
		$total = $Model->where($map)->count();// 查询满足要求的总记录数
		$page = json_decode($this->pageParam($total));
		$data = Db::view('homework_child','id,task_content,task_img,status,eval_star,eval,create_time,update_time')
		->view('childs','realname','childs.id = homework_child.child_id')
		->where('homework_child.flag',1)
		->where('homework_id',$map['homework_id'])
		->limit($page->firstRow,$page->listRows)
		->order('status asc')
		->order($page->sort)
		->select();
		$vo = $this->toJosnForGird($data,$page);
		return $vo;
	}
	/**
	 * @authority 修改学生作业(批改作业)视图层
	 */
	public function editChildWork(){
		$Model = model('homework_child');
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		//获取学生的姓名以及作业标题
		$child = model('childs');
		$vo['realname'] = $child->where('id',$vo['child_id'])->value('realname');
		$homework = model('homework');
		$vo['title'] = $homework->where('id',$vo['homework_id'])->value('title');
		$homeworkimg = explode('|',$vo['task_img']);
		$this->assign('homeworkimg',$homeworkimg);
		$this->assign('vo',$vo);
		return $this->fetch('editChild');
	}
	/**
	 * @authority 修改学生作业(批改作业)
	 */
	public function updateChildWork(){
		$Model = model('homework_child');
		$pk = $Model->getPk();
		$data = request()->param();
		$where[$pk] = $data[$pk];
		$result = $Model->allowField(true)->save($data,$where);
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
	}
	/**
	 * @authority 学生作业详细信息
	 */
	public function detailViewChild() {
		$Model = model('homework_child');
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		//获取学生的姓名以及作业标题
		$child = model('childs');
		$vo['realname'] = $child->where('id',$vo['child_id'])->value('realname');
		$homework = model('homework');
		$vo['title'] = $homework->where('id',$vo['homework_id'])->value('title');
		$homeworkimg = explode('|',$vo['task_img']);
		$this->assign('homeworkimg',$homeworkimg);
		$this->assign('vo',$vo);
		return $this->fetch('detailViewChild');
	}
	/**
	 * 上传图片
	 * @return multitype:multitype:string
	 */
	public function uploadImg(){
		$file = request()->file('image');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/homework/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path,config('app_upload_path'));
			return $this->ajaxReturn($file_path.$info->getSaveName(),'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}
}