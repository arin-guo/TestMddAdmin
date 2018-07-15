<?php
namespace app\school\controller;
use app\school\controller\Base;
use think\Db;
/**
 * 餐饮模块每日控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月28日 上午10:16:19 
 * 类说明
 */
class CookbookDate extends Base{
	/**
	 * @authority 浏览
	 */
	public function index(){
		$date = date('Y-m-d',time());
		$this->assign('date',$date);
		return $this->fetch();
	}
    /**
     * 根据日期获取菜谱,默认一周的菜谱
     */
    public function getCookbooksByWeek(){
        $gettime = input('begin');
        if(empty($gettime)){
            return $this->backJson(-200,'没有传入日期!');
        }
        $schoolId = session('school_info_id');
        $time = strtotime($gettime);
        $w = date('w',$time);
        $begin = $time - 3600*24*$w;
        $end = $begin + 3600*24*6;
        $result = Db::view('CookbookDate','id,cookbook_id,day_time,type')
                ->view('Cookbook','id as cid','CookbookDate.cookbook_id = Cookbook.id')
                ->where('CookbookDate.school_id',$schoolId)
                ->where('CookbookDate.flag',1)
                ->where('Cookbook.school_id',$schoolId)
                ->where('Cookbook.flag',1)
                ->where('day_time','between time',[date('Y-m-d',$begin),date('Y-m-d',$end)])
                ->order('day_time asc')
                ->order('CookbookDate.type asc')
                ->select();
        $week = array('星期日','星期一','星期二','星期三','星期四','星期五','星期六');
        $weekday = array();
        for ($i = 0;$i < 7;$i++){
            $weekday[$i] = date('Y-m-d',$begin+3600*24*$i);
        }
        //return var_dump($result);
        foreach($weekday as $key=>$val){
            $data[$key]['id'] = $key+1;
            $data[$key]['date'] = $val." ".$week[date('w',strtotime($val))];
            $data[$key]['cookbook']['breakfast'] = [];
            $data[$key]['cookbook']['morningTea'] = [];
            $data[$key]['cookbook']['lunch'] = [];
            $data[$key]['cookbook']['afternoonTea'] = [];
            foreach($result as $k =>$v){
                if($val == $v['day_time']){
                    if($v['type'] == 1){
                        array_push($data[$key]['cookbook']['breakfast'],$v['cid']);
                    }elseif ($v['type'] == 2){
                        array_push($data[$key]['cookbook']['morningTea'],$v['cid']);
                    }elseif ($v['type'] == 3){
                        array_push($data[$key]['cookbook']['lunch'],$v['cid']);
                    }elseif ($v['type'] == 4){
                        array_push($data[$key]['cookbook']['afternoonTea'],$v['cid']);
                    }
                }
            }
        }
        return $this->backJson(200,'',$data);
    }
    /**
     * 根据日期获取菜谱,默认一天的菜谱
     */
    public function getCookbooksByDay(){
        $gettime = input('begin');
        if(empty($gettime)){
            return $this->ajaxReturn('','没有传入开始时间',1);
        }
        $schoolId = session('school_info_id');
        $result = Db::view('CookbookDate','id,cookbook_id,day_time,type')
            ->view('Cookbook','id as cid','CookbookDate.cookbook_id = Cookbook.id')
            ->where('CookbookDate.school_id',$schoolId)
            ->where('CookbookDate.flag',1)
            ->where('Cookbook.school_id',$schoolId)
            ->where('Cookbook.flag',1)
            ->where('day_time',$gettime)
            ->order('day_time asc')
            ->order('CookbookDate.type asc')
            ->select();
        $week = array('星期日','星期一','星期二','星期三','星期四','星期五','星期六');
        $data['date'] = $gettime." ".$week[date('w',strtotime($gettime))];
        $data['cookbook']['breakfast'] = [];
        $data['cookbook']['morningTea'] = [];
        $data['cookbook']['lunch'] = [];
        $data['cookbook']['afternoonTea'] = [];
        foreach ($result as $key=>$val){
            if($val['type'] == 1){
                $data['cookbook']['breakfast'][] = $val['cid'];
            }elseif ($val['type'] == 2){
                $data['cookbook']['morningTea'][] = $val['cid'];
            }elseif ($val['type'] == 3){
                $data['cookbook']['lunch'][] = $val['cid'];
            }elseif ($val['type'] == 4){
                $data['cookbook']['afternoonTea'][] = $val['cid'];
            }
        }
        return $this->backJson(200,'',$data);
    }
    /**
     * 获取对应菜的信息
     */
    function getCookbooks(){
        $cid = input('cookbooks');
        //$cookbookId = explode(',',$cid);
        $model = model('Cookbook');
        if(empty($cid)){
            $result = $model->field('id,img,name,type')->where('school_id',session('school_info_id'))->where('flag',1)->select();
        }else{
            $result = $model->field('id,img,name,type')->where('school_id',session('school_info_id'))->where('id','in',$cid)->where('flag',1)->select();
        }
        foreach ($result as $key=>$val){
            $data[$key]['id'] = $val['id'];
            $data[$key]['imgurl'] = !empty($val['img'])?config('view_replace_str.__IMGROOT__').$val['img']:'';
            $data[$key]['name'] = $val['name'];
            $data[$key]['type'] = $val['type'];
        }
        return $this->backJson(200,'',$data);
    }
    /**
     * 编辑每日菜谱
     */
    public function editCookbooks(){
        $day = input('date');
        $ck = input('cookbook');
        $model = model('CookbookDate');
        if (empty($day) || empty($ck)){
            return $this->backJson(-200,'参数缺失!');
        }
        $ck = json_decode($ck,true);
        $update['flag'] = 2;
        $where['school_id'] = session('school_info_id');
        $where['day_time'] = $day;
        $model->save($update,$where);
        $i = 0;
        foreach($ck as $key=>$val){
            if(!empty($val['selected'])){
                foreach ($val['selected'] as $k=>$v){
                    $list[$i]['cookbook_id'] = $v;
                    $list[$i]['type'] = $val['type'];
                    $list[$i]['school_id'] = $where['school_id'];
                    $list[$i]['day_time'] = $where['day_time'];
                    $list[$i]['flag'] = 1;
                    $i++;
                }
            }
        }
        //return var_dump($list);
        $res = $model->saveAll($list);
        return $this->backJson(200,'菜谱修改成功!');
    }
    /**
     * 复制一周的菜谱到下一周
     */
    public function copyWeekCookbook(){
        $gettime = input('begin');
        if(empty($gettime)){
            return $this->backJson(-200,'没有传入日期!');
        }
        $model = model('CookbookDate');
        $schoolId = session('school_info_id');
        $time = strtotime($gettime);
        $w = date('w',$time);
        $begin = $time - 3600*24*$w;
        $end = $begin + 3600*24*6;
        //获取选中周的菜谱信息
        $result = Db::view('CookbookDate','id,cookbook_id,day_time,type')
            ->view('Cookbook','id as cid','CookbookDate.cookbook_id = Cookbook.id')
            ->where('CookbookDate.school_id',$schoolId)
            ->where('CookbookDate.flag',1)
            ->where('Cookbook.school_id',$schoolId)
            ->where('Cookbook.flag',1)
            ->where('day_time','between time',[date('Y-m-d',$begin),date('Y-m-d',$end)])
            ->order('day_time asc')
            ->order('CookbookDate.type asc')
            ->select();

        foreach($result as $key=>$val){
            $dayTime =  strtotime($val['day_time']);
            $dayTime = $dayTime + 3600*24*7;
            $list[$key]['day_time'] = date('Y-m-d',$dayTime);
            $list[$key]['cookbook_id'] = $val['cookbook_id'];
            $list[$key]['type'] = $val['type'];
            $list[$key]['school_id'] = $schoolId;
            $list[$key]['flag'] = 1;
        }
        //return var_dump($list);
        $nextbegin = $begin + 3600*24*7;
        $nextend = $end + 3600*24*7;
        Db::name('CookbookDate')->where('day_time','between time',[date('Y-m-d',$nextbegin),date('Y-m-d',$nextend)])->where('school_id',$schoolId)->where('flag',1)->update(['flag'=>2]);
        $res = $model->saveAll($list);
        return $this->backJson(200,'成功复制到下周!');

    }
    /**
     * 删除某一天的菜谱
     */
    public function delCookbooks(){
        $gettime = input('begin');
        if(empty($gettime)){
            return $this->backJson(-200,'没有传入日期!');
        }
        $model = model('CookbookDate');
        $schoolId = session('school_info_id');
        $model->where('school_id',$schoolId)->where('day_time',$gettime)->update(['flag'=>2]);
        return $this->backJson(200,'当天菜谱已删除!');
    }
	/**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		//获取搜索关键字
		if(!empty(input('type')) && input('type') != ' '){
			$map['type'] = array("eq",input('type'));
		}
		if(!empty(input('day_time_begin')) && empty(input('day_time_end'))){
			$map['day_time'] = array('between time',[input('day_time_begin'),'2027-10-01']);
		}elseif(!empty(input('day_time_begin')) && !empty(input('day_time_end'))){
			$map['day_time'] = array('between time',[input('day_time_begin'),input('day_time_end')]);
		}else{
			$map['day_time'] = array('between time',[date('Y-m-d',time()),'2027-10-01']);
		}
		return $map;
	}
	/**
	 * @authority 每日菜谱列表
	 */
	public function getAllData(){
		$ku = model('Cookbook');
		$Model = model(request()->controller());
		$map = $this->loadSeachCondition();
		$total = $Model->where($map)->count();// 查询满足要求的总记录数
		$page = json_decode($this->pageParam($total));
		$data = $Model->where($map)->limit($page->firstRow,$page->listRows)->order($page->sort)->select();
		foreach ($data as $key=>$val){
			$data[$key]['cookname'] = $ku->where('id',$val['cookbook_id'])->value('name');
		}
		$vo = $this->toJosnForGird($data,$page);
		return $vo;
	}
	/**
	 * @authority 新增
	 */
	public function add(){
		//获取菜名和id
		$ku = model('Cookbook');
        $sid = session('school_info_id');
		$vo = $ku->where('school_id',$sid)->where('flag',1)->select();
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	/**
	 * @authority 修改
	 */
	public function edit(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		//获取菜名和id
		$ku = model('Cookbook');
		$cook = $ku->where('flag',1)->select();
		$this->assign('cook',$cook);
		$this->assign('vo',$vo);
		return $this->fetch();
	}
	/**
	 * @authority 比较开始日期和结束日期
	 */
	public function compareDate(){
		$begin = input('post.begin');
		$end = input('post.end');
		if(strtotime($begin) > strtotime($end)){
			return $this->ajaxReturn($begin,'开始时间大于结束时间',1);
		}
	}
}