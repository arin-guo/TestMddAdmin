<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
/**
 * web 接口
 * @author ji
 * @version 创建时间：2018.4.4
 * 类说明
 */
class Web extends Controller{
	
	/**
	 * 获取新闻详情
	 * 
	 */
	public function reportDetail(){
		$report = model('Report');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$field = 'id,title,source,visit_num,up_num,content';
		$data = $report->where('id',$id)->setInc('visit_num');
		$data = $report->field($field)->find($id);
		$data['up_num'] = model('FriendCircleUp')->where('friend_circle_id',$id)->where('user_id',input('uid'))->count();
		if(model('FriendCircleUp')->where('friend_circle_id',$id)->where('user_id',input('uid'))->where('type',input('type')+100)->find()) $data['status'] = 1;
		else $data['status'] = 2;
		return $this->ajaxReturn($data);
	}

	/**
	 * 资讯点赞
	 * 
	 */
	public function reportUp(){
		$up = model('FriendCircleUp');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$data['friend_circle_id'] = $id;
		$data['user_id'] = input('uid');
		$data['type'] = input('type') + 100;
		if($up->where('friend_circle_id',$id)->where('user_id',input('uid'))->where('type',input('type')+100)->find())
		{
			$result = $up->where('friend_circle_id',$id)->where('user_id',input('uid'))->where('type',input('type')+100)->delete();
			return $this->ajaxReturn();
		}
		else
		{
			$result = $up->isUpdate(false)->save($data);
			return $this->ajaxReturn();
		}
	}

	/**
	 * 获取亲子游活动详情
	 * 
	 */
	public function tourDetail(){
		$tour = model('ParentChildTour');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$field = 'id,title,address,content_headimg,phone_number,begin_time,end_time,run_line,is_ready,clear_cue,money_all,price,content';
		$data = $tour->field($field)->find($id);
		$arr = array();
		// preg_match("/([\s\S]*?)联系电话/i",$data['run_line'],$matches); /[\u4e00-\u9fa5]+/
		preg_match('/\d+/',$data['address'],$matches);
		preg_match("/[\x7f-\xff]+/",$data['address'],$place);
		foreach(explode('，',$data['run_line']) as $k=>$v)
		{
			if(substr($v,5,1)) $arr[$k]['item'] = $v; 
		}
		$data['line'] = $arr;
		// $data['telphone'] = $matches[0];
		// $data['address'] = $place[0];
		$data['open'] = $data['clear_cue'];
		// $data['end'] = $data['enroll_close_time'];
		unset($data['run_line']);unset($data['clear_cue']);
		$umodel = input('type') == 3 ? model('Headmasters') : model('Parents');
		$where['is_attention']=array('like','%'.input('type').'-'.input('uid').'y%');
		$data['attention'] = $tour->where('id',input('id'))->where($where)->find() ? 1 : 2;
		$data['status'] = input('type') == 3 && model('TourSchoolChoose')->where('tour_id',$id)->where('school_id',$umodel->where('id',input('uid'))->find()['school_id'])->where('status',1)->where('is_pass',2)->find() ? 2 : (input('type') == 1 ? 2 : 3);
		$data['begin'] = '';$data['end'] = '';$data['close'] = '';$data['cue'] = '';$data['maybe'] = '';
		if($cData = model('TourSchoolChoose')->where('tour_id',$id)->where('school_id',$umodel->where('id',input('uid'))->find()['school_id'])->where('status',1)->where('is_pass',1)->find())
		{
			$data['begin'] = $cData['leader_begin_time'];
			$data['end'] = $cData['leader_end_time'];
			$data['close'] = $cData['leader_enroll_time'];
			$data['cue'] = $cData['leader_cue'];
			$data['maybe'] = $cData['maybe_mun'];
			$data['status'] = input('type') == 3 ? $cData['status'] : (model('TourSignUp')->where('tour_id',$id)->where('flag',1)->where('parent_id',input('uid'))->value('status') ? model('TourSignUp')->where('tour_id',$id)->where('flag',1)->where('parent_id',input('uid'))->value('status') : (input('type') == 1 ? 2 : 3));
		}
		return $this->ajaxReturn($data);
	}

