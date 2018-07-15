<?php
namespace app\admin\behavior;

class CronRun{
	
	public function appEnd(){
		//记录日志
		writeAdminLog();
	}
	
}