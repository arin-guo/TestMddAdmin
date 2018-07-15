<?php
namespace app\index\controller;

use think\Controller;
class News extends Controller
{
    public function getNewInfo(){
    	$News = model('News');
    	if(empty(input('id'))){
    		return $this->error("未找到该页面！");
    	}
    	$info = $News->where('flag',1)->where('id',input('id'))->find();
    	if(empty($info)){
    		return $this->error("未找到该页面！");
    	}
    	//访问数+1
    	$News->where('flag',1)->where('id',input('id'))->setInc('visit_num',1);
    	$this->assign('vo',$info);
    	return $this->fetch('new');
    }
}