	/**
	 * 获取旅行社团体游活动详情
	 * 
	 */
	public function travelDetail(){
		$tour = model('ParentChildTour');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$field = 'id,title,address,content_headimg,phone_number,begin_time,end_time,run_line,is_ready,clear_cue,money_all,cance_say,need_thing,not_money,price,content';
		$data = $tour->field($field)->find($id);
		$arr = array();
		// preg_match("/([\s\S]*?)联系电话/i",$data['run_line'],$matches); /[\u4e00-\u9fa5]+/
		/*preg_match('/\d+/',$data['address'],$matches);
		preg_match("/[\x7f-\xff]+/",$data['address'],$place);*/
		foreach(explode('，',$data['run_line']) as $k=>$v)
		{
			if(substr($v,5,1)) $arr[$k]['item'] = $v; 
		}
		$data['line'] = $arr;
		$data['paly_time'] = $data['clear_cue'];unset($data['clear_cue']);
		/*// $data['telphone'] = $matches[0];
		// $data['address'] = $place[0];
		// $data['end'] = $data['enroll_close_time'];
		*/unset($data['run_line']);
		$where['is_attention']=array('like','%'.input('type').'-'.input('uid').'y%');
		$data['content_headimg'] = $data['content_headimg'] ? config('view_replace_str.__IMGROOT__') . $data['content_headimg'] : '';
		$data['attention'] = $tour->where('id',input('id'))->where($where)->find() ? 1 : 2;
		$data['status'] = model('TourSignUp')->where('tour_id',$id)->where('flag',1)->where('parent_id',input('uid'))->where('totle_num',input('type'))->value('status') ? model('TourSignUp')->where('tour_id',$id)->where('flag',1)->where('parent_id',input('uid'))->where('totle_num',input('type'))->value('status') : 2;
		/*if($cData = model('TourSchoolChoose')->where('tour_id',$id)->where('school_id',$umodel->where('id',input('uid'))->find()['school_id'])->where('status',1)->where('is_pass',1)->find())
		{
			$data['begin'] = $cData['leader_begin_time'];
			$data['end'] = $cData['leader_end_time'];
			$data['close'] = $cData['leader_enroll_time'];
			$data['cue'] = $cData['leader_cue'];
			$data['maybe'] = $cData['maybe_mun'];
			$data['status'] = input('type') == 3 ? $cData['status'] : (model('TourSignUp')->where('tour_id',$id)->where('flag',1)->where('parent_id',input('uid'))->value('status') ? model('TourSignUp')->where('tour_id',$id)->where('flag',1)->where('parent_id',input('uid'))->value('status') : (input('type') == 1 ? 2 : 3));
		}*/
		return $this->ajaxReturn($data);
	}

	/**
	 * 旅行社游点击预约/点击改约
	 * 
	 */
	public function orderDetail(){
		$sign = model('TourSignUp');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$where['tour_id'] = $id;
		$where['parent_id'] = input('uid');
		$where['totle_num'] = input('type');
		$data = Db::view('Tour_sign_up s','id,tour_id,child_id,link_name AS child,link_tel AS tel,totle_num,status,big_num,small_num,class_id,remark')->view('Parent_child_tour t','title,address,photo,intro,price,begin_time,end_time','t.id = s.tour_id')->where($where)->find();
		if(empty($sign->where($where)->find())/* || $sign->where($where)->find()['status'] == 2*/)
		{
			$tour = model('ParentChildTour');
			$wheres['id'] = $id;
			$data = $tour->field('id,title,photo,intro,price,address,begin_time,end_time')->where($wheres)->find();
			$data['big_num'] = 0;$data['small_num'] = 0;$data['tel'] = '';
			$data['remark'] = '';$data['status'] = 2;
		}
		$data['photo'] = 'http://test.upload.mengdd.net/' . $data['photo'];
		return $this->ajaxReturn($data);
	}

