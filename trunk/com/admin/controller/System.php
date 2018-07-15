<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use mail\Mail;
use think\Db;
class System extends Base{
	
	/**
	 * 发送邮件
	 */
	public function sendmail(){
		return $this->fetch();
	}
	
	public function mail(){
		$mail = new Mail();
		$result = $mail->send_email(input('toemail'),input('nickname'),input('title'),input('html'));
		if($result['status'] == true){
			return $this->ajaxReturn(1,'发送成功！',1);
		}else{
			return $this->ajaxReturn(0,$result['errorMsg'],0);
		}
	}
	
	/**
	 * 系统日志
	 */
	public function log(){
		return $this->fetch();
	}
	
	/**
	 * 获取文件列表
	 */
	public function getFileList(){
		if (!in_array(input('type'), array(1,2)) || empty(input('month'))){
			return $this->ajaxReturn(0,'参数错误！',0);
		}
		$dir = input('type') == 1?RUNTIME_PATH:".";
		$dir .="/log/".input('month');
		$data['path'] = $dir;
		if(!file_exists($dir)){
			$data['file'] = [];
			return $this->ajaxReturn($data,'该月不存在日志！',1);
		}
		$handler = opendir($dir);
		while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
			if ($filename != "." && $filename != ".."){
				$data['file'][] = $filename ;
			}
		}
		ksort($data['file']);
		closedir($handler);
		return $this->ajaxReturn($data,'请求成功！',1);
	}
	
	/**
	 * 读取文件内容
	 */
	public function getFileContent(){
		if (empty(input('path'))){
			return $this->ajaxReturn(0,'参数错误！',0);
		}
		$file_path = input('path');
		if(file_exists($file_path)){
			$data = file_get_contents($file_path);//将整个文件内容读入到一个字符串中
			$data = str_replace("\r\n","<br />",$data);
			return $this->ajaxReturn($data,'读取成功！',1);
		}else{
			return $this->ajaxReturn(0,'文件不存在！',0);
		}
	}
	
	public function clearFileContent(){
		if (empty(input('path'))){
			return $this->ajaxReturn(0,'参数错误！',0);
		}
		$file_path = input('path');
		if(file_exists($file_path)){
			$myfile = fopen($file_path, "w");
			fwrite($myfile,"");
			fclose($myfile);
			return $this->ajaxReturn('','清除成功！',1);
		}else{
			return $this->ajaxReturn(0,'文件不存在！',0);
		}
	}
	
	/**
	 * 保存头像
	 */
	public function uploadImgHead(){
		$img = input('post.imgPath');
		if(empty($img)){
			return $this->ajaxReturn(0,'上传失败！',0);
		}
		//匹配出图片的格式
		if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)){
			$type = $result[2];
			$path = config('app_upload_path')."/uploads/admin/headimg/".date('Ymd')."/";
			if(!file_exists($path)){
				//检查是否有该文件夹，如果没有就创建，并给予最高权限
				mkdir($path, 0777, true);
			}
			$new_file = $path.time().".{$type}";
			if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $img)))){
				$file_path = ltrim($new_file,config('app_upload_path'));
				$result = Db::name('Member')->where('id',session('user_admin_id'))->setField('headimg',$file_path);
				session('user_admin_headimg',$file_path);
				if($result !== false){
					return $this->ajaxReturn($file_path,'上传成功！',1);
				}else{
					return $this->ajaxReturn(0,'服务器繁忙！',0);
				}
			}else{
				return $this->ajaxReturn(0,'上传失败！',0);
			}
		}
	}
	
	/**
	 * 编辑器跨域上传图片，并拼接跨域图片头
	 */
	public function uploadImgEditor(){
		$file = request()->file('upfile');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		//路径规则为config.'/uploads/相应的模块/作用（如head_img）
		$path = config('app_upload_path').'/uploads/editor/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path.$info->getSaveName(),config('app_upload_path'));
			$data['state'] = 'SUCCESS';
			$data['url'] = $file_path;
			return json_encode($data);
		}else{
			$data['state'] = 'ERROR';
			return json_encode($data);
		}
	}
	
	/**
	 * 清除缓存
	 */
	public function delRuntime() {
		$dirs = ROOT_PATH.'/runtime/cache/';
		$this->delDirAndFile ( $dirs );
		$this->redirect('@admin');
	}
	
	/**
	 * 递归删除文件夹
	 * @param 文件路径
	 */
	protected function delDirAndFile($dirName) {
		if ($handle = opendir($dirName)){
			while(false !== ($item = readdir($handle))){
				if($item != "." && $item != ".."){
					if(is_dir($dirName."/".$item)){
						$this->delDirAndFile($dirName."/".$item);
					}else{
						unlink($dirName."/".$item);
					}
				}
			}
			closedir($handle);
			rmdir($dirName);
		}
	}
}