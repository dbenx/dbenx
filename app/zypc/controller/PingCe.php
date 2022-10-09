<?php

namespace app\zypc\controller;

use app\zypc\model\PingceData;
use think\admin\Controller;

class PingCe extends Controller
{
    /*
     * 专业评测
     */
    public function index()
    {
        PingceData::mQuery()->page();
    }

    /*
     * 申请表
     */
    public function sqb()
    {
        var_dump($this->app->request->param());
        $this->fetch();
    }

    /*
     * 评测系统
     */
    public function pcxt()
    {
        $this->title='aaa';
        PingceData::mForm('pcxt');
    }

}