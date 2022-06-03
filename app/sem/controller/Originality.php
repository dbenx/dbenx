<?php

namespace app\sem\controller;

use think\admin\Controller;

/**
 * 百度创意生成工具
 */
class Originality extends Controller
{
    public function index()
    {
        $this->title = '创意管理';
        $this->fetch();
    }


}