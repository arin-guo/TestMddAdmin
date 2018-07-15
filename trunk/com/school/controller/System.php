<?php
namespace app\school\controller;
use app\school\controller\Base;
use mail\Mail;
use think\Db;
class System extends Base{
	
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
			$path = config('app_upload_path')."/uploads/school/headimg/".date('Ymd')."/";
			if(!file_exists($path)){
				//检查是否有该文件夹，如果没有就创建，并给予最高权限
				mkdir($path, 0777, true);
			}
			$new_file = $path.time().".{$type}";
			if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $img)))){
				$file_path = ltrim($new_file,config('app_upload_path'));
				$result = Db::name('Member')->where('id',session('user_school_id'))->setField('headimg',$file_path);
				session('user_school_photo',$file_path);
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
}