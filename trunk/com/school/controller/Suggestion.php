<?php
namespace app\school\controller;
use app\school\controller\Base;
use think\Db;
/**
 * 意见反馈
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年8月7日 下午4:13:11 
 * 类说明
 */
class Suggestion extends Base{
	/**
	 * 组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		if(!empty(input('tel'))){
			$map['tel'] = array("like","%".input('tel')."%");
		}
        if(!empty(input('type')) && input('type') != ' '){
            $map['type'] = array("eq",input('type'));
        }
		return $map;
	}
    /**
     * 获取列表
     * @return multitype:multitype:string
     */
    public function getAllData(){
        $Model = model(request()->controller());
        $map = $this->loadSeachCondition();
        $total = $Model->where($map)->count();// 查询满足要求的总记录数
        $page = json_decode($this->pageParam($total));
        $data = $Model->where($map)->limit($page->firstRow,$page->listRows)->order($page->sort)->select();
        foreach($data as $key=>$val){
            if($val['user_id']){
                if($val['type'] == 1){
                    $tel = Db::view('parents')->where('id',$val['user_id'])->value('tel');
                    $data[$key]['phone'] = $tel;
                }elseif($val['type'] == 2){
                    $tel = Db::view('teachers')->where('id',$val['user_id'])->value('tel');
                    $data[$key]['phone'] = $tel;
                }
            }
        }
        $vo = $this->toJosnForGird($data,$page);
        return $vo;
    }
}