	/**
	 * 家长预约(再约)提交
	 * 
	 */
	public function goOrder(){
		$sign = model('TourSignUp');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$linArr = Db::view('Parents p','id')->view('Parent_child r','child_id','r.parent_id = p.id')->view('Childs c','id AS cid,school_id,classes_id,realname','c.id = r.child_id')->where('p.id',input('uid'))->where('r.flag',1)->select();
		$data['link_tel'] = input('tel');
		$data['big_num'] = input('bnum');
		$data['small_num'] = input('snum');
		$data['totle_num'] = input('type');
		$data['parent_id'] = input('uid');
		$data['remark'] = input('remark');
		$data['tour_id'] = $id;
		$data['child_id'] = input('child') ? input('child') : 0;
		$data['link_name'] = strpos(input('child'),',') ? $linArr[0]['realname'] . ',' . $linArr[1]['realname'] : model('Childs')->where('id',input('child'))->value('realname');
		$data['school_id'] = $linArr[0]['school_id'] ? $linArr[0]['school_id'] : 0;
		$data['class_id'] = $linArr[0]['classes_id'] ? $linArr[0]['classes_id'] : 0;
		if($sign->where('tour_id',$id)->where('parent_id',input('uid'))->where('totle_num',input('type'))->find()['status'] == 1)
		{
			if(input('flag') == 1)
			{
				$result = $sign->isUpdate(true)->save($data,['tour_id'=>$id,'parent_id'=>input('uid'),'totle_num'=>input('type')]);
				if($result) $msge = '保存成功';else $msge = '没有修改';
			}
			else
			{
				$sign->isUpdate(true)->save(array('status'=>2),['tour_id'=>$id,'parent_id'=>input('uid'),'totle_num'=>input('type')]);$msge = '取消成功';
			}
		}
		elseif($sign->where('tour_id',$id)->where('parent_id',input('uid'))->where('totle_num',input('type'))->find()['status'] == 2)
		{
			$result = $sign->isUpdate(true)->save(array('status'=>1),['tour_id'=>$id,'parent_id'=>input('uid'),'totle_num'=>input('type')]);$msge = '预约成功';
		}
		else
		{
			$result = $sign->isUpdate(false)->save($data);$msge = '预约成功';
		}
		if($result)
		{
			return $this->ajaxReturn($msge);
		}
		else
		{
			return $this->err('失败');
		}
	}

	/**
	 * 编辑获取温馨提醒
	 * 
	 */
	public function cueDetail(){
		$tour = model('ParentChildTour');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$data = $tour->field('clear_cue')->find($id);
		return $this->ajaxReturn($data);
	}

	/**
	 * 亲子游活动详情输入温馨提醒
	 * 
	 */
	public function tourCue(){
		$choose = model('TourSchoolChoose');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$data['leader_cue'] = input('cue');
		$result = $choose->isUpdate(true)->save($data,['tour_id'=>input('id'),'school_id'=>model('Headmasters')->where('id',input('uid'))->find()['school_id']]);
		if($result)
		{
			return $this->ajaxReturn();
		}
		else
		{
			return $this->err('失败');
		}
	}

	/**
	 * 亲子游活动园长发推取推
	 * 
	 */
	public function tourChoose(){
		$choose = model('TourSchoolChoose');
		$Leader = model('Headmasters');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$where['tour_id'] = input('id');
		$where['school_id'] = $Leader->where('id',input('uid'))->find()['school_id'];
		$data['tour_id'] = input('id');
		$data['status'] = 1;
		$data['school_id'] = $Leader->where('id',input('uid'))->find()['school_id'];
		$data['leader_cue'] = input('cue');
		$data['leader_enroll_time'] = input('close');
		$data['leader_begin_time'] = input('begin');
		$data['leader_end_time'] = input('end');
		$data['maybe_mun'] = input('maybe');
		$url = "http://test.admin.mengdd.net/index/Spa/getSpa#/familytripdetail/id/" . $id;
		if($choose->where($where)->find()['status'] == 2)
		{
			$result = $choose->isUpdate(true)->save($data,['tour_id'=>input('id'),'school_id'=>$where['school_id']]);
			model('Banners')->isUpdate(true)->save(array('flag'=>1),['url'=>$url,'school_id'=>$where['school_id']]);
		}
		elseif($choose->where($where)->find()['status'] == 1)
		{
			$result = $choose->isUpdate(true)->save(array('status'=>2,'is_pass'=>2),['tour_id'=>input('id'),'school_id'=>$where['school_id']]);
			model('Banners')->isUpdate(true)->save(array('flag'=>2),['url'=>$url,'school_id'=>$where['school_id']]);
		}
		else
		{
			if(model('ParentChildTour')->where('id',$id)->value('is_ready') == 1 || empty(model('ParentChildTour')->where('id',$id)->value('is_ready'))) return $this->err('失败');
			$result = $choose->isUpdate(false)->save($data);
			model('Banners')->isUpdate(false)->save(array('photo'=>model('ParentChildTour')->find($id)['banner_photo'],'title'=>model('ParentChildTour')->find($id)['title'],'url'=>$url,'school_id'=>$where['school_id']));
		}
		if($result)
		{
			return $this->ajaxReturn();
		}
		else
		{
			return $this->err('失败');
		}
	}

