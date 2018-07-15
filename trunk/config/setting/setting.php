<?php
use think\Request;
return [
		// 视图输出字符串内容替换
		'view_replace_str'       => [
				'__ROOT__' => '/',
				'__IMGROOT__' => 'http://test.upload.mengdd.net/',
				'__HPLUS__' => '/static/hplus',
				'__LAYUI__' => '/static/layui/src',
				'__TEANET__' => '/static/index',
		],
		//短信账号
		'app_send_msg_apikey'=>'',
		//心知天气
		'app_xinzhi_weather_key'=>'fd8piufqlaybvmkk',
		//上传图片的路径
		'app_upload_path' => '/var/www/mdd_test',//D://www//mdd
		//大华乐橙
		'app_lechange_url' => 'https://openapi.lechange.cn:443/openapi/',
		'app_lechange_app_id' => 'lc4c59936d2ee24ca0',
		'app_lechange_app_secret' => 'af70845046eb4d08bae695813bd584',
		'app_lechange_admin_user' =>'c45e6e002c254aaf',//管理员账户
];