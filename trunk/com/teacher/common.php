<?php

use think\Db;
use PHPExcel;
use PHPExcel_IOFactory;
/**
 * 记录行为日志
 */
function writeAdminLog($type = 0){
	//过滤登录类
	if(strtolower(request()->controller()) == 'login' && $type == 0){
		return true;
	}
	$MemberLog = Db::name('MemberLog');
	$uid = session('user_admin_id');$flag = 0;
	$data['user_id'] = $uid;
	$data['username'] = session('user_admin_username');
	$data['realname'] = session('user_admin_realname');
	$data['userip'] = request()->ip();
	$data['create_time'] = time();
	if($type == 1){
		$data['behavior'] = "登录系统";$flag = 1;
	}elseif($type == -1){
		$data['behavior'] = "退出系统";$flag = 1;
	}else{
		$actionList = session('admin_actionlog_list'.$uid);
		foreach ($actionList as $val){
			$moduleName = explode('/', $val['url']);
			if(request()->controller()  == $moduleName[0]){
				switch (request()->action()){
					case "index"     : $data['title'] = $val['title'];$data['url'] = request()->controller().'/'.request()->action();$data['behavior'] = "浏览列表";$flag = 1;break;
					case "insert"    : $data['title'] = $val['title'];$data['url'] = request()->controller().'/'.request()->action();$data['behavior'] = "新增数据";$flag = 1;break;
					case "update"    : $data['title'] = $val['title'];$data['url'] = request()->controller().'/'.request()->action();$data['behavior'] = "修改数据";$flag = 1;break;
					case "detailView": $data['title'] = $val['title'];$data['url'] = request()->controller().'/'.request()->action();$data['behavior'] = "查看详情";$flag = 1;break;
					case "del"       : $data['title'] = $val['title'];$data['url'] = request()->controller().'/'.request()->action();$data['behavior'] = "物理删除";$flag = 1;break;
					case "logicDel"  : $data['title'] = $val['title'];$data['url'] = request()->controller().'/'.request()->action();$data['behavior'] = "逻辑删除";$flag = 1;break;
					case "editSeq"   : $data['title'] = $val['title'];$data['url'] = request()->controller().'/'.request()->action();$data['behavior'] = "修改排序";$flag = 1;break;
					default ;
				}
			}
		}
	}
	if($flag){
		$result = $MemberLog->insert($data);
	}
}

/**
 * 导出excel
 * @param unknown $expTitle
 * @param unknown $expCellName
 * @param unknown $expTableData
 */
function exportExcel($expTitle,$expCellName,$expTableData){
	$xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
	$fileName = $expTitle.date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
	$cellNum = count($expCellName);
	$dataNum = count($expTableData);
	$objPHPExcel = new PHPExcel();
	$cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
	for($i=0;$i<$cellNum;$i++){
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i][1]);
	}
	$objPHPExcel->getActiveSheet()->getStyle($cellName[0].'1'.":".$cellName[$cellNum-1].'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
	$objPHPExcel->getActiveSheet()->getStyle($cellName[0].'1'.":".$cellName[$cellNum-1].'1')->getFill()->getStartColor()->setRGB('71b83d');
	for($i=0;$i<$dataNum;$i++){
		for($j=0;$j<$cellNum;$j++){
			$objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2), empty($expCellName[$j][0])?"":$expTableData[$i][$expCellName[$j][0]]);
		}
	}
	ob_end_clean();//用来清除缓冲区防止导出的excel乱码
	header('pragma:public');
	header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
	header("Content-Disposition:attachment;filename=$fileName.xls");
    header('Cache-Control: max-age=0');
	$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');//"Excel2007"生成2007版本的xlsx，"Excel5"生成2003版本的xls
	$objWriter->save('php://output');
	exit ;
}