	/**
	 * 获取家长报名详情
	 * 
	 */
	public function signDetail(){
		$sign = model('TourSignUp');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$where['tour_id'] = $id;
		$where['parent_id'] = input('uid');
		$data = Db::view('Tour_sign_up s','id,tour_id,child_id,link_name AS child,link_tel AS tel,totle_num,status,class_id,remark')->view('Parent_child_tour t','title,address,photo,price,begin_time,end_time','t.id = s.tour_id')->where($where)->find();
		if(strpos($data['child'],',')){$data['child'] = array(0=>array('id'=>explode(',',$data['child_id'])[0],'name'=>explode(',',$data['child'])[0],'check'=>1,'class'=>model('Classes')->where('id',$data['class_id'])->value('name')),1=>array('id'=>explode(',',$data['child_id'])[1],'name'=>explode(',',$data['child'])[1],'check'=>1,'class'=>model('Classes')->where('id',$data['class_id'])->value('name')));}else{if(model('ParentChild')->where('parent_id',input('uid'))->where('flag',1)->count() > 1){$data['child'] = array(0=>array('id'=>$data['child_id'],'name'=>$data['child'],'check'=>1,'class'=>model('Classes')->where('id',$data['class_id'])->value('name')),1=>array('id'=>$nid = array_values(array_diff(array(0=>array_column(model('ParentChild')->field('child_id')->where('parent_id',input('uid'))->where('flag',1)->select(),'child_id')[0],1=>array_column(model('ParentChild')->field('child_id')->where('parent_id',input('uid'))->where('flag',1)->select(),'child_id')[1]),array(0=>$data['child_id'])))[0],'name'=>model('Childs')->where('id',$nid)->value('realname'),'check'=>2,'class'=>model('Classes')->where('id',$data['class_id'])->value('name')));}else{$data['child'] = array(0=>array('id'=>$data['child_id'],'name'=>$data['child'],'check'=>1,'class'=>model('Classes')->where('id',$data['class_id'])->value('name')));}}
		if(empty($sign->where($where)->find())/* || $sign->where($where)->find()['status'] == 2*/)
		{
			$tour = model('ParentChildTour');
			$linArr = Db::view('Parents p','tel')->view('Parent_child r','flag','r.parent_id = p.id')->view('Childs c','id,realname,classes_id AS class_id','c.id = r.child_id')->where('p.id',input('uid'))->where('r.flag',1)->select();
			$wheres['id'] = $id;
			$data = $tour->field('id,title,photo,price,address,begin_time,end_time')->where($wheres)->find();
			$data['child'] = array(0=>array('id'=>$linArr[0]['id'],'name'=>$linArr[0]['realname'],'check'=>2,'class'=>model('Classes')->where('id',$linArr[0]['class_id'])->value('name')));
			if(count($linArr) > 1) $data['child'] = array(0=>array('id'=>$linArr[0]['id'],'name'=>$linArr[0]['realname'],'check'=>2,'class'=>model('Classes')->where('id',$linArr[0]['class_id'])->value('name')),1=>array('id'=>$linArr[1]['id'],'name'=>$linArr[1]['realname'],'check'=>2,'class'=>model('Classes')->where('id',$linArr[0]['class_id'])->value('name')));
			$data['tel'] = $linArr[0]['tel'];
			$data['status'] = 3;$data['remark'] = '';
		}
		$data['photo'] = 'http://test.upload.mengdd.net/' . $data['photo'];
		preg_match("/[\x7f-\xff]+/",$data['address'],$place);
		$data['address'] = $place[0];
		$data['leader_begin_time'] = model('TourSchoolChoose')->where('tour_id',$id)->where('school_id',model('Parents')->where('id',input('uid'))->find()['school_id'])->value('leader_begin_time');
		$data['leader_end_time'] = model('TourSchoolChoose')->where('tour_id',$id)->where('school_id',model('Parents')->where('id',input('uid'))->find()['school_id'])->value('leader_end_time');
		return $this->ajaxReturn($data);
	}

