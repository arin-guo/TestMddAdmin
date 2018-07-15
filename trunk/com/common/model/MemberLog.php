<?php
namespace app\common\model;
use think\Model;
use app\common\model\Base;
/**
 * 行为日志
 * @author chenlisong E-mail:chenlisong1021@163.com 
 * @version 创建时间：2017年3月20日 下午1:28:34 
 * 类说明
 */
class MemberLog extends Base{
	//自动写入时间戳
	protected $autoWriteTimestamp = false;
	protected $createTime = false;
	protected $updateTime = false;
}