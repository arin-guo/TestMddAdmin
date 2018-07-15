<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use lechange\Lechange;
use think\Db;
/**
 * 直播设备
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年12月10日 上午11:28:02 
 * 类说明
 */
class ClassLive extends Base{
	
	public function index(){
		$School = model('Schools');
		$vo = $School->where('flag',1)->where('is_device',1)->field('id,name')->select();
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
		//搜索条件
		if(!empty(input('device_id'))){
			$map['device_id'] = array("like","%".input('device_id')."%");
		}
		if(!empty(input('title'))){
			$map['title'] = array("like","%".input('title')."%");
		}
		if(input('class_id') != " " && !empty(input('class_id'))){
			$map['class_id'] = input('class_id');
		}
		if(input('school_id') != " " && !empty(input('school_id'))){
			$map['school_id'] = input('school_id');
		}
		return $map;
	}
	
	/**
	 * 获取班级列表
	 */
	public function getClassData(){
		$Class = model('Classes');$Type = model('Type');$Subtype = model('Subtype');
		$where['flag'] = 1;
		$where['school_id'] = input('id');
		$ids = Db::name('ClassLive')->where('flag',1)->where('school_id',input('id'))->value('GROUP_CONCAT(class_id)');
		if(input('type') == 1){//未绑定
			$where['id'] = array('not in',$ids);
		}else{//已经绑定的班级
			$where['id'] = array('in',$ids);
		}
		$vo = $Class->where($where)->field('id,cats_code,name')->select();
		$tId = $Type->where('type_name',10002)->where('reserve',input('id'))->where('flag',1)->value('id');
		foreach ($vo as $key=>$val){
			$cats_name = $Subtype->where('parent_id',$tId)->where('flag',1)->where('subtype_code',$val['cats_code'])->value('subtype_name');
			$vo[$key]['name'] = $cats_name."-".$val['name'];
		}
		return $this->ajaxReturn($vo,'获取成功！',1);
	}
	
	/**
	 * 新增
	 */
	public function add(){
		$School = model('Schools');
		$vo = $School->where('flag',1)->where('is_device',1)->field('id,name')->select();
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	
	/**
	 * 绑定设备
	 */
	public function bindDevice(){
		$leChange = new Lechange();
		if(cache('lechange_admin_token') != null){
			$token = cache('lechange_admin_token');
		}else{
			$reback = $leChange->getRootAccessToken();
			$token = $reback['accessToken'];
			cache('lechange_admin_token',$reback['accessToken'],$reback['expireTime']);
		}
		switch (input('type')){
			case 1://绑定设备并获取通道号
				$deviceId = input('deviceId');
				$code = input('device_code');
				$bln = $leChange->checkDeviceBindOrNot($token, $deviceId);
				if($bln == false){//设备已经绑定
					$reback = $leChange->bindDevice($token, $deviceId, $code);
					if($reback['result']['code'] != "0"){
						return $this->ajaxReturn($reback['result']['msg'],'获取失败！',0);
					}
				}
				$reback = $leChange->bindDeviceInfo($token, $deviceId);
				if($reback['result']['code'] != "0"){
					return $this->ajaxReturn($reback['result']['msg'],'获取失败！',0);
				}
				//获取已经存在的通道
				$ids = Db::name('ClassLive')->where('flag',1)->where('device_id',input('deviceId'))->value('GROUP_CONCAT(channel_id)');
				foreach ($reback['result']['data']['channels'] as $key=>$val){
					if(!in_array($val['channelId'], explode(',', $ids))){
						$backData[] = $val['channelId'];
					}
				}
				return $this->ajaxReturn($backData,'获取成功！',1);
				break;
			case 2://解绑单个通道
				$info = Db::name('ClassLive')->where('flag',1)->where('id',input('id'))->find();
				$reback = $leChange->unBindLive($token, $info['live_token']);
				if($reback['result']['code'] == "0"){
					//删除单个班级
					Db::name('ClassLive')->where('flag',1)->where('id',input('id'))->setField('flag',2);
					return $this->ajaxReturn(1,'删除班级直播成功！',1);
				}else{
					return $this->ajaxReturn($reback['result']['msg'],'删除班级直播失败！',0);
				}
				break;
			case 3://根据通道创建直播地址
				$deviceId = input('deviceId');
				$channelId = input('channelId');
				//判断通道是否被绑定
				$count = Db::name('ClassLive')->where('flag',1)->where('device_id',input('deviceId'))->where('channel_id',input('channelId'))->count();
				if($count != 0){
					return $this->ajaxReturn('该通道已被绑定，如需更换，请解绑！',0,0);
				}
				$reback = $leChange->bindDeviceLive($token, $deviceId, $channelId);
				if($reback['result']['code'] == "0"){
					$backData['coverUrl'] = $reback['result']['data']['streams'][0]['coverUrl'];
					$backData['hls'] = $reback['result']['data']['streams'][0]['hls'];
					$backData['liveToken'] = $reback['result']['data']['liveToken'];
					return $this->ajaxReturn($backData,'获取成功！',1);
				}else{
					return $this->ajaxReturn($reback['result']['msg'],'获取失败！',0);
				}
				break;
            case 4://解绑设备
                $info = Db::name('ClassLive')->where('flag',1)->where('id',input('id'))->find();
                $reback = $leChange->unBindDevice($token, $info['device_id']);
                if($reback['result']['code'] == "0"){
                    //删除所有该设备的班级直播
                    Db::name('ClassLive')->where('flag',1)->where('device_id',$info['device_id'])->setField('flag',2);
                    return $this->ajaxReturn(1,'解绑成功！',1);
                }else{
                    return $this->ajaxReturn($reback['result']['msg'],'解绑失败！',0);
                }
                break;
		}
	}
	
	/**
	 * @authority 详细
	 */
	public function detailView() {
		$Model = model(request()->controller());$Type = model('Type');$Subtype = model('Subtype');
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$vo['schoolName'] = Db::name('Schools')->where('id',$vo['school_id'])->value('name');
		$tId = $Type->where('type_name',10002)->where('reserve',$vo['school_id'])->where('flag',1)->value('id');
		$classInfo = Db::name('Classes')->where('id',$vo['class_id'])->field('cats_code,name')->find();
		$cats_name = $Subtype->where('parent_id',$tId)->where('flag',1)->where('subtype_code',$classInfo['cats_code'])->value('subtype_name');
		$vo['className'] = $cats_name."-".$classInfo['name'];;
		$this->assign('vo',$vo);
		return $this->fetch();
	}
}