	/**
	 * 家长报名(再报)提交
	 * 
	 */
	public function goSign(){
		$sign = model('TourSignUp');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$linArr = Db::view('Parents p','id')->view('Parent_child r','child_id','r.parent_id = p.id')->view('Childs c','id AS cid,school_id,classes_id,realname','c.id = r.child_id')->where('p.id',input('uid'))->where('r.flag',1)->select();
		$data['link_tel'] = input('tel');
		$data['totle_num'] = input('num');
		$data['parent_id'] = input('uid');
		$data['remark'] = input('remark');
		$data['tour_id'] = $id;
		$data['child_id'] = input('child');
		$data['link_name'] = strpos(input('child'),',') ? $linArr[0]['realname'] . ',' . $linArr[1]['realname'] : model('Childs')->where('id',input('child'))->value('realname');
		$data['school_id'] = $linArr[0]['school_id'];
		$data['class_id'] = $linArr[0]['classes_id'];
		if(input('status') == 1)
		{
			$result = $sign->isUpdate(true)->save($data,['tour_id'=>$id,'parent_id'=>input('uid')]);
		}
		elseif($sign->where('tour_id',$id)->where('parent_id',input('uid'))->find()['status'] == 2)
		{
			$result = $sign->isUpdate(true)->save(array('status'=>1,'totle_num'=>input('num'),'link_tel'=>input('tel')),['tour_id'=>$id,'parent_id'=>input('uid')]);
		}
		else
		{
			$result = $sign->isUpdate(false)->save($data);
		}
		if($result)
		{
			return $this->ajaxReturn();
		}
		else
		{
			return $this->err('失败');
		}
	}

	/**
	 * 取消报名
	 * 
	 */
	public function cancelSign(){
		$sign = model('TourSignUp');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$data['status'] = 2;
		$result = $sign->isUpdate(true)->save($data,['tour_id'=>input('id'),'parent_id'=>input('uid')]);
		if($result)
		{
			return $this->ajaxReturn();
		}
		else
		{
			return $this->err('失败');
		}
	}

	/**
	 * 删除
	 * 
	 */
	public function deleteTour(){
		$choose = model('TourSchoolChoose');$Leader = model('Headmasters');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$data['flag'] = 2;
		$result = $choose->isUpdate(true)->save($data,['tour_id'=>$id,'school_id'=>$Leader->where('id',input('uid'))->find()['school_id']]);
		if(input('type') == 1)
		{
			$result = model('TourSignUp')->isUpdate(true)->save($data,['tour_id'=>$id,'parent_id'=>input('uid')]);
		}
		if($result)
		{
			return $this->ajaxReturn();
		}
		else
		{
			return $this->err('失败');
		}
	}

	/**
	 * 关注取关
	 * 
	 */
	public function tourAttention(){
		$tour = model('ParentChildTour');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		if(input('type') == 3){$data['is_attention'] = $tour->where('id',input('id'))->value('is_attention').'3-'.input('uid').'y,';
		$where['is_attention']=array('like','%3-'.input('uid').'y%');
		$isin = $tour->where('id',$id)->where($where)->find();
		if($isin) $data['is_attention'] = str_replace('3-'.input('uid').'y,', '', $isin['is_attention']);}elseif(input('type') == 2){$data['is_attention'] = $tour->where('id',input('id'))->value('is_attention').'2-'.input('uid').'y,';
		$where['is_attention']=array('like','%2-'.input('uid').'y%');
		$isin = $tour->where('id',$id)->where($where)->find();
		if($isin) $data['is_attention'] = str_replace('2-'.input('uid').'y,', '', $isin['is_attention']);}else{$data['is_attention'] = $tour->where('id',input('id'))->value('is_attention').'1-'.input('uid').'y,';
		$where['is_attention']=array('like','%1-'.input('uid').'y%');
		$isin = $tour->where('id',$id)->where($where)->find();
		if($isin) $data['is_attention'] = str_replace('1-'.input('uid').'y,', '', $isin['is_attention']);}
		$result = $tour->isUpdate(true)->save($data,['id'=>input('id')]);
		if($result)
		{
			return $this->ajaxReturn();
		}
		else
		{
			return $this->err('失败');
		}
	}

