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
class Report extends Base{
	/**
	 * 搜索组装条件
	 * @return 
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		//获取搜索关键字
		if(!empty(input('title'))){
			$map['title'] = array("like","%".input('title')."%");
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