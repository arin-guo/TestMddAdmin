<?php
namespace app\school\controller;
use app\school\controller\Base;
use think\Db;
/**
 * 家长管理控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月14日 上午10:16:19 
 * 类说明
 */
class Parents extends Base{
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		$map['parent_id'] = 0;
		//获取搜索关键字
		if(!empty(input('realname'))){
			$map['realname'] = array("like","%".input('realname')."%");
		}
		if(!empty(input('tel'))){
			$map['tel'] = array("like","%".input('tel')."%");
		}
		if(!empty(input('status')) && input('status') != ' '){
			$map['status'] = array("eq",input('status'));
		}else{
			$map['status'] = 1;
		}
		return $map;
	}
    //增加主家长
    public function insert(){
        $Model = model(request()->controller());
        $data = request()->param();
        $data['password'] = md5($data['password']);
        $data['username'] = "家长".substr($data['tel'], -4);
        $data['school_id'] = session('school_info_id');
        $data['status'] = 1;
        $data['type'] = 1;//主家长
        $data['unique_code'] = buildUniqueCode($schoolinfo['id']);
        $data['is_main_pick'] = 1;
        $data['parent_id'] = 0;
        if(!empty($data['childinfo'][0]) && !empty($data['childinfo'][1]) && !empty($data['childinfo'][2])){
            $Childs = model('childs');
            Db::startTrans();
            $result = $Model->allowField(true)->save($data);
            if($result){
                $id = $Model->id;
                $info = Db::name('parents')->where('id',$id)->where('flag',1)->find();
                try{
                    //判断小孩是否需要立即分班
                    $childData['classes_id'] = 0;
                    //每个家长下所有小孩的识别码都相同,如已经有小孩，就不再生成新的识别码
                    $childData['unique_code'] = $info['unique_code'];
                    $childData['realname'] = $data['childinfo'][0];
                    $childData['school_id'] = $info['school_id'];
                    $childData['status'] = $info['status'];
                    $childData['sex'] = $data['childinfo'][1];

                    $child = $Childs->allowField(true)->isUpdate(false)->save($childData);
                    //小孩与家长绑定
                    $relationData['relation'] = $data['childinfo'][2];
                    $relationData['parent_id'] = $info['id'];
                    $relationData['child_id'] = $Childs->id;
                    $relationData['create_time'] = time();
                    Db::name('ParentChild')->insert($relationData);
                    // 提交事务
                    Db::commit();
                    return $this->ajaxReturn(1, '添加家长与孩子信息成功', 1);
                }catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return $this->ajaxReturn(0, '添加家长与孩子信息失败', 0);
                }
            }
        }else{
            $ci = 1;
            foreach ($data['childinfo'] as $key=>$val){
                if(!empty($val)){
                    $ci ++;
                }
            }
            if($ci > 1){
                return $this->ajaxReturn(0,'您的孩子信息没有填写完整',0);
            }elseif($ci == 1){
                $result = $Model->allowField(true)->save($data);
                if($result){
                    return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
                }else{
                    return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
                }
            }
        }
    }
	/**
	 * @authority 打开孩子信息页
	 */
	public function childInfo() {
		$id = request()->param('id');
        $this->assign('id',$id);
		return $this->fetch('detailChild');
	}
    /**
     * @authority 孩子列表
     */
    public function getChildData(){
        // 查询满足要求的总记录数
        $total = Db::view('parent_child','relation')
            ->view('childs','realname','childs.id = parent_child.child_id')
            ->where('parent_id',input('get.id'))
            ->where('childs.flag',1)
            ->count();
        $page = json_decode($this->pageParam($total));
        $data = Db::view('parent_child','relation')
            ->view('childs','id,realname,classes_id,sex,code,status','childs.id = parent_child.child_id')
            ->where('parent_id',input('get.id'))
            ->where('childs.flag',1)
            ->limit($page->firstRow,$page->listRows)
            ->order($page->sort)
            ->select();
        foreach($data as $key=>$val){
            if($val['classes_id'] != 0){
               $classname = Db::view('classes',['id'=>'cid','name'=>'class_name'])
                   ->view('subtype',['id'=>'sid','subtype_name'],'subtype.subtype_code=classes.cats_code')
                   ->where('classes.id',$val['classes_id'])
                   ->find();
               $data[$key]['classname'] = $classname['subtype_name'].'-'.$classname['class_name'];
            }
        }
        $vo = $this->toJosnForGird($data,$page);
        return $vo;
    }
    /**
     * @authority 打开新增孩子页面
     */
    public function addchild(){
        $id = request()->param('id');
        //获取班级类型
        $Type = model('Type');
        $Subtype = model('Subtype');
        $tId = $Type->where('type_name',10002)->where('reserve',session('school_info_id'))->where('flag',1)->value('id');
        if(empty($tId)){
            $this->error('请先设置班级类型，再执行此操作！');
        }
        $vo = $Subtype->where('parent_id',$tId)->where('flag',1)->field('subtype_code,subtype_name')->select();
        $this->assign('id',$id);
        $this->assign('vo',$vo);
        return $this->fetch('addchild');
    }
    /**
     * 获取班级名
     * @return multitype:multitype:string
     */
    public function getClassName(){
        $cats_code=input('post.id');
        $Class=model('Classes');
        if(!empty($cats_code) && $cats_code != " "){
            $classinfo=$Class->where('cats_code',$cats_code)->where('school_id',session('school_info_id'))->where('flag',1)->field('id,name')->select();
            if(empty($classinfo)){
                return $this->ajaxReturn(0,'此类型下没有具体班级',0);
            }else{
                return $this->ajaxReturn($classinfo,'',1);
            }
        }else{
            return $this->ajaxReturn(0,'不选择具体班级进行搜索',1);
        }
    }
    /**
     * @authority 插入孩子信息
     */
    public function childinsert(){
        $User = model("Parents");$Childs = model('Childs');
        $data = request()->param();
        $info = $User->where('id',$data['id'])->where('flag',1)->find();
        //判断孩子最多添加4个
        $count = Db::name('ParentChild')->where('parent_id',input('param.id'))->where('flag',1)->count();
        if($count >= 4){
            return $this->ajaxReturn(0,'最多只能添加4个孩子',0);
        }else{
            Db::startTrans();
            try{
                //判断小孩是否需要立即分班
                $childData['classes_id'] = empty($data['classes_id'])?"":$data['classes_id'];
                //每个家长下所有小孩的识别码都相同,如已经有小孩，就不再生成新的识别码
                $childData['unique_code'] = $info['unique_code'];
                $childData['code'] = empty($data['code'])?"":$data['code'];
                $childData['realname'] = $data['realname'];
                $childData['id_card'] = empty($data['id_card'])?"":$data['id_card'];
                $childData['school_id'] = $info['school_id'];
                $childData['status'] = $info['status'];
                $childData['sex'] = $data['sex'];
                $child = $Childs->allowField(true)->isUpdate(false)->save($childData);
                //小孩与家长绑定
                $relationData[0]['relation'] = $data['relation'];
                $relationData[0]['parent_id'] = $info['id'];
                $relationData[0]['child_id'] = $Childs->id;
                $relationData[0]['create_time'] = time();
                //获取所有从属家长
                $ids = $User->where('parent_id',$info['id'])->where('flag',1)->field('id')->select();
                if(!empty($ids)){
                    $i = 1;
                    foreach ($ids as $key=>$val){
                        $relationData[$i]['relation'] = Db::name('ParentChild')->where('parent_id',$val['id'])->where('flag',1)->value('relation');
                        $relationData[$i]['parent_id'] = $val['id'];
                        $relationData[$i]['child_id'] = $Childs->id;
                        $relationData[$i]['create_time'] = time();
                        $i++;
                    }
                }
                Db::name('ParentChild')->insertAll($relationData);
                //家长的状态改为正常
                if($info['status'] != 1){
                    $User->where('id',$info['id'])->setField('status',1);
                }
                // 提交事务
                Db::commit();
                return $this->ajaxReturn(1, '添加成功', 1);
            }catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->ajaxReturn(0, '添加失败', 0);
            }
        }
    }
    /**
     * @authority 打开修改孩子信息页面
     */
    public function editchild(){
        $Model = model('childs');
        $pk = $Model->getPk();
        $vo = $Model::get(request()->param($pk));
        $this->assign('vo',$vo);
        return $this->fetch('editchild');
    }
    /**
     * @authority 修改孩子信息
     */
    public function childupdate(){
        $Model = model('childs');
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
     * @authority 逻辑删除孩子
     */
    public function childLogicDel(){
        $Child = model('Childs');
        $id = request()->param('id');
        //删除所有孩子的关系
        $Child->where('id',$id)->setField('flag',2);
        $result = Db::name('ParentChild')->where('child_id',$id)->where('flag',1)->setField('flag',2);
        if($result !== false){
            return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
        }else{
            return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
        }
    }
    /**
     * @authority 查看从属家长信息
     */
    public function cParentInfo(){
        $id = input('param.id');
        $this->assign('id',$id);
        return $this->fetch('detailParent');
    }
    //从属家长列表
    public function getParentData(){
        $Model = model('Parents');
        $map['parent_id'] = input('get.id');
        $map['flag'] = 1;
        $total = $Model->where($map)->count();// 查询满足要求的总记录数
        $page = json_decode($this->pageParam($total));
        $data = $Model->where($map)->limit($page->firstRow,$page->listRows)->order($page->sort)->select();
        $cname = $Model->where('id',$map['parent_id'])->value('realname');
        foreach($data as $key=>$val){
            $data[$key]['c_name'] = $cname;
        }
        $vo = $this->toJosnForGird($data,$page);
        return $vo;
    }
    /**
     * @authority 打开新增从属家长页面
     */
    public function addsubparent(){
        $id = request()->param('id');
        $ucode = Db::view('Parents')->where('id',$id)->where('flag',1)->value('unique_code');
        $this->assign('id',$id);
        $this->assign('ucode',$ucode);
        return $this->fetch('addsubparent');
    }
    /**
     * @authority 增加从属家长
     */
    public function subparentinsert(){
        $Parent = model('Parents');$Child = model('Childs');
        $data = request()->param();
        $count = $Parent->where('parent_id',$data['parent_id'])->where('flag',1)->count();
        if($count >= 2){
            return $this->ajaxReturn(0,'从属家长最多可添加2个',0);
        }else{
            Db::startTrans();
            try {
                $data['school_id'] = session('school_info_id');
                $data['username'] = '家长'.substr($data['tel'], -4);
                $data['password'] = md5($data['password']);
                $data['type'] = 2;//从属家属
                $data['status'] = 1;
                $data['is_main_pick'] = 2;
                $subInfo = $Parent->allowField(true)->isUpdate(false)->save($data);
                //与小孩绑定关系
                $list = Db::name('ParentChild')->where('parent_id',$data['parent_id'])->where('flag',1)->select();
                foreach($list as $key=>$val){
                    $subData[$key]['parent_id'] = $Parent->id;
                    $subData[$key]['child_id'] = $val['child_id'];
                    $subData[$key]['relation'] = $data['relation'];
                    $subData[$key]['create_time'] = time();
                }
                Db::name('ParentChild')->insertAll($subData);
                Db::commit();
                return $this->ajaxReturn(1,'添加成功',1);
            } catch (\Exception $e) {
                Db::rollback();
                return $this->ajaxReturn(0,'添加失败',0);
            }
        }
    }
    /**
     * @authority 删除从属家长账号
     */
    public function subParentDel(){
        $parent = model('Parents');
        $pc = model('ParentChild');
        Db::startTrans();
        try{
            $id = request()->param('id');
            $result = $parent->save(['flag'=>2],['id'=>$id]);
            $result2 = $pc->save(['flag'=>2],['parent_id'=>$id,'flag'=>1]);
            Db::commit();
            return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
        }
    }
    /**
     * @authority 删除家长账号与孩子信息
     */
    public function logicDel(){
        $Child=model("childs");
        $pc = model('ParentChild');
        $parent = model('Parents');
        // 开启事务
        Db::startTrans();
        try{
            $id = request()->param('id');
            $parent->where('parent_id',$id)->update(['flag'=>2,'update_time'=>time()]);
            $parent->where('id',$id)->update(['flag'=>2,'update_time'=>time()]);
            //Db::name('parents')->where('parent_id',$id)->where('flag',1)->update($deldata);
            //Db::name('parents')->where('id',$id)->update($deldata);
            $cid = $pc->where('flag',1)->where('parent_id',$id)->select();
            if($cid){
                foreach ($cid as $val){
                    $Child->save(['flag'=>2],['flag'=>1,'id'=>$val['child_id']]);
                    $pc->save(['flag'=>2],['flag'=>1,'child_id'=>$val['child_id']]);
                }
            }
            // 提交事务
            Db::commit();
            return $this->ajaxReturn(1,lang('ADMIN_DELETE_SUCCESS'),1);
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->ajaxReturn(0,lang('ADMIN_DELETE_ERROR'),0);
        }
    }
	/**
	 * 上传图片类
	 * @return multitype:multitype:string
	 */
	public function uploadImg(){
		$file = request()->file('image');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/parent/headimg/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path.$info->getSaveName(),config('app_upload_path'));
			return $this->ajaxReturn($file_path,'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}
    /**
     * 检查字段是否可用
     */
    public function checkValueAll(){
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
            return $this->ajaxReturn(0,'手机已存在，请更换！','n');
        }else{
            return $this->ajaxReturn(1,'','y');
        }
    }
    /**
     * 检查字段是否可用
     * 全局唯一，部分学校
     */
    public function checkValueChild(){
        $Model = model('childs');
        $value = input('param');$field = input('name');
        $map[$field] = $value;
        $map['school_id'] = session('school_info_id');
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
	 * 导出excel视图页面
	 * @return multitype:multitype:string
	 */
	public function export(){
		return $this->fetch();
	}
	
	/**
	 * 导出excel
	 */
	public function exportExcel1(){
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
		$xlsModel = model('parents');
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		if(!empty(input('status'))){
			$map['status'] = input('status');
		}
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