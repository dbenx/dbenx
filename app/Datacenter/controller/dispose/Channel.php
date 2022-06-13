<?php

namespace app\Datacenter\controller\dispose;

use app\Datacenter\model\DataSysChannel;
use think\admin\Controller;

/*渠道归属*/

class Channel extends Controller
{
    /**
     * 渠道归属设置
     * @auth true
     * @menu  true
     * @auth true
     */
    public function index()
    {
        $this->title="渠道归属设置";
        DataSysChannel::mQuery(['deleted' => 0])->order('sort desc')->page();
    }
    /**
     * 添加信息
     * @auth true
     * @menu  true
     * @auth true
     */
    public function add()
    {
        DataSysChannel::mForm('form');
    }
    /**
     * 编辑信息
     * @auth true
     * @menu  true
     * @auth true
     */
    public function edit()
    {
        DataSysChannel::mForm('form');
    }
    /**
     * 删除信息
     * @auth true
     * @menu  true
     * @auth true
     */
    public function remove()
    {
        DataSysChannel::mDelete();
    }
}