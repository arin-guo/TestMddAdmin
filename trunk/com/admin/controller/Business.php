<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use think\Db;
/**
 * 学校管理
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年9月6日 上午10:16:19 
 * 类说明
 */
class Business extends Base{
	
	/**
	 * 组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		if(!empty(input('name'))){
			$map['name'] = array("like","%".input('name')."%");
		}
		if(!empty(input('address'))){
			$map['address'] = array("like","%".input('address')."%");
		}
		return $map;
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
        for($i=0;$i<count($province,0);$i++){
            foreach ($province[$i]['children'] as $key=>$val){
                $district = array();
                foreach ($placeinfo as $k=>$v){
                    if($val['id'] == $v['parent_id']){
                        $district[] = $placeinfo[$k];
                    }
                }
                if(!empty($district)){
                    $province[$i]['children'][$key]['children'] = $district;
                }
            }
        }
        $info = json_encode($province);
        //exit(print_r($province));
        $this->assign('placeinfo',$info);
        return $this->fetch();
    }
	/**
	 * @authority 新增方法
	 * 在新增学士时，自动写入这些关键数据
	 * 班级类型分为：托班，小班，中班，大班
	 * 教职工类型分为：班主任，保育阿姨，任课老师，后勤，厨师，保健医生，安保，财务，园长，副园长，其他。
	 */
	public function insert(){
		$Model = model('Schools');$Type = model('Type');$Subtype = model('Subtype');
		Db::startTrans();
		try {
			$data = request()->param();
			/*ji add*/
            //$data['device_password'] = '000000';
			$Model->allowField(true)->save($data);
			//自动增加学校的类型字段
			$tdata[0]['type_name'] = '10001';
			$tdata[0]['reserve'] = $Model->id;
			$tdata[0]['seq'] = 0;
			$tdata[0]['flag'] = 1;
			$tdata[0]['remark'] = "教职工类型";
			$tdata[1]['type_name'] = '10002';
			$tdata[1]['reserve'] = $Model->id;
			$tdata[1]['seq'] = 0;
			$tdata[1]['flag'] = 1;
			$tdata[1]['remark'] = "班级类型";
			$ids = $Type->saveAll($tdata);
			foreach ($ids as $key=>$val){
				if($key == 0){//教职工类型
					$subData[] = ['subtype_code'=>'10001','subtype_name'=>'班主任','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'10002','subtype_name'=>'保育阿姨','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'10003','subtype_name'=>'任课老师','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'10004','subtype_name'=>'后勤','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'10005','subtype_name'=>'厨师','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'10006','subtype_name'=>'保健医生','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'10007','subtype_name'=>'安保','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'10008','subtype_name'=>'财务','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'10009','subtype_name'=>'园长','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'10010','subtype_name'=>'副园长','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'10011','subtype_name'=>'其他','parent_subcode'=>'0','parent_id'=>$val->id];
				}elseif($key == 1){//班级类型
					$subData[] = ['subtype_code'=>'20001','subtype_name'=>'托班','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'20002','subtype_name'=>'小班','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'20003','subtype_name'=>'中班','parent_subcode'=>'0','parent_id'=>$val->id];
					$subData[] = ['subtype_code'=>'20004','subtype_name'=>'大班','parent_subcode'=>'0','parent_id'=>$val->id];
				}
			}
			$Subtype->saveAll($subData);
			Db::commit();
			return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
		}catch (\Exception $e){
			Db::rollback();
			return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
		}
	}
    /**
     * @authority 修改时传入城市级联数据
     */
    public function edit(){
        $Model = model(request()->controller());
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
        for($i=0;$i<count($province,0);$i++){
            foreach ($province[$i]['children'] as $key=>$val){
                $district = array();
                foreach ($placeinfo as $k=>$v){
                    if($val['id'] == $v['parent_id']){
                        $district[] = $placeinfo[$k];
                    }
                }
                if(!empty($district)){
                    $province[$i]['children'][$key]['children'] = $district;
                }
            }
        }
        $info = json_encode($province);
        //exit(print_r($province));
        $this->assign('placeinfo',$info);
        $this->assign('vo',$vo);
        return $this->fetch();
    }
	/**
	 * @authority 详细
	 */
	public function detailView() {
		$Model = model(request()->controller());$Headmasters = model('Headmasters');
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$had = $Headmasters->where('flag',1)->where('school_id',$vo['id'])->find();
		$this->assign('vo',$vo);
		$this->assign('had',$had);
		return $this->fetch();
	}
	
	/**
	 * 检查园长手机号(app登录账号)是否可用
	 */
	public function checkValue2(){
		$Model = model('Headmasters');
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
			return $this->ajaxReturn(0,'手机号已存在，请更换！','n');
		}else{
			return $this->ajaxReturn(1,'','y');
		}
	}
	
	/**
	 * 检查后台管理账号是否可用
	 */
	public function checkValue3(){
		$Model = model('Member');
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
			return $this->ajaxReturn(0,'账号已存在，请更换！','n');
		}else{
			return $this->ajaxReturn(1,'','y');
		}
	}
    /**
     * 检查学校名称是否可用
     */
    public function checkValue4(){
        $Model = model('Schools');
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
            return $this->ajaxReturn(0,'学校名称已存在，请更换！','n');
        }else{
            return $this->ajaxReturn(1,'','y');
        }
    }
	
	/**
	 * 管理员
	 * @return \think\mixed
	 */
	public function manage(){
		$Model = model('Headmasters');$Member = model('Member');
		$vo = $Model::get(['flag'=>1,'school_id'=>input('id')]);
		$member = $Member->where('flag',1)->where('is_admin',1)->where('school_id',input('id'))->where('use_type',2)->field('id,username')->find();
		$this->assign('school_id',input('id'));
		$this->assign('vo',$vo);
		$this->assign('member',$member);
		return $this->fetch();
	}
	
	/**
	 * @authority 修改方法
	 */
	public function updateManage(){
		$Model = model('Headmasters');$Member = model('Member');
		$param = request()->param();
		$data['school_id'] = $param['school_id'];
		$data['photo'] = $param['photo'];
		$data['realname'] = $param['realname'];
		$data['tel'] = $param['tel'];
		$data['password'] = md5($param['password']);
		if(!empty(input('id'))){
			$result = $Model->allowField(['school_id','realname','password','tel'])->save($data,['id'=>input('id')]);
		}else{
			$result = $Model->save($data);
		}
		if($result !== false){
			//保存基本信息后需要同时修改保存管理员账号信息
			$count = $Member->where('use_type',2)->where('flag',1)->where('school_id',$param['school_id'])->where('is_admin',1)->count();
			$mdata['username'] = $param['username'];
			$mdata['password'] = md5($param['admin_password']);
			$mdata['realname'] = $param['realname'];
			if($count == 0){
				$mdata['headimg'] = $param['photo'];
				$mdata['create_time'] = time();
				$mdata['update_time'] = time();
				$mdata['use_type'] = 2;
				$mdata['is_admin'] = 1;
				$mdata['school_id'] = $data['school_id'];
				$Member->isUpdate(false)->save($mdata);
			}else{
				$Member->where('use_type',2)->where('flag',1)->where('school_id',$param['school_id'])->where('is_admin',1)->update($mdata);
			}
			return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
	}
	
}