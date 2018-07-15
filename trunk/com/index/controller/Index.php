<?php
namespace app\index\controller;

use think\Controller;
use lechange\Lechange;
class Index extends Controller
{
    public function index()
    {
//     	$leChange = new Lechange();
//     	$reback = $leChange->getRootAccessToken();
//     	$token = 'At_1924df8640304e2f9fe42fe49f9d721f';
//     	$deviceId = '3H069B1PAZFE4FE';
//     	switch (input('type')){
//     		case 1://绑定设备
//     			$code = '';
//     			$reback = $leChange->bindDevice($token, $deviceId, $code);
//     			break;
//     		case 2://解绑设备
//     			$reback = $leChange->unBindDevice($token, $deviceId);
//     			break;
//     		case 3://获取单个设备信息
//     			$reback = $leChange->bindDeviceInfo($token, $deviceId);
//     			break;
//     		case 4://创建直播地址
//     			$reback = $leChange->bindDeviceLive($token, $deviceId, 0);
//     			break;
//     	}
//     	dump($reback);
    	return 'hi..';
    }
}
