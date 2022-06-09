<?php

namespace app\Datacenter\controller\dispose;

use app\Datacenter\model\DataSysMedia;
use think\admin\Controller;

/**
 * 媒体设置
 */
class media extends Controller
{
    /**
     * 媒体归属设置
     * @auth true
     * @menu  true
     * @auth true
     */
    public function index()
    {
        $this->title='媒体归属设置';
        DataSysMedia::mQuery()->where(['deleted'=>0])->page();
    }

    /**
     * 添加媒体
     * @auth true
     * @menu  true
     * @auth true
     */
    public function add(){
        DataSysMedia::mForm('form');
    }

    /**
     * 编辑媒体
     * @auth true
     * @menu  true
     * @auth true
     */
    public  function edit(){
        DataSysMedia::mForm('form');
    }

    /**
     * 修改状态
     * @auth true
     * @menu  true
     * @auth true
     */
    public function state()
    {
        DataSysMedia::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除信息
     * @auth true
     * @menu  true
     * @auth true
     */
    public function remove()
    {
        DataSysMedia::mDelete();
    }


}