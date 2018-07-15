<?php
namespace app\school\behavior;

class CronRun{
	
	public function appEnd(){
		//记录日志
		writeSchoolLog();
	}
	
}