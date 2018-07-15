<?php
namespace app\index\controller;

use think\Controller;
class Agreement extends Controller
{
    public function getAgreement(){
    	return $this->fetch('agreement');
    }
    public function getPrivacyPolicy(){
        return $this->fetch('privacyPolicy');
    }
}
