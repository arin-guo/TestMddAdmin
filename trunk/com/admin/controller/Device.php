<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use Think\Db;
/**
 * 意见反馈
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年8月7日 下午4:13:11 
 * 类说明
 */
class Device extends Base{
    //获取学校列表
    public function index(){
        $school = model('schools');
        $vo = $school->where('flag',1)->where('is_lock',1)->field('id,name')->order('id asc')->select();
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
		if(!empty(input('device_name'))){
			$map['device_name'] = array("like","%".input('device_name')."%");
		}
        if(input('school_id') != " " && !empty(input('school_id'))){
            $map['school_id'] = input('school_id');
        }
		return $map;
	}
    //列表
    public function getAllData(){
        $Model = model(request()->controller());
        $school = model('schools');
        $map = $this->loadSeachCondition();
        $total = $Model->where($map)->count();// 查询满足要求的总记录数
        $page = json_decode($this->pageParam($total));
        $data = $Model->where($map)->limit($page->firstRow,$page->listRows)->order($page->sort)->select();
        foreach($data as $key=>$val){
            $data[$key]['school_name'] = $school->where('id',$val['school_id'])->value('name');
        }
        $vo = $this->toJosnForGird($data,$page);
        return $vo;
    }
    /**
     * @authority 新增
     */
    public function add(){
        $school = model('schools');
        $vo = $school->where('flag',1)->where('is_lock',1)->field('id,name')->order('id asc')->select();
        $this->assign('vo',$vo);
        return $this->fetch();
    }
    /**
     * @authority 修改
     */
    public function edit(){
        $Model = model(request()->controller());
        $pk = $Model->getPk();
        $vo = $Model::get(request()->param($pk));
        $school = model('schools');
        $vo['school_name']=$school->where('id',$vo['school_id'])->value('name');
        $slist = $school->where('flag',1)->where('is_lock',1)->field('id,name')->order('id asc')->select();
        $this->assign('slist',$slist);
        $this->assign('vo',$vo);
        return $this->fetch();
    }
    /**
     * @authority 查看维修记录
     */
    public function fixinfo(){
        $id = input('param.id');
        $this->assign('id',$id);
        return $this->fetch('fixlist');
    }
    //维修列表
    public function getAllFixData(){
        $Model = model('device_fix_log');
        $map['device_id'] = input('get.id');
        $map['flag'] = 1;
        $total = $Model->where($map)->count();// 查询满足要求的总记录数
        $page = json_decode($this->pageParam($total));
        $data = $Model->where($map)->limit($page->firstRow,$page->listRows)->order($page->sort)->select();
        $dname = Db::view('device')->where('id',$map['device_id'])->value('device_name');
        foreach($data as $key=>$val){
            $data[$key]['device_name'] = $dname;
        }
        $vo = $this->toJosnForGird($data,$page);
        return $vo;
    }
    /**
     * @authority 打开新增维修记录页面
     */
    public function addfix(){
        $Model = model(request()->controller());
        $pk = $Model->getPk();
        $vo = $Model->field('id,device_name')->find(request()->param($pk));
        $this->assign('vo',$vo);
        return $this->fetch('addfix');
    }
    /**
     * @authority 插入维修记录
     */
    public function fixloginsert(){
        $Model = model('device_fix_log');
        $data = request()->param();
        $result = $Model->allowField(true)->save($data);
        if($result){
            return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
        }else{
            return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
        }
    }
    /**
     * @authority 打开修改维修记录页面
     */
    public function editfix(){
        $Model = model('device_fix_log');
        $pk = $Model->getPk();
        $vo = $Model::get(request()->param($pk));
        $did = input('get.did');
        $dname = Db::view('device')->where('id',$did)->value('device_name');
        $vo['device_name'] = $dname;
//        echo '<pre>';
//        print_r($vo);
//        exit;
        $this->assign('vo',$vo);
        return $this->fetch('editfix');
    }
    /**
     * @authority 修改维修记录
     */
    public function fixlogupdate(){
        $Model = model('device_fix_log');
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
     * @authority 逻辑删除维修记录
     */
    public function fixLogicDel()
    {
        $Model = model('device_fix_log');
        $ids = input('param.id');
        if (!empty($ids)) {
            $where['id'] = array('in', explode(',', $ids));
            if (false !== $Model->save(array('flag' => 2), $where)) {
                return $this->ajaxReturn(1, lang('ADMIN_DELETE_SUCCESS'), 1);
            } else {
                return $this->ajaxReturn(0, lang('ADMIN_DELETE_ERROR'), 0);
            }
        } else {
            return $this->ajaxReturn(0, lang('ADMIN_DELETE_ERROR'), 0);
        }
    }
}