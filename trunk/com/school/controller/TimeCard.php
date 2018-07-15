<?php
namespace app\school\controller;
use app\school\controller\Base;
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
		return $this->fetch();
	}
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['type'] = 2;
		$map['school_id'] = session('school_info_id');
		//获取搜索关键字
		if(!empty(input('realname'))){
			$map['realname'] = array("like","%".input('realname')."%");
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
        $data = request()->param();
        $xlsName  = "教师考勤列表";
        $xlsCell  = array(
            array('realname','姓名'),
            array('sex','性别'),
            array('job_num','工号'),
            array('day_time','日期'),
            array('record_time','打卡记录'),
            array('in_status','上班状态'),
            array('out_status','下班状态'),
        );
        $map['time_card.school_id'] = session('school_info_id');
        $map['time_card.type'] = 2;
        $map['time_card.flag'] = 1;
        $map['teachers.is_job'] = 1;
        $map['teachers.flag'] = 1;
        if($data['realname']){
            $map['time_card.realname'] = array('like','%'.$data['realname'].'%');
        }
        //exit(var_dump($data));
        if(!empty($data['begin_time']) && !empty($data['end_time'])){
            $xlsData = Db::view('time_card','realname,day_time,record_time,in_status,out_status')
                    ->view('teachers','job_num,sex','time_card.user_id = teachers.id')
                    ->where($map)
                    ->where('day_time','between time',[$data['begin_time'],$data['end_time']])
                    ->order('teachers.id','asc')
                    ->select();
        }elseif(!empty($data['begin_time']) && empty($data['end_time'])){
            $xlsData = Db::view('time_card','realname,day_time,record_time,in_status,out_status')
                    ->view('teachers','job_num,sex','time_card.user_id = teachers.id')
                    ->where($map)
                    ->where('day_time','>= time',$data['begin_time'])
                    ->order('teachers.id','asc')
                    ->select();
        }elseif(empty($data['begin_time']) && !empty($data['end_time'])){
            $xlsData = Db::view('time_card','realname,day_time,record_time,in_status,out_status')
                    ->view('teachers','job_num,sex','time_card.user_id = teachers.id')
                    ->where($map)
                    ->where('day_time','<= time',$data['end_time'])
                    ->order('teachers.id','asc')
                    ->select();
        }elseif(empty($data['begin_time']) && empty($data['end_time'])){
            $xlsData = Db::view('time_card','realname,day_time,record_time,in_status,out_status')
                    ->view('teachers','job_num,sex','time_card.user_id = teachers.id')
                    ->where($map)
                    ->order('teachers.id','asc')
                    ->select();
            //exit(var_dump($xlsData));
        }
        foreach ($xlsData as $key=>$val){
            switch ($val['sex']) {
                case 1:$xlsData[$key]['sex'] = '男';break;
                case 2:$xlsData[$key]['sex'] = '女';break;
            }
            switch ($val['in_status']) {
                case 1:$xlsData[$key]['in_status'] = '正常';break;
                case -1:$xlsData[$key]['in_status'] = '迟到';break;
                case 2:$xlsData[$key]['in_status'] = '请假';break;
                case 0:$xlsData[$key]['in_status'] = '缺卡';break;
            }
            switch ($val['out_status']) {
                case 1:$xlsData[$key]['out_status'] = '正常';break;
                case -1:$xlsData[$key]['out_status'] = '早退';break;
                case 2:$xlsData[$key]['out_status'] = '请假';break;
                case 0:$xlsData[$key]['out_status'] = '缺卡';break;
            }
            if($val['record_time']){
                $val['record_time'] = explode('|',$val['record_time']);
                foreach ($val['record_time'] as $k=>$value){
                    if($value){
                        $val['record_time'][$k] = date('H:i:s',$value);
                    }else{
                        $val['record_time'][$k] = '空缺';
                    }
                }
                $xlsData[$key]['record_time'] = implode(',',$val['record_time']);
            }else{
                $xlsData[$key]['record_time'] = '暂无记录';
            }
        }
        exportExcel($xlsName,$xlsCell,$xlsData);
    }
}