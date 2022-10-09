<?php

namespace app\Datacenter\controller\dispose;

use app\Datacenter\model\DataSysChannel;
use app\Datacenter\model\DataSysMedia;
use app\Datacenter\model\DataSysMedium;
use think\admin\Controller;
use think\admin\model\SystemUser;

class medium extends Controller
{

    public function index()
    {
        $this->title = "媒介渠道设置";
        $this->GetSet();
        DataSysMedium::mQuery()->where(['deleted' => 0])->like('name')->equal('network,media,channel,status')->dateBetween('create_at')->order('sort desc')->page();
    }

    protected function _index_page_filter(&$data)
    {
        foreach (DataSysMedia::mk()->where(['deleted' => 0])->column('id,name') as $val) {
            $DataSysMedia[$val['id']] = $val['name'];
        }
        foreach (DataSysChannel::mk()->where(['deleted' => 0])->column('id,name') as $val) {
            $DataSysChannel[$val['id']] = $val['name'];
        }
        foreach (SystemUser::mk()->where(['is_deleted' => 0])->column('id,nickname') as $val) {
            $username[$val['id']] = $val['nickname'];
        }

        foreach ($data as &$vo) {
            $vo['media'] = $DataSysMedia[$vo['media']];
            $vo['channel'] = $DataSysChannel[$vo['channel']];
            $vo['userid'] = $username[$vo['userid']];
        }
    }

    /**
     * 添加媒介
     * @auth true
     * @menu  true
     * @auth true
     */
    public function add()
    {
        DataSysMedium::mForm('form');
    }

    /**
     * 编辑媒介
     * @auth true
     * @menu  true
     * @auth true
     */
    public function edit()
    {
        DataSysMedium::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $vo
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isGet()) {
            $this->GetSet();
        }
    }

    /**
     * 修改状态
     * @auth true
     * @menu  true
     * @auth true
     * H4017682910
     */
    public function state()
    {
        DataSysMedium::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
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
        DataSysMedium::mDelete();
    }

    /**
     * 获取 媒体，渠道，账户归属数据
     */
    protected function GetSet()
    {
        //媒体归属
        $this->media = DataSysMedia::mk()->where(['deleted' => 0])->column('id,name');
        //渠道归属
        $this->channel = DataSysChannel::mk()->where(['deleted' => 0])->column('id,name');
        //账户归属
        $this->username = SystemUser::mk()->where(['is_deleted' => 0])->column('id,nickname');
    }

}