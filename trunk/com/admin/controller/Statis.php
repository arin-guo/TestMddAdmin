<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use Think\Db;
/**
 * 资讯控制器
 * @author ji
 * @version 创建时间：2018.4.4
 * 
 */
class Statis extends Base{
	/**
	 * 搜索组装条件
	 * @return 
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		//获取搜索关键字
		if(!empty(input('title'))){
			$map['title'] = array("like","%".input('name')."%");
		}
        if(!empty(input('status')) && input('status') != ' '){
            $map['status'] = array("eq",input('status'));
        }
        return $map;
	}
    //列表
    public function getAllData(){
        $Model = model('Report');
        $map = $this->loadSeachCondition();
        $total = $Model->where($map)->count();// 查询满足要求的总记录数
        $page = json_decode($this->pageParam($total));
        $data = $Model->where($map)->limit($page->firstRow,$page->listRows)->order($page->sort)->select();
        $vo = $this->toJosnForGird($data,$page);
        return $vo;
    }
    /**
     * @authority 新增时加入城市级联数据
     */
    public function index(){
        $placeinfo = Db::name('district')->field('id,CONCAT(name,extra,suffix) as name,parent_id')->where('parent_id',12)->whereOr('id',12)->select();
        foreach ($placeinfo as $key=>$val){
            if($val['parent_id'] == 0){
                $province[] = $placeinfo[$key];
            }
        }
        foreach ($province as $key=>$val){
            $city = array();
            foreach ($placeinfo as $k=>$v){
                if ($val['id'] == $v['parent_id']){
                    $city[] = $placeinfo[$k];
                }
            }
            if(!empty($city)){
                $province[$key]['children'] = $city;
            }
        }
        $info = json_encode($province);
        //exit(print_r($province));
        $this->assign('placeinfo',$info);

        $this->assign('school',model('Schools')->select());
        Db::query('SET group_concat_max_len = 102400');
        if($_POST['room'] && $_POST['room'] != '请选择幼儿园')
        {
            $this->assign('schoolname',$_POST['room']);
        	$map = model('Schools')->where('name',$_POST['room'])->value('GROUP_CONCAT(id)');
        }
        elseif($til = $_POST['title'])
        {
            $this->assign('schoolname',model('Schools')->where('name','LIKE','%'.$til.'%')->value('name'));
        	$map = model('Schools')->where('name','LIKE','%'.$til.'%')->value('id');
        	if(empty($map)){echo "<script>alert('没有该信息');</script>";$map = 0;}
        }
        elseif($area = $_POST['area'])
        {
        	$this->assign('are',$area);
        	$map = model('Schools')->where('place_code','LIKE','%'.model('District')->where('name',substr(substr(explode('/',$area)[1],0,-3),0,99))->value('id').'%')->value('GROUP_CONCAT(id)') ? model('Schools')->where('place_code','LIKE','%'.model('District')->where('name',substr(substr(explode('/',$area)[1],0,-3),0,99))->value('id').'%')->value('GROUP_CONCAT(id)') : 0;
        }
        else
        {
        	$map = model('Schools')->value('GROUP_CONCAT(id)');
        }
        $pids = model('Parents')->where('school_id','IN',$map)->where('flag',1)->value('GROUP_CONCAT(id)') ? model('Parents')->where('school_id','IN',$map)->where('flag',1)->value('GROUP_CONCAT(id)') : 0;
        $tids = model('Teachers')->where('school_id','IN',$map)->where('flag',1)->value('GROUP_CONCAT(id)') ? model('Teachers')->where('school_id','IN',$map)->where('flag',1)->value('GROUP_CONCAT(id)') : 0;
        $hids = model('Headmasters')->where('school_id','IN',$map)->where('flag',1)->value('GROUP_CONCAT(id)') ? model('Headmasters')->where('school_id','IN',$map)->where('flag',1)->value('GROUP_CONCAT(id)') : 0;
        $sql = 'user_id IN (' . $pids . ') AND type = 1 OR user_id IN (' . $tids . ') AND type = 2 OR user_id IN (' . $hids . ') AND type = 3';
        $this->assign('reg',model('Parents')->where('create_time','GT',strtotime(date('Y-m-d 00:00:00')))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('+1 day'))))->where('flag',1)->where('school_id','IN',$map)->count());
        $this->assign('total',$totle = model('Headmasters')->where('school_id','IN',$map)->where('flag',1)->count() + model('Teachers')->where('school_id','IN',$map)->where('flag',1)->count() + model('Parents')->where('school_id','IN',$map)->where('flag',1)->count());
        $this->assign('active',$active = model('LoginSession')->where($sql)->where('create_time','GT',strtotime(date('Y-m-d 00:00:00')))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('+1 day'))))->count());
        $this->assign('not',$totle - $active);
        $this->assign('quz',model('Parents')->where('school_id','IN',model('Schools')->where('place_code','LIKE','%215%')->value('GROUP_CONCAT(id)'))->where('flag',1)->count());
        $this->assign('jiax',model('Parents')->where('school_id','IN',model('Schools')->where('place_code','LIKE','%211%')->value('GROUP_CONCAT(id)'))->where('flag',1)->count());
        $this->assign('huz',model('Parents')->where('school_id','IN',model('Schools')->where('place_code','LIKE','%212%')->value('GROUP_CONCAT(id)'))->where('flag',1)->count());
        $this->assign('shaox',model('Parents')->where('school_id','IN',model('Schools')->where('place_code','LIKE','%213%')->value('GROUP_CONCAT(id)'))->where('flag',1)->count());
        $this->assign('jinh',model('Parents')->where('school_id','IN',model('Schools')->where('place_code','LIKE','%214%')->value('GROUP_CONCAT(id)'))->where('flag',1)->count());
        $this->assign('taiz',model('Parents')->where('school_id','IN',model('Schools')->where('place_code','LIKE','%217%')->value('GROUP_CONCAT(id)'))->where('flag',1)->count());
        $this->assign('wenz',model('Parents')->where('school_id','IN',model('Schools')->where('place_code','LIKE','%210%')->value('GROUP_CONCAT(id)'))->where('flag',1)->count());
        $this->assign('ninb',model('Parents')->where('school_id','IN',model('Schools')->where('place_code','LIKE','%209%')->value('GROUP_CONCAT(id)'))->where('flag',1)->count());
        $this->assign('hangz',model('Parents')->where('school_id','IN',model('Schools')->where('place_code','LIKE','%208%')->value('GROUP_CONCAT(id)'))->where('flag',1)->count());
        $this->assign('now',date('Y年m月d日'));$this->assign('sec',date('Y年m月d日',strtotime('-1 day')));$this->assign('thr',date('Y年m月d日',strtotime('-2 day')));$this->assign('fou',date('Y年m月d日',strtotime('-3 day')));$this->assign('fiv',date('Y年m月d日',strtotime('-4 day')));$this->assign('sat',date('Y年m月d日',strtotime('-5 day')));$this->assign('sun',date('Y年m月d日',strtotime('-6 day')));
        $this->assign('secreg',model('Parents')->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-1 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00')))->where('flag',1)->where('school_id','IN',$map)->count());
        $this->assign('thrreg',model('Parents')->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-2 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('-1 day'))))->where('flag',1)->where('school_id','IN',$map)->count());
        $this->assign('foureg',model('Parents')->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-3 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('-2 day'))))->where('flag',1)->where('school_id','IN',$map)->count());
        $this->assign('fivreg',model('Parents')->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-4 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('-3 day'))))->where('flag',1)->where('school_id','IN',$map)->count());
        $this->assign('satreg',model('Parents')->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-5 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('-4 day'))))->where('flag',1)->where('school_id','IN',$map)->count());
        $this->assign('sunreg',model('Parents')->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-6 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('-5 day'))))->where('flag',1)->where('school_id','IN',$map)->count());
        $this->assign('secact',model('LoginSession')->where($sql)->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-1 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00')))->count());
        $this->assign('thract',model('LoginSession')->where($sql)->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-2 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('-1 day'))))->count());
        $this->assign('fouact',model('LoginSession')->where($sql)->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-3 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('-2 day'))))->count());
        $this->assign('fivact',model('LoginSession')->where($sql)->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-4 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('-3 day'))))->count());
        $this->assign('satact',model('LoginSession')->where($sql)->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-5 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('-4 day'))))->count());
        $this->assign('sunact',model('LoginSession')->where($sql)->where('create_time','GT',strtotime(date('Y-m-d 00:00:00',strtotime('-6 day'))))->where('create_time','LT',strtotime(date('Y-m-d 00:00:00',strtotime('-5 day'))))->count());
        return $this->fetch();
    }
    /**
     * @ 新增方法
     */
    public function insert(){
        $Model = model('Report');
        $data = request()->param();
        $result = $Model->allowField(true)->save($data);
        if($result){
            return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
        }else{
            return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
        }
    }
    /**
     * @ 修改
     */
    public function edit(){
        $Model = model('Report');
        $pk = $Model->getPk();
        $vo = $Model::get(request()->param($pk));
        $this->assign('vo',$vo);
        return $this->fetch();
    }
    /**
     * @ 修改方法
     */
    public function update(){
        $Model = model('Report');
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
     * 更改状态
     */
    public function updateStatus() {
        $Model = model('Report');
        $pk = $Model->getPk();
        $id = request()->param($pk);$field = request()->param('field');$value = request()->param('value');
        if(isset($id) && isset($field) && isset($value)){
            $where[$pk] = array('in',explode(',', $id));
            $data[$field] = $value;
            if (false !== $Model->save($data,$where)) {
                return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
            } else {
                return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
            }
        }else{
            return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
        }
    }
    /**
     * @ 逻辑删除
     */
    public function logicDel(){
        $Model = model('Report');
        $pk = $Model->getPk();
        $ids = request()->param($pk);
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
	 * 上传图片
	 * @return multitype:multitype:string
	 */
	public function uploadImgHead(){
		$file = request()->file('image');
		$valid['size'] = 4097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/tourschool/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path,config('app_upload_path'));
			return $this->ajaxReturn($file_path.$info->getSaveName(),'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}
    /**
     * 上传图片
     * @return multitype:multitype:string
     * 用于百度编辑器
     */
    public function uploadImg(){
        $file = request()->file('upfile');
        $valid['size'] = 2097152;//2M
        $valid['ext'] = 'jpg,png,gif';
        $path = config('app_upload_path').'/uploads/editor/';
        $info = $file->validate($valid)->rule('date')->move($path);
        if($info){
            $reback['state'] = 'SUCCESS';
            $reback['url'] = ltrim($path,config('app_upload_path')).$info->getSaveName();
            return json_encode($reback);
        }else{
            $reback['state'] = 'ERROR';
            return json_encode($reback);
        }
    }
}