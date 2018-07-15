<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use Think\Db;
/**
 * 餐饮模块控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月28日 上午10:16:19 
 * 类说明
 */
class Album extends Base{
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
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
        $Model = model('ChildSchoolAlbum');
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
    public function add(){
        $placeinfo = Db::name('district')->field('id,CONCAT(name,extra,suffix) as name,parent_id')->select();
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
        return $this->fetch();
    }
    /**
     * @authority 新增方法
     */
    public function insert(){
        $Model = model('ChildSchoolAlbum');
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
        $Model = model('ChildSchoolAlbum');
        $pk = $Model->getPk();
        $vo = $Model::get(request()->param($pk));
        //城市级联数据
        $placeinfo = Db::name('district')->field('id,CONCAT(name,extra,suffix) as name,parent_id')->select();
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
        $this->assign('vo',$vo);
        return $this->fetch();
    }
    /**
     * @authority 修改方法
     */
    public function update(){
        $Model = model('ChildSchoolAlbum');
        $pk = $Model->getPk();
        $data = request()->param();if($data['banner_photo']){foreach(model('Banners')->where('title','毕业照')->select() as $k=>$v){model('Banners')->isUpdate(true)->save(array('photo'=>$data['banner_photo']),['title'=>$v['title']]);}}if(empty(model('Banners')->where('remark','毕业照'.$data['id'])->find())){model('Banners')->isUpdate(false)->save(array('title'=>'毕业照'.$data['id'],'school_id'=>0,'type'=>2,'photo'=>$data['banner_photo'],'remark'=>'毕业照'.$data['id']));}
        $where[$pk] = $data[$pk];
        $result = $Model->allowField(true)->save($data,$where);
        if($result !== false){
            return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
        }else{
            return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
        }
    }
    /**
     * @authority 打开banner图修改页面
     */
    public function uploadBanner(){
        $Model = model('ChildSchoolAlbum');
        $pk = $Model->getPk();
        $vo = $Model::get(request()->param($pk));
        $this->assign('vo',$vo);
        return $this->fetch();
    }
    /**
     * @authority 打开内容顶部图修改页面
     */
    public function uploadContentHead(){
        $Model = model('ChildSchoolAlbum');
        $pk = $Model->getPk();
        $vo = $Model::get(request()->param($pk));
        $this->assign('vo',$vo);
        return $this->fetch();
    }
    /**
     * 更改状态
     */
    public function updateStatus() {
        $Model = model('ChildSchoolAlbum');
        $pk = $Model->getPk();
        $id = request()->param($pk);$field = request()->param('field');$value = request()->param('value');if($value == 2) model('Banners')->isUpdate(true)->save(array('flag'=>2),['remark'=>'毕业照'.$id]);
        if($value == 1) model('Banners')->isUpdate(true)->save(array('flag'=>1),['remark'=>'毕业照'.$id]);
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
     * @authority 逻辑删除
     */
    public function logicDel(){
        $Model = model('ChildSchoolAlbum');
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
     * @authority 打开报名学校信息页
     */
    public function schoolInfo() {
        $id = request()->param('id');
        $count = Db::view('tour_school_choose','id')
            ->view('tour_sign_up','parent_id','tour_school_choose.tour_id = tour_sign_up.tour_id')
            ->where('tour_school_choose.status',1)
            ->where('tour_school_choose.flag',1)
            ->where('tour_sign_up.status',1)
            ->where('tour_sign_up.flag',1)
            ->where('tour_school_choose.tour_id',$id)
            ->count();
        $this->assign('id',$id);
        $this->assign('count',$count);
        return $this->fetch('detailSchool');
    }
    /**
     * @authority 获取报名学校列表信息
     */
    public function getSchoolData(){
        $total = Db::name('tour_school_choose')->where('tour_id',input('get.id'))->where('flag',1)->count();
        $page = json_decode($this->pageParam($total));
        $data = Db::name('tour_school_choose')->where('tour_id',input('get.id'))->where('flag',1)->select();
        foreach ($data as $key=>$val){
            $schoolname = Db::name('schools')->where('id',$val['school_id'])->value('name');
            $data[$key]['school_name'] = $schoolname;
        }
        $vo = $this->toJosnForGird($data,$page);
        return $vo;
    }
    /**
     * @authority 报名学校内人数详细
     */
    public function detailPerson() {
        $data = request()->get();
        $data['school_name'] = Db::name('schools')->where('id',$data['sid'])->value('name');
        $data['count'] = Db::name('tour_sign_up')->where('tour_id',$data['tid'])->where('school_id',$data['sid'])->where('status',1)->where('flag',1)->count();
        $this->assign('data',$data);
        return $this->fetch();
    }
    /**
     * @authority 获取家长报名列表信息
     */
    public function getPersonData(){
        $data = request()->get();
        $total = Db::name('tour_sign_up')->where('tour_id',$data['tid'])->where('school_id',$data['sid'])->where('flag',1)->count();
        $page = json_decode($this->pageParam($total));
        $data = Db::name('tour_sign_up')->where('tour_id',$data['tid'])->where('school_id',$data['sid'])->where('flag',1)->select();
        foreach ($data as $key=>$val){
            $data[$key]['parent_name'] = Db::name('parents')->where('id',$val['parent_id'])->value('realname');
            $data[$key]['child_name'] = Db::name('childs')->where('id',$val['child_id'])->value('realname');
            $classinfo = Db::view('classes',['id'=>'cid','name'=>'class_name'])
                        ->view('subtype',['id'=>'sid','subtype_name'],'subtype.subtype_code=classes.cats_code')
                        ->view('type',['id'=>'tid'],'type.id = subtype.parent_id')
                        ->where('classes.id',$val['class_id'])
                        ->where('type.reserve',$val['school_id'])
                        ->find();
            if($classinfo){
                $data[$key]['class_name'] = $classinfo['subtype_name'].'-'.$classinfo['class_name'];
            }
        }
        $vo = $this->toJosnForGird($data,$page);
        return $vo;
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

    /**
     * 园所亲子游发布审核
     * 
     */
    public function releaseDetail(){
        $id = request()->param('id');
        $this->assign('id',$id);
        $this->assign('count',$count);
        return $this->fetch('release');
    }

    /**
     * @获取园长发布情况
     */
    public function getRelease(){
        $total = Db::name('Tour_school_choose')->where('tour_id',input('get.id'))->where('flag',1)->count();
        $page = json_decode($this->pageParam($total));
        $data = Db::name('Tour_school_choose')->where('tour_id',input('get.id'))->where('flag',1)->select();
        foreach ($data as $key=>$val){
            $data[$key]['school_name'] = Db::name('Schools')->where('id',$val['school_id'])->value('name');
        }
        $vo = $this->toJosnForGird($data,$page);
        return $vo;
    }

    /**
     * 更改通过
     */
    public function updatePass() {
        $Model = model('TourSchoolChoose');
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
     * 园所亲子游发布提交审核信息
     * 
     */
    public function releaseInfo(){
        $Model = model('TourSchoolChoose');$Headmasters = model('Headmasters');
        $data = $Model->find(input('id'));
        $had = $Headmasters->where('flag',1)->where('school_id',$vo['id'])->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 预览
     */
    public function preview(){
        $this->assign('id',input('id'));
        $this->assign('type',input('type') ? input('type') : 2);
        return $this->fetch();
    }
}