	/**
	 * 获取毕业照详情
	 * 
	 */
	public function albumDetail(){
		$tour = model('ChildSchoolAlbum');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$data['sku'] = '';
		$data['begin'] = '';
		$data['end'] = '';
		$data['close'] = '';
		$data['cue'] = '';
		$umodel = input('type') == 3 ? model('Headmasters') : (input('type') == 1 ? model('Parents') : model('Teachers'));
		// if(input('type') != 3 && !model('AlbumSchoolChoose')->where('album_id',$id)->where('school_id',$umodel->where('id',input('uid'))->find()['school_id'])->where('status',1)->find()) return $this->err('园长没发布毕业照');
		if($cData = model('AlbumSchoolChoose')->where('album_id',$id)->where('school_id',$umodel->where('id',input('uid'))->find()['school_id'])->where('status',1)->find())
		{
			if(input('type') == 1 && model('AlbumSignUp')->where('parent_id',input('uid'))->where('status',1)->find()) $data['status'] = 1;
			if(input('type') == 1 && model('AlbumSignUp')->where('parent_id',input('uid'))->where('status',2)->find()) $data['status'] = 2;
			if(input('type') == 1 && !model('AlbumSignUp')->where('parent_id',input('uid'))->find()) $data['status'] = 2;
			$data['begin'] = $cData['leader_begin_time'];
			$data['end'] = $cData['leader_end_time'];
			$data['close'] = $cData['leader_enroll_time'];
			$data['cue'] = $cData['leader_cue'];
			$data['sku'] = $cData['pid'];
		}
		return $this->ajaxReturn($data);
	}

	/**
	 * 毕业照园长发推取推
	 * 
	 */
	public function albumChoose(){
		$choose = model('AlbumSchoolChoose');
		$Leader = model('Headmasters');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$where['album_id'] = input('id');
		$where['school_id'] = $Leader->where('id',input('uid'))->find()['school_id'];
		$data['album_id'] = input('id');
		$data['pid'] = input('sku');
		$data['status'] = 1;
		$data['school_id'] = $Leader->where('id',input('uid'))->find()['school_id'];
		$data['leader_cue'] = input('cue');
		$data['leader_enroll_time'] = input('close');
		$data['leader_begin_time'] = input('begin');
		$data['leader_end_time'] = input('end');
		$data['maybe_mun'] = input('maybe');
		$url = "http://test.admin.mengdd.net/index/Spa/getSpa#/graduation/detail/id/" . $id;
		if($choose->where($where)->find()['status'] == 2)
		{
			$result = $choose->isUpdate(true)->save($data,['album_id'=>input('id'),'school_id'=>$where['school_id']]);
			model('Banners')->isUpdate(true)->save(array('flag'=>1),['url'=>$url,'school_id'=>$where['school_id']]);
		}
		elseif($choose->where($where)->find()['status'] == 1)
		{
			$result = $choose->isUpdate(true)->save(array('status'=>2,'cause'=>input('cause')),['album_id'=>input('id'),'school_id'=>$where['school_id']]);
			foreach(model('AlbumSignUp')->where('album_id',input('id'))->where('school_id',$where['school_id'])->select() as $k=>$v){model('AlbumSignUp')->isUpdate(true)->save(array('status'=>2),['id'=>$v['id']]);}
			model('Banners')->isUpdate(true)->save(array('flag'=>2),['url'=>$url,'school_id'=>$where['school_id']]);
		}
		else
		{
			$result = $choose->isUpdate(false)->save($data);
			model('Banners')->isUpdate(false)->save(array('photo'=>model('ParentChildTour')->find($id)['banner_photo'],'title'=>'毕业照','is_on'=>3,'type'=>2,'url'=>$url,'school_id'=>$where['school_id']));
		}
		if($result)
		{
			return $this->ajaxReturn();
		}
		else
		{
			return $this->err('失败');
		}
	}

