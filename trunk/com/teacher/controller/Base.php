<?php
namespace app\teacher\controller;
use think\Controller;
use auth\Auth;
use think\Session;
use think\Config;

/**
 * 
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年3月9日 上午10:28:20 
 * 类说明
 */
class Base extends Controller{
	
	public function _initialize(){
		header("Content-type: text/html; charset=utf-8");
		if (Session::has('user_teacher_id') == null) {
			$this->redirect('teacher/Login/index');
		}
	}
	
	/**
	 * 空操作
	 */
	public function _empty(){
		return $this->fetch(ROOT_PATH.'public/static/error/404.html');
	}
	
	/**
	 * @authority 浏览
	 */
	public function index(){
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
	 * @authority 新增
	 */
	public function add(){
		return $this->fetch();
	}
	
	/**
	 * @authority 新增方法
	 */
	public function insert(){
		$Model = model(request()->controller());
		$data = request()->param();
		$result = $Model->allowField(true)->save($data);
		if($result){
			return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
		}
	}
	
	/**
	 * @authority 修改
	 */
	public function edit(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * @authority 修改方法
	 */
	public function update(){
		$Model = model(request()->controller());
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
	 * @authority 逻辑删除
	 */
	public function logicDel(){
		$Model = model(request()->controller());
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
	 * @authority 物理删除
	 */
	public function del(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$ids = request()->param($pk);
		if(!empty($ids)){
			$where[$pk] = array('in', explode(',', $ids));
			if(false !== $Model->where($where)->delete()){
				return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
			}else{
				return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
			}
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
		}
	}
	
	/**
	 * @authority 详细
	 */
	public function detailView() {
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * 更改状态
	 */
	public function updateStatus() {
		$Model = model(request()->controller());
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
	 * 检查字段是否可用
	 */
	public function checkValue(){
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
	
	/**
	 * 返回
	 * @param string $data
	 * @param string $info
	 * @param number $status
	 * @return multitype:string number
	 */
	public function ajaxReturn($data= array(),$info='',$status=0){
		$result = array(
				'data' => $data,
				'info' => $info,
				'status' => $status
		);
		return json($result);
	}
	
	/**
	 * 封装dtgird的json数据
	 */
	public function toJosnForGird($data,$page){
		if(empty($page)){
			$page->page = 0;
			$page->total = 0;
			$page->records = 0;
		}
		$newdata['page'] = $page->page;
		$newdata['total'] = $page->total;
		$newdata['records'] = $page->records;
		$newdata['rows'] = $data;
		return $newdata;
	}
	
	public function pageParam($totalRows = 0){
		$records = $totalRows;//总条数
		$listRows = empty(input('get.rows'))?20:input('get.rows');
		if(!empty($listRows)) {
			$listRows =   intval($listRows);
		}
		//排序
		$sidx = empty(input('get.sidx'))?model(request()->controller())->getPk():input('get.sidx');
		$sord = empty(input('get.sord'))?"desc":input('get.sord');
		$sort = $sidx." ".$sord;
		$totalPages   =   ceil($totalRows/$listRows);
		$nowPage      =   !empty(input('get.page'))?intval(input('get.page')):1;
		$firstRow     =   $listRows * ($nowPage-1);
		$data['total'] = $totalPages; //总页数
		$data['page'] = $nowPage;//当前页数
		$data['records'] = $totalRows;//总数量
		$data['listRows'] = $listRows;//获取默认列表每页显示行数,默认20
		$data['firstRow'] = $firstRow;//当前页数-1
		$data['sort'] = $sort;
		return json_encode($data);
	}
}