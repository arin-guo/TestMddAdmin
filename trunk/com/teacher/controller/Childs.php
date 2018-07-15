<?php
namespace app\teacher\controller;
use app\teacher\controller\Base;
use think\Db;
/**
 * 学生控制器
 * @author guoqiang E-mail:guo14903@163.com 
 * @version 创建时间：2017年10月25日 上午10:16:19 
 * 类说明
 */
class Childs extends Base{
    //获取学校列表
    public function index(){
        //获取教师的班级id
        $tid = session('user_teacher_id');
        $cid=Db::view('teacher_class')
            ->where('teacher_id',$tid)
            ->where('flag',1)
            ->where('teacher_type',1)
            ->value('classes_id');
        $cidr=Db::view('teacher_class')
            ->where('teacher_id',$tid)
            ->where('flag',1)
            ->where('teacher_type',3)
            ->value('classes_id');
        if(empty($cid) && empty($cidr)){
            return $this->err('您还未被分配班级，请联系园长');
        }else{
            if(empty($cid)){
                $cid = $cidr;
            }
        }
        $this->assign('cid',$cid);
        return $this->fetch();
    }
    /**
	 * 搜索组装条件
	 * @return multitype:multitype:string
	 */
	protected function loadSeachCondition(){
		$map = array();
		$map['flag'] = 1;
		$map['school_id'] = session('school_info_id');
		$map['classes_id']=input('classes_id');
		//获取搜索关键字
		if(!empty(input('realname'))){
			$map['realname'] = array("like","%".input('realname')."%");
		}
		if(!empty(input('code'))){
			$map['code'] = array("like","%".input('code')."%");
		}
		if(!empty(input('status')) && input('status') != ' '){
			$map['status'] = array("eq",input('status'));
		}else{
			$map['status'] = 1;
		}
		return $map;
	}
	/**
	 * @authority 家长信息页
	 */
	public function parentInfo() {
		$id = request()->param('id');
		$vo = Db::view('parent_child','relation')
		->view('parents','realname,tel,id_card,type,sex,address','parents.id = parent_child.parent_id')
		->where('child_id',$id)
        ->where('parents.flag',1)
		->order('type asc')
		->select();
		$this->assign('vo',$vo);
		return $this->fetch('detailParent');
	}
	/**
	 * @authority 详细页
	 */
	public function detailView() {
		$Model = model(request()->controller());
		$pk = $Model->getPk();
		$vo = $Model::get(request()->param($pk));
		//查询年级名和班级名
		$classid=$vo['classes_id'];
		$classinfo = Db::view('childs','classes_id')
		->view('classes',['id'=>'cid','name'=>'class_name'],'classes.id=childs.classes_id')
		->view('subtype',['id'=>'sid','subtype_name'],'subtype.subtype_code=classes.cats_code')
		->where('childs.classes_id','eq',$classid)
		->find();
		if($classinfo){
			$vo['classname'] = $classinfo['subtype_name'].'-'.$classinfo['class_name'];
		}
		$this->assign('vo',$vo);
		return $this->fetch();
	}
    /**
     * 更改学号
     */
    public function updateChildCode() {
        $Childs = model('Childs');
        if(empty(input('id'))){
            return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
        }
        if(empty(input('code'))){
            return $this->ajaxReturn(0,"学号为空！",0);
        }
        //判断学号是否重复
        $map['code'] = input('code');
        $map['school_id'] = session('school_info_id');
        $map['id'] = array('neq',input('id'));
        $map['flag'] = 1;
        $count = $Childs->where($map)->count();
        if($count != 0){
            return $this->ajaxReturn(0,'学号已重复！',0);
        }
        if (false !== $Childs->where('id',input('id'))->setField('code',input('code'))) {
            return $this->ajaxReturn(1,lang('ADMIN_EDIT_SUCCESS'),1);
        } else {
            return $this->ajaxReturn(0,lang('ADMIN_EDIT_ERROR'),0);
        }
    }
	/**
	 * 上传图片类
	 * @return multitype:multitype:string
	 */
	public function uploadImg(){
		$file = request()->file('image');
		$valid['size'] = 2097152;//2M
		$valid['ext'] = 'jpg,png,gif';
		$path = config('app_upload_path').'/uploads/child/headimg/';
		$info = $file->validate($valid)->rule('date')->move($path);
		if($info){
			$file_path = ltrim($path.$info->getSaveName(),config('app_upload_path'));
			return $this->ajaxReturn($file_path,'上传成功！',1);
		}else{
			return $this->ajaxReturn(0,$file->getError(),0);
		}
	}
    /**
     * 导出excel视图页面
     * @return multitype:multitype:string
     */
    public function export(){
        $cid=input('classes_id');
        $this->assign('cid',$cid);
        return $this->fetch();
    }
    /**
     * 导出excel
     */
    public function exportExcel1(){
        $xlsName  = "学生列表";
        $xlsCell  = array(
            array('id','ID'),
            array('unique_code','识别码'),
            array('realname','学生姓名'),
            array('en_name','英文名'),
            array('code','学号'),
            array('id_card','身份证'),
            array('sex','性别'),
            array('age','年龄'),
            array('birthday','生日'),
            array('household','地址'),
            array('ethnicity','民族'),
            array('hobby','爱好'),
            array('body_situation','身体特殊情况'),
            array('allergy_situation','过敏情况'),
            array('status','状态'),
            array('remark','备注'),
            array('create_time','创建时间')
        );
        $xlsModel = model('childs');
        $map['flag'] = 1;
        $map['school_id'] = session('school_info_id');
        $map['classes_id'] = input('classes_id');
        if(!empty(input('status'))){
            $map['status'] = input('status');
        }
        $xlsData  = $xlsModel->where($map)->order('create_time desc')->field('id,realname,unique_code,en_name,code,id_card,sex,age,birthday,household,ethnicity,hobby,body_situation,allergy_situation,status,remark,create_time')->select();
        foreach ($xlsData as $key=>$val){
            $xlsData[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
            switch ($val['sex']) {
                case 1:$xlsData[$key]['sex'] = '男';break;
                case 2:$xlsData[$key]['sex'] = '女';break;
            }
            switch ($val['status']) {
                case 1:$xlsData[$key]['status'] = '正常';break;
                case -1:$xlsData[$key]['status'] = '毕业';break;
                case -2:$xlsData[$key]['status'] = '转学';break;
            }
        }
        exportExcel($xlsName,$xlsCell,$xlsData);
    }
}