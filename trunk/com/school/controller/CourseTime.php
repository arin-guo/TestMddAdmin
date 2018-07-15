<?php
namespace app\school\controller;
use app\school\controller\Base;
use think\Db;
/**
 * 课程管理控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月25日 下午16:16:19 
 * 类说明
 */
class CourseTime extends Base{
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		//获取搜索关键字
		if(!empty(input('weeks')) && input('weeks') != " " ){
			$map['weeks'] = input('weeks');
		}
		if(!empty(input('title')) && input('title') != " " ){
			$map['title'] = input('title');
		}
		return $map;
	}
    /**
     * 检查字段是否可用
     * 同一天同一节课不能重复，
     */
    public function checkValueCT(){
        $Model = model(request()->controller());
        $getinfo = input('get.');
        $map['weeks'] = $getinfo['weeks'];
        $map['title'] = $getinfo['title'];

        $map['school_id'] = session('school_info_id');
        if(input('type') == 1){
            $map[$Model->getPk()] = array('neq',input($Model->getPk()));
        }
        if(input('flag') != -1){
            $map['flag'] = 1;
        }
        $count = $Model->where($map)->count();
        if($count){
            return $this->ajaxReturn(0,'当天课程节次已存在，请更换！','n');
        }else{
            return $this->ajaxReturn(1,'','y');
        }
    }
	/**
	 * @authority 逻辑删除
	 */
	public function logicDel(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$ids = request()->param($pk);
		if(!empty($ids)){
			$cinfo=Db::view('CourseTimeClass','id')
			->view('Course','name','CourseTimeClass.course_id = Course.id')
			->view('CourseTime','title,weeks,begin_time,end_time','CourseTimeClass.course_time_id = CourseTime.id')
			->view('Classes',['name'=>'classname'],'CourseTimeClass.class_id = Classes.id')
			->view('Subtype',['subtype_name'=>'classtype'],'Classes.cats_code = Subtype.subtype_code')
			->where('Course.flag',1)
			->where('CourseTime.flag',1)
			->where('Classes.flag',1)
			->where('Subtype.flag',1)
			->where('CourseTimeClass.course_time_id',$ids)
			->select();
			if($cinfo){
				foreach ($cinfo as $value){
					$clist[]=$value['classtype'].'-'.$value['classname'];
				}
				$clist = implode("，",$clist);
				return $this->ajaxReturn(0,'该时间段还在'.$clist.'的课程表中，请联系'.$clist.'的班主任删除课程表',0);
			}else{
				$where[$pk] = array('in', explode(',', $ids));
				if(false !== $Model->save(array('flag'=>2),$where)){
					return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
				}else{
					return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
				}
			}
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
		}
	}
}