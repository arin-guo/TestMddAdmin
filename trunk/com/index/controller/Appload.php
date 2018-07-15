<?php
namespace app\index\controller;

use think\Controller;
class Appload extends Controller
{
    public function getAppload(){
    	
        return $this->fetch('appload');
    }
}
