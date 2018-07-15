<?php 

use think\Cache;
use think\Db;
//设置报错级别
error_reporting(E_ERROR);
/**
 * 获取自定义字段
 */
function getFields($field){
	if(Cache::get('c_field_'.$field)){
		$value = Cache::get('c_field_'.$field);
	}else{
		$where['flag'] = 1;
		$where['field'] = $field;
		$value = Db::name('fields')->where($where)->value('value');
		Cache::set('c_field_'.$field, $value,3600);
	}
	return $value;
}

/**
 * 格式化金钱
 * @param unknown $price
 */
function formatPrice($price){
	return sprintf("%.2f", $price);
}

/**
 * 写入日志
 * Enter description here ...
 * @param unknown_type $title
 * @param unknown_type $msg
 */
function writeLog($msg){
	$path = "./log/".date('Ym')."/";
	if(!file_exists($path)){
		//检查是否有该文件夹，如果没有就创建，并给予最高权限
		mkdir($path, 0777, true);
	}
	$file = fopen($path.Date('d').".log", "a+");
	$msg .= "\r\n";
	fwrite($file, $msg);
	fclose($file);
}

/**
 * 隐藏手机号中间四位
 */
function getHidePhone($tel){
	return substr($tel,0,3)." *** ".substr($tel,-4);
}

function create_uuid($prefix = ""){    //可以指定前缀
	$str = md5(uniqid(mt_rand(), true));
	$uuid  = substr($str,0,8) . '-';
	$uuid .= substr($str,8,4) . '-';
	$uuid .= substr($str,12,4) . '-';
	$uuid .= substr($str,16,4) . '-';
	$uuid .= substr($str,20,12);
	return $prefix . $uuid;
}


/**
 * 模拟post进行url请求
 * @param string $url
 * @param string $param
 */
function request_post($url = '', $param = array()) {
	if (empty($url) || empty($param)) {
		return false;
	}
	$o = "";
	foreach ( $param as $k => $v ){
		$o.= "$k=" . $v . "&" ;
	}
	$param = substr($o,0,-1);
	$postUrl = $url;
	$curlPost = $param;
	$ch = curl_init();//初始化curl
	//设置请求头
	curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
	curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
	curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
	$data = curl_exec($ch);//运行curl
	curl_close($ch);
	return $data;
}

/**
 * 短信息发送接口（相同内容群发，可自定义流水号）
 * @param unknown $to 接收手机号，多个号码间以逗号分隔且最大不超过1000个号码
 * @param unknown $text 发送内容,标准内容不能超过70个汉字
 */
function sendMsssage($to,$text){
	$url = 'http://api01.monyun.cn:7901/sms/v2/std/single_send';
	if(empty(config('app_send_msg_apikey'))){
		return false;
	}
	$postData['apikey'] = config('app_send_msg_apikey');//账号
	$postData['mobile'] = $to;
	$postData['content'] = urlencode(iconv("UTF-8","GBK",$text));//转gbk明文
	$res = request_post($url, $postData);
	if($res.result == 0){
		return true;
	}else{
		return false;
	}
}


/**
 * 获取天气
 */
function getWeather(){
	if(empty(config('app_xinzhi_weather_key'))){
		return false;
	}
    $key = config('app_xinzhi_weather_key');
    $ip = request()->ip() == '127.0.0.1' ?'hangzhou':request()->ip();
    $url = 'https://api.seniverse.com/v3/weather/now.json?key='.$key.'&location='.$ip.'&language=zh-Hans&unit=c';
    $data = file_get_contents($url);
    $data = json_decode($data,true);
    return $data;

}

/**
 * [移动端访问自动切换主题模板]
 * @return boolen [是否为手机访问]
 */
function ismobile() {
	// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
	if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
		return true;

	//此条摘自TPM智能切换模板引擎，判断是否为客户端
	if(isset ($_SERVER['HTTP_CLIENT']) &&'PhoneClient'==$_SERVER['HTTP_CLIENT'])
		return true;
	//如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
	if (isset ($_SERVER['HTTP_VIA']))
		//找不到为flase,否则为true
		return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
	//判断手机发送的客户端标志,兼容性有待提高
	if (isset ($_SERVER['HTTP_USER_AGENT'])) {
		$clientkeywords = array(
				'nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile'
		);
		//从HTTP_USER_AGENT中查找手机浏览器的关键字
		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return true;
		}
	}
	//协议法，因为有可能不准确，放到最后判断
	if (isset ($_SERVER['HTTP_ACCEPT'])) {
		// 如果只支持wml并且不支持html那一定是移动设备
		// 如果支持wml和html但是wml在html之前则是移动设备
		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
			return true;
		}
	}
	return false;
}
?>