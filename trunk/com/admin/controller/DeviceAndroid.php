<?php
namespace app\admin\controller;
use app\admin\controller\Base;
/**
 * 柜机版本
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年9月12日 上午10:16:19 
 * 类说明
 */
class DeviceAndroid extends Base{
	
	/**
	 * 组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		//搜索条件
		if(!empty(input('version'))){
			$map['version'] = array("like","%".input('version')."%");
		}
        if(!empty(input('status')) && input('status') != ' '){
            $map['status'] = input('status');
        }
		return $map;
	}
	/**
	 * @authority 新增
	 */
	public function add(){
		$fileList = $this->getFileList();
        $user = model('schools');
        $where['flag'] = 1;
        $school = $user->field('id,name')->where($where)->select();
		$this->assign('file',$fileList);
		$this->assign('schoolinfo',$school);
		return $this->fetch();
	}
	
	// 添加新版本
	public function insert(){
		$Model = model(request()->controller());
		$data = request()->post();
		//exit(print_r($data));
        $result = $Model->allowField(true)->save($data);
        if($result){
            if($data['range'] == 2){
                $vid = $Model->id;
                $deviceschool = model('DeviceSchool');
                foreach ($data['school_id'] as $key=>$val){
                    $list[$key]['version_id'] = $vid;
                    $list[$key]['school_id'] = $val;
                    $list[$key]['status'] = 1;
                }
                $deviceschool->saveAll($list);
                return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
            }elseif ($data['range'] == 1){
                return $this->ajaxReturn(1,lang('ADMIN_ADD_SUCCESS'),1);
            }
        }else{
            return $this->ajaxReturn(0,lang('ADMIN_ADD_ERROR'),0);
        }
	}
    /**
     * @authority 修改版本状态
     */
    public function changeStatus(){
        $Model = model(request()->controller());
        $data = request()->post();
        $Model->allowField(true)->save(['status'=>$data['status']],['id'=>$data['id']]);
        if ($data['range'] == 2){
            $deviceschool = model('DeviceSchool');
            $deviceschool->allowField(true)->save(['status'=>$data['status']],['version_id'=>$data['id']]);
        }
        return $this->ajaxReturn(1,'操作成功',1);
    }
    /**
     * @authority 删除
     */
    public function logicDel(){
        $Model = model(request()->controller());
        $data = request()->post();
        $Model->allowField(true)->save(['status'=>$data['status']],['id'=>$data['id']]);
        if ($data['range'] == 2){
            $deviceschool = model('DeviceSchool');
            $deviceschool->allowField(true)->save(['status'=>$data['status']],['version_id'=>$data['id']]);
        }
        return $this->ajaxReturn(1,'删除成功',1);
    }
	/**
	 * @authority 修改
	 */
	public function edit(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		$fileList = $this->getFileList();
		$this->assign('vo',$vo);
		$this->assign('file',$fileList);
		return $this->fetch();
	}
	
	/**
	 * @authority 修改方法
	 */
	public function update(){
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$data = request()->post();
		if($data['type'] == 2){
			$data['url'] = "";
		}
		$where[$pk] = $data[$pk];
		$result = $Model->allowField(true)->save($data,$where);
		if($result !== false){
			return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
		}else{
			return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
		}
	}
	
	/**
	 * 获取文件列表
	 */
	public function getFileList(){
		$dir = config('app_upload_path').'/uploads/static/deviceApp/';
		$data['dir'] = substr($dir,strlen(config('app_upload_path')),strlen($dir)-strlen(config('app_upload_path')));
		if(!file_exists($dir)){
			$data['filename'] = [];
			return $data;
		}
		$handler = opendir($dir);
		while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
			if ($filename != "." && $filename != ".."){
				$nameArray = explode(".", $filename);
				if($nameArray[count($nameArray) - 1] == "apk"){
					$data['filename'][] = $filename;
				}

			}
		}
		ksort($data);
		closedir($handler);
		return $data;
	}
}