	/**
	 * 毕业照获取家长报名详情
	 * 
	 */
	public function takeDetail(){
		$sign = model('AlbumSignUp');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$where['album_id'] = $id;
		$where['parent_id'] = input('uid');
		$data = Db::view('Album_sign_up s','id,album_id,child_id,link_name AS child,link_tel AS tel,totle_num,status,class_id,remark')->view('Child_school_album t','title,photo,price,intro,price_two,price_three,begin_time,end_time','t.id = s.album_id')->where($where)->find();
		if(strpos($data['child'],',')){$data['child'] = array(0=>array('id'=>explode(',',$data['child_id'])[0],'name'=>explode(',',$data['child'])[0],'check'=>1,'class'=>model('Classes')->where('id',$data['class_id'])->value('name')),1=>array('id'=>explode(',',$data['child_id'])[1],'name'=>explode(',',$data['child'])[1],'check'=>1,'class'=>model('Classes')->where('id',$data['class_id'])->value('name')));}else{if(model('ParentChild')->where('parent_id',input('uid'))->where('flag',1)->count() > 1){$data['child'] = array(0=>array('id'=>$data['child_id'],'name'=>$data['child'],'check'=>1,'class'=>model('Classes')->where('id',$data['class_id'])->value('name')),1=>array('id'=>$nid = array_values(array_diff(array(0=>array_column(model('ParentChild')->field('child_id')->where('parent_id',input('uid'))->where('flag',1)->select(),'child_id')[0],1=>array_column(model('ParentChild')->field('child_id')->where('parent_id',input('uid'))->where('flag',1)->select(),'child_id')[1]),array(0=>$data['child_id'])))[0],'name'=>model('Childs')->where('id',$nid)->value('realname'),'check'=>2,'class'=>model('Classes')->where('id',$data['class_id'])->value('name')));}else{$data['child'] = array(0=>array('id'=>$data['child_id'],'name'=>$data['child'],'check'=>1,'class'=>model('Classes')->where('id',$data['class_id'])->value('name')));}}
		if(empty($sign->where($where)->find())/* || $sign->where($where)->find()['status'] == 2*/)
		{
			$tour = model('ChildSchoolAlbum');
			$linArr = Db::view('Parents p','tel')->view('Parent_child r','flag','r.parent_id = p.id')->view('Childs c','id,realname,classes_id AS class_id','c.id = r.child_id')->where('p.id',input('uid'))->where('r.flag',1)->select();
			$wheres['id'] = $id;
			$data = $tour->field('id,title,photo,price,intro,price_two,price_three,begin_time,end_time')->where($wheres)->find();
			$data['child'] = array(0=>array('id'=>$linArr[0]['id'],'name'=>$linArr[0]['realname'],'check'=>2,'class'=>model('Classes')->where('id',$linArr[0]['class_id'])->value('name')));
			if(count($linArr) > 1) $data['child'] = array(0=>array('id'=>$linArr[0]['id'],'name'=>$linArr[0]['realname'],'check'=>2,'class'=>model('Classes')->where('id',$linArr[0]['class_id'])->value('name')),1=>array('id'=>$linArr[1]['id'],'name'=>$linArr[1]['realname'],'check'=>2,'class'=>model('Classes')->where('id',$linArr[0]['class_id'])->value('name')));
			$data['tel'] = $linArr[0]['tel'];
			$data['status'] = 3;$data['remark'] = '';
		}
		$data['photo'] = 'http://test.upload.mengdd.net/' . $data['photo'];
		$data['sku'] = model('AlbumSchoolChoose')->where('album_id',$id)->where('school_id',model('Parents')->where('id',input('uid'))->find()['school_id'])->value('pid');
		$data['leader_begin_time'] = model('AlbumSchoolChoose')->where('album_id',$id)->where('school_id',model('Parents')->where('id',input('uid'))->find()['school_id'])->value('leader_begin_time');
		$data['leader_end_time'] = model('AlbumSchoolChoose')->where('album_id',$id)->where('school_id',model('Parents')->where('id',input('uid'))->find()['school_id'])->value('leader_end_time');
		$data['leader_enroll_time'] = model('AlbumSchoolChoose')->where('album_id',$id)->where('school_id',model('Parents')->where('id',input('uid'))->find()['school_id'])->value('leader_enroll_time');
		return $this->ajaxReturn($data);
	}

