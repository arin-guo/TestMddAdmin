<?php
namespace app\teacher\controller;
use app\teacher\controller\Base;
use think\Db;
/**
 * 班级课程控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月27日 上午10:16:19 
 * 类说明
 */
class CourseTimeClass extends Base{
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		//获取搜索关键字
		if(!empty(input('weeks')) && input('weeks') != " " ){
			$map['weeks'] = input('weeks');
		}
	if(!empty(input('title')) && input('title') != " " ){
			$map['title'] = input('title');
		}
		if(!empty(input('name'))){
			$map['name'] = array("like","%".input('name')."%");
		}
		return $map;
	}
	/**
	 * @authority 课程列表
	 */
	public function getAllData(){
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
		$map = $this->loadSeachCondition();
		//查询满足要求的总记录数
		$total = Db::view('CourseTimeClass','id')
		->view('Course','name','CourseTimeClass.course_id = Course.id')
		->view('CourseTime','title,weeks,begin_time,end_time','CourseTimeClass.course_time_id = CourseTime.id')
		->where('Course.flag',1)
		->where('CourseTime.flag',1)
		->where('CourseTimeClass.class_id',$cid['classes_id'])
		->where($map)
		->count();
		$page = json_decode($this->pageParam($total));
		//获取课程时间安排信息
		$data=Db::view('CourseTimeClass','id')
		->view('Course','name','CourseTimeClass.course_id = Course.id')
		->view('CourseTime','title,weeks,begin_time,end_time','CourseTimeClass.course_time_id = CourseTime.id')
		->where('Course.flag',1)
		->where('CourseTime.flag',1)
		->where('CourseTimeClass.class_id',$cid['classes_id'])
		->where($map)
		->limit($page->firstRow,$page->listRows)
		->order($page->sort)
		->order('weeks asc')
		->order('title asc')
		->select();
		$vo = $this->toJosnForGird($data,$page);
		return $vo;
	}
	/**
	 * @authority 新增
	 */
	public function add(){
		$where['school_id'] = session('school_info_id');
		$tid = session('user_teacher_id');
		//获取教师的班级id
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
		//获取被绑定的课程id和课程时间id
		$ctc=model('CourseTimeClass');
		$ctcid=$ctc->where($where)->where('class_id',$cid['classes_id'])->select();
		foreach ($ctcid as $value){
			$coursetimeid[] = $value['course_time_id'];
		}
		$course=model('Course');
		$cou=$course->where($where)->where('flag',1)->where('is_on',1)->select();
		$coursetime=model('CourseTime');
		if($coursetimeid == null){
            $coutime=$coursetime->where($where)->where('flag',1)->order('weeks asc')->select();
        }else{
            $coutime=$coursetime->where($where)->where('flag',1)->where('id','not in',$coursetimeid)->order('weeks asc')->select();
        }
		$this->assign('classid',$cid['classes_id']);
		$this->assign('cou',$cou);
		$this->assign('coutime',$coutime);
		return $this->fetch();
	}
	/**
	 * @authority 修改
	 */
	public function edit(){
		//获取course_time_class表的信息
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$this->assign('vo',$vo);
		//获取教师的班级id
		$where['school_id'] = session('school_info_id');
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
		//获取被绑定的课程id和课程时间id
		$ctcid=$Model->where($where)->where('class_id',$cid['classes_id'])->select();
		foreach ($ctcid as $value){
			$coursetimeid[] = $value['course_time_id'];
		}
		foreach ($courseid as $k=>$v){
			if($vo['course_id'] == $v){
				unset($courseid[$k]);
			}
		}
		foreach ($coursetimeid as $k=>$v){
			if($vo['course_time_id'] == $v){
				unset($coursetimeid[$k]);
			}
		}
		$course=model('Course');
		$cou=$course->where($where)->where('flag',1)->where('is_on',1)->select();
		$coursetime=model('CourseTime');
		$coutime=$coursetime->where($where)->where('flag',1)->where('id','not in',$coursetimeid)->order('weeks asc')->select();
		$this->assign('cou',$cou);
		$this->assign('coutime',$coutime);
		return $this->fetch();
	}
}