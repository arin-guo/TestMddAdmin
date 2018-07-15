<?php
namespace app\index\controller;

use think\Controller;
class Spa extends Controller
{
    public function getSpa(){
    	
        return $this->fetch('mddspa');
    }
}
