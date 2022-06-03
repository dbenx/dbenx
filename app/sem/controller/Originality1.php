<?php

namespace app\sem\controller;

use think\admin\Controller;

/**
 * 百度创意生成工具
 */
class Originality1 extends Controller
{
    public function index()
    {
        $this->title = '创意生成工具';
        $this->keyword1 = str_replace("-", PHP_EOL, cookie('keyword1'));
        $this->keyword2 = str_replace("-", PHP_EOL, cookie('keyword2'));
        $this->keyword3 = str_replace("-", PHP_EOL, cookie('keyword3'));
        $this->ppkw = str_replace("-", PHP_EOL, cookie('ppkw'));
        $this->ms01= str_replace("-", PHP_EOL, cookie('ms01'));
        $this->ms02 = str_replace("-", PHP_EOL, cookie('ms02'));
        $this->msall = str_replace("-", PHP_EOL, cookie('msall'));
        $this->fetch();
    }

    public function  cs(){
        $this->fetch();
    }

}