	/**
	 * 毕业照家长报名(再报)提交
	 * 
	 */
	public function goTake(){
		$sign = model('AlbumSignUp');
		if(is_null($id = input('id'))){
			return $this->err('参数错误！');
		}
		$linArr = Db::view('Parents p','id')->view('Parent_child r','child_id','r.parent_id = p.id')->view('Childs c','id AS cid,school_id,classes_id,realname','c.id = r.child_id')->where('p.id',input('uid'))->where('r.flag',1)->select();
		$data['link_tel'] = input('tel');
		$data['totle_num'] = strpos(input('child'),',') ? 2 : 1;
		$data['parent_id'] = input('uid');
		$data['album_id'] = $id;
		$data['remark'] = input('sku');
		$data['child_id'] = input('child');
		$data['link_name'] = strpos(input('child'),',') ? $linArr[0]['realname'] . ',' . $linArr[1]['realname'] : model('Childs')->where('id',input('child'))->value('realname');
		$data['school_id'] = $linArr[0]['school_id'];
		$data['class_id'] = $linArr[0]['classes_id'];
		if($sign->where('album_id',$id)->where('parent_id',input('uid'))->find()['status'] == 1)
		{
			$result = $sign->isUpdate(true)->save(array('status'=>2),['album_id'=>$id,'parent_id'=>input('uid'),'status'=>1]);
		}
		elseif($sign->where('album_id',$id)->where('parent_id',input('uid'))->find()['status'] == 2)
		{
			$result = $sign->isUpdate(true)->save(array('status'=>1,'totle_num'=>strpos(input('child'),',') ? 2 : 1,'link_tel'=>input('tel')),['album_id'=>$id,'parent_id'=>input('uid'),'status'=>2]);
		}
		else
		{
			$result = $sign->isUpdate(false)->save($data);
		}
		if($result)
		{
			return $this->ajaxReturn();
		}
		else
		{
			return $this->err('失败');
		}
	}
	
	public function ajaxReturn($data= array(),$info='y'/*,$status=0*/){
		$result = array(
				'result' => $info,
				'data' => $data/*,
				'status' => $status*/
		);
		return json($result);
	}
	
	/**
	 * 失败
	 * @param $errorMsg
	 * @param string $errorCode
	 * @param string $ambulance
	 * @return array
	 */
	public function err($errorMsg,$errorCode='',$ambulance=''){
		$result = array(
				'result' => 'n',
				'errorCode' => $errorCode,
				'errorMsg' => $errorMsg
		);
	
		if(!empty($ambulance)){
			$result['ambulance'] = $ambulance;
		}
		return json($result);
	}
	
	// /**
 //     * 成功
 //     * @param $data
 //     * @param $nextStartId
 //     * @param string $ambulance
 //     * @return array
 //     */
	// public function suc($data,$nextStartId='',$ambulance=''){
	// 	$data = $this->handleBackData($data);
	// 	$result = array(
	// 			'result' => 'y',
	// 			'data' => is_null($data)?"":$data,
	// 	);
	// 	if(!empty($ambulance)){
	// 		$result['ambulance'] = $ambulance;
	// 	}
	// 	if(is_int($nextStartId)){//分页参数
	// 		$result['nextStartId'] = $nextStartId;
	// 	}
	// 	return json($result);
	// }
	
	// /**
	//  * 递归数组键改为驼峰命名
	//  * @param unknown $data
	//  * @param unknown $backData
	//  */
	// protected function handleBackData($data = array()){
	// 	$backData = array();
	// 	if(is_object($data)){
	// 		$data = $data->toArray();
	// 	}
	// 	if(!is_array($data)||empty($data)){
	// 		return json($backData);
	// 	}
	// 	foreach($data as $k=>$v){
	// 		if(is_object($v)){
	// 			$v = $v->toArray();
	// 		}
	// 		$_key = $this->convertUnder($k);
	// 		$backData[$_key]= is_array($v)?$this->handleBackData($v):$v;
	// 	}
	// 	return json($backData);
	// }
	
	// /**
	//  * 下划线转驼峰命名
	//  * @param unknown $str
	//  * @param string $ucfirst  如为true 首字母大写
	//  * @return Ambigous <string, unknown>
	//  */
	// protected function convertUnder( $str , $ucfirst = false){
	// 	$str = ucwords(str_replace('_', ' ', $str));
	// 	$str = str_replace(' ','',lcfirst($str));
	// 	return $ucfirst ? json(ucfirst($str)) : json($str);
	// }
}