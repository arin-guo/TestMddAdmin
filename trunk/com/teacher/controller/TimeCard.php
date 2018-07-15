<?php
namespace app\teacher\controller;
use app\teacher\controller\Base;
use think\Db;
/**
 * 考勤统计控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年10月15日 上午10:16:19 
 * 类说明
 */
class TimeCard extends Base{
	/**
	 * @authority 浏览
	 */
	public function index(){
		//获得老师所在的班级
		$classes_id = Db::name('TeacherClass')->where('teacher_id',session('user_teacher_id'))->where('flag',1)->where('teacher_type',1)->value('classes_id');
        $classes_idr = Db::name('TeacherClass')->where('teacher_id',session('user_teacher_id'))->where('flag',1)->where('teacher_type',3)->value('classes_id');
        if(empty($classes_id) && empty($classes_idr)){
            return $this->ajaxReturn(0,'您还未被分配班级，请联系园长',0);
        }else{
            if(empty($classes_id)){
                $classes_id = $classes_idr;
            }
        }
		$this->assign('class_id',$classes_id);
		return $this->fetch();
	}
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['type'] = 1;
		$map['school_id'] = session('school_info_id');
		//获取教师的班级的所有学生的id
		$ids = Db::name('Childs')->where('flag',1)->where('status',1)->where('classes_id',input('class_id'))->value('GROUP_CONCAT(id)');
		$map['user_id'] = array('in',$ids);		
		//获取搜索关键字
		if(!empty(input('realname'))){
			$map['realname'] = array("like","%".input('realname')."%");
		}
		if(!empty(input('parent_name'))){
			$map['parent_name'] = array("like","%".input('parent_name')."%");
		}
		if(!empty(input('day_time'))){
			$map['day_time'] = input('day_time');
		}
		if(!empty(input('in_status')) && input('in_status') != ' '){
			$map['in_status'] = array("eq",input('in_status'));
		}
		if(!empty(input('out_status')) && input('out_status') != ' '){
			$map['out_status'] = array("eq",input('out_status'));
		}
		return $map;
	}
	/**
	 * @authority 列表
	 */
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
	 * @authority 详细
	 */
	public function detailView() {
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$face = explode('|',$vo['face_img']);
		$record = explode('|',$vo['record_time']);
		foreach($face as $key=>$val){
			foreach($record as $k=>$v){
				if($key == $k){
					$facerecord[$k]['face'] = $face[$k];
					$facerecord[$k]['record_time'] = $record[$k];
				}
			}
		}
		$this->assign('facerecord',$facerecord);
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * 导出excel视图页面
	 * @return multitype:multitype:string
	 */
	public function export(){
		return $this->fetch();
	}
	/**
	 * 导出excel
	 */
	public function exportExcel(){
		$xlsName  = "家长列表";
		$xlsCell  = array(
				array('id','ID'),
				array('username','用户名'),
				array('unique_code','识别码'),
				array('realname','家长姓名'),
				array('tel','手机号'),
				array('id_card','身份证'),
				array('sex','性别'),
				array('address','地址'),
				array('type','类别'),
				array('status','状态'),
				array('remark','备注'),
				array('create_time','创建时间')
		);
		$xlsModel = model('time_card');
		$map = $this->loadSeachCondition();
// 		echo '<pre>';
// 		print_r($map);
// 		exit;
		$xlsData  = $xlsModel->where($map)->order('create_time desc')->field('id,username,unique_code,realname,tel,id_card,sex,address,type,status,remark,create_time')->select();
		foreach ($xlsData as $key=>$val){
			$xlsData[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
			switch ($val['type']) {
				case 1:$xlsData[$key]['type'] = '主家长';break;
				case 2:$xlsData[$key]['type'] = '从属家长';break;
			}
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