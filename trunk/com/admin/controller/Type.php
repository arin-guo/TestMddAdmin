<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use think\Db;
/**
 * 数据字典
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年3月27日 下午4:15:36 
 * 类说明
 */
class Type extends Base{
	
	/**
	 * 组装条件
	 * @return multitype:multitype:string
	 */
	public function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['reserve'] = 0;
		return $map;
	}
	
	/**
	 * @authority 查看小类
	 */
	public function getSubTypeAllData(){
		$map['flag'] = 1;
		$map['parent_id'] = input('id');
		$data = Db::name('Subtype')->where($map)->field('id,subtype_code,parent_subcode,subtype_name as text,parent_id,seq')->order("parent_subcode asc,seq asc")->select();
		$newData = $this->recursionData($data);
		return $newData;
	}
	
	/**
	 * @authority 逻辑删除
	 */
	public function logicDel(){
		$Model = model("Type");
		$id = input('post.id');
		$info = $Model::get($id);
		$count = Db::name("Subtype")->where('flag',1)->where('parent_id',$info['id'])->count();
		if($count != 0){
			return $this->ajaxReturn(0,"该分类已被绑定，请先删除被绑定的数据再继续操作！",0);
		}
		if(false !== $Model->save(array('flag'=>2),['id'=>$id])){
			return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
		}
	}
	
	/**
	 * 递归数据获取小类
	 */
	public function recursionData($data, $name = 'nodes', $pid = 0){
		$arr = array();
		foreach ($data as $v) {
			if ($v['parent_subcode'] == $pid) {
				$v[$name] = self::recursionData($data, $name, $v['subtype_code']);
				$v['tags'] = array($v['subtype_code']);
				$arr[] = $v;
			}
		}
		return $arr;
	}
	
	/**
	 * @authority 新增小类
	 */
	public function addSubType(){
		$this->assign('parent_id',input('id'));
		$this->assign('parent_subcode',input('parent_subcode'));
		$this->assign('parent_name',input('parent_name'));
		return $this->fetch();
	}
	
	/**
	 * @authority 插入小类
	 */
	public function insertSubType(){
		$Model = model('Subtype');
		$data = request()->param();
		$result = $Model->allowField(true)->save($data);
		if($result){
			return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
		}
	}
	
	/**
	 * @authority 修改小类
	 */
	public function editSubType(){
		$Model = Db::name('Subtype');
		$map['id'] = input('id');
		$vo = $Model->where($map)->find();
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * @authority 更新小类
	 */
	public function updateSubType() {
		$Model = model('Subtype');
		$data = request()->param();
		$where['id'] = $data['id'];
		$result = $Model->allowField(true)->save($data,$where);
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
	}
	
	/**
	 * @authority 修改小类排序
	 */
	public function editSeq() {
		$Model = Db::name('Subtype');
		$map['flag'] = 1;
		$map['parent_id'] = input('id');
		$data = $Model->where($map)->field('subtype_code as id,parent_subcode as pId,subtype_name as name')->order("parent_subcode asc,seq asc")->select();
 		$newData = $this->recursionZtreeData($data);
		$this->assign("zNodes",json_encode($newData));
		$this->assign('parent_id',input('id'));
		return $this->fetch();
	}
	
	
	/**
	 * 递归数据获取小类
	 */
	public function recursionZtreeData($data, $pid = 0){
		static $treeList = array();
		foreach ($data as $key => $value){
            if($value['pId'] == $pid){
            	$value['open'] = true;
            	$treeList[] = $value;
            	unset($data[$key]);
                $this->recursionZtreeData($data,$value['id']);
            }
        }
        return $treeList ;
	}
	
	/**
	 * @authority 更新小类排序
	 */
	public function updateSeq(){
		$modelName = config('database.prefix').'subtype';
		//批量更新
		$seq = input('seq');
		echo $sql;
		$seqArray = explode(',', $seq);
		$sql .= 'update '.$modelName.' SET seq = CASE subtype_code ';
		foreach ($seqArray as $key=>$val){
			$ids .= $val.",";
			$key++;
			$sql .= ' WHEN '.$val.' THEN '.$key." ";
		}
		$sql .= ' END ';
		$ids = substr($ids,0,strlen($ids)-1);
		$sql .= 'where subtype_code in ('.$ids.')';
		$result = Db::execute($sql);
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_SEQ_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_SEQ_ERROR'),0);
		}
	}
	
	/**
	 * 删除小类
	 */
	public function delSubType(){
		$data = request()->param();
		$where['id'] = $data['id'];
		//每一次被引用都需要在这加入删除前的判断
		$info = Db::name('Subtype')->where($where)->find();
		
		//如果该分类为父节点，则需要删除所有子节点，才能删除父节点
		if($info['parent_subcode'] == 0){
			$count = Db::name('Subtype')->where('flag',1)->where('parent_subcode',$info['subtype_code'])->count();
			if($count != 0){
				return $this->ajaxReturn(0,"请先删除子节点，在尝试删除父节点！",0);
			}
		}
		$result = Db::name('Subtype')->where($where)->setField('flag',2);
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
		}
	}
	
	/**
	 * 检查小类别是否可用
	 */
	public function checkSubType(){
		$Model = Db::name('Subtype');
		$value = input('param');$field = input('name');
		$map[$field] = $value;
		$map['flag'] = 1;
		$map['parent_id'] = input('get.parent_id');
		if(input('type') == 1){
			$map[$Model->getPk()] = array('neq',input($Model->getPk()));
		}
		$count = $Model->where($map)->count();
		if($count){
			return $this->ajaxReturn(0,'字段值已存在，请更换！','n');
		}else{
			return $this->ajaxReturn(1,'','y');
		}
	}
	
	/**
	 * @authority 上传图片
	 */
	public function uploadImg(){
		$file = request()->file('image');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/admin/type/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path.$info->getSaveName(),config('app_upload_path'));
			return $this->ajaxReturn($file_path,'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}
}