<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use think\Db;
/**
 * 
 * @author huangjian E-mail:870596179@qq.com
 * @version 创建时间：2016年10月21日 上午10:42:33 
 * 类说明
 */
class AuthRule extends Base{
	
	/**
	 * @authority 浏览
	 */
	public function index(){
		$Model = model('AuthRule');
		$map['status'] = 1;
		$map['type'] = 1;
		$map['use_type'] = 1;
		$vo = $Model->where($map)->order('seq asc')->select();
		//查询出层级关系数据
		foreach ($vo as $key=>$val){
			$map['type'] = 2;
			$map['parent_id'] = $val['id'];
			$sub_menu = $Model->where($map)->order('seq asc')->select();
			$vo[$key]['sub_menu'] = $sub_menu;
		}
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * @authority 新增子菜单
	 */
	public function addSubMenu(){
		$parent_id = input('get.parent_id');
		$this->assign('parent_id',$parent_id);
		return $this->fetch();
	}
	
	/**
	 * @authority 插入子菜单
	 */
	public function insertSubMenu(){
		$Model = model('AuthRule');
		$data = request()->param();
		if(strpos($data['url'], ".")) {
			$name = substr($data['url'],0,strrpos($data['url'],'.'));
		}elseif (strpos($data['url'], "?")){
			$name = substr($data['url'],0,strrpos($data['url'],'?'));
		}else{
			$name = $data['url'];
		}
		$data['name'] = 'admin/'.$name;
		$result = $Model->allowField(true)->save($data);
		if($result){
			return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
		}
	}
	
	/**
	 * @authority 修改子菜单
	 */
	public function editSubMenu($M = ""){
		$Model = model('AuthRule');
		$vo = $Model::get(input('id'));
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * @authority 修改排序
	 */
	public function editSeq() {
		$Model = model('AuthRule');
		$map['status'] = 1;
		$map['type'] = 1;
		$map['use_type'] = 1;
		$vo = $Model->where($map)->field("id,title")->order('seq asc')->select();
		$map['type'] = 2;
		$sub_menu = $Model->where($map)->field("id,title,parent_id")->order('seq asc')->select();
		$newData = array();
		$k = 0;
		for($i=0; $i<count($vo); $i++) {
			$newData[$k]['id'] = $vo[$i]['id'];
			$newData[$k]['name'] = $vo[$i]['title'];
			$newData[$k]['pId'] = 0;
			$newData[$k]['isParent'] = true;
			$newData[$k]['open'] = true;
			$k++;
			for($j=0; $j<count($sub_menu); $j++) {
				if($vo[$i]['id'] == $sub_menu[$j]['parent_id']) {
					$newData[$k]['id'] = $sub_menu[$j]['id'];
					$newData[$k]['pId'] = $vo[$i]['id'];
					$newData[$k]['name'] = $sub_menu[$j]['title'];
					$k++;
				}
			}
		}
		$this->assign("zNodes",json_encode($newData));
		return $this->fetch();
	}
	

	/*
	 * @authority 更新排序
	 */
	public function updateSeq(){
		$modelName = config('database.prefix').'auth_rule';
		//批量更新
		$seq = input('seq');
		$seqArray = explode(',', $seq);
		$sql .= 'update '.$modelName.' SET seq = CASE id ';
		foreach ($seqArray as $key=>$val){
			$ids .= $val.",";
			$key++;
			$sql .= ' WHEN '.$val.' THEN '.$key." ";
		}
		$sql .= ' END ';
		$ids = substr($ids,0,strlen($ids)-1);
		$sql .= 'where id in ('.$ids.')';
		$result = Db::execute($sql);
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_SEQ_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_SEQ_ERROR'),0);
		}
	}
	
}