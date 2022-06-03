<?php
namespace app\admin\controller\parameter;
use think\admin\Controller;
use app\admin\model\SystemPlatform;


class Platform extends  controller
{
    /**
     * 接口平台配置
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function index(){
        $this->title = '接口平台管理';
        $query = SystemPlatform::mQuery();
        $query->where(['deleted' => 0])->order('sort desc,id desc');
        $query->like('name')->page();
    }

    /**
     * 添加系统权限
     * @auth true
     */
    public function add()
    {
        SystemPlatform::mForm('form');
    }

    /**
     * 编辑系统权限
     * @auth true
     */
    public function edit()
    {
        SystemPlatform::mForm('form');
    }

    /**
     * 修改权限状态
     * @auth true
     */
    public function state()
    {
        SystemPlatform::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除系统权限
     * @auth true
     */
    public function remove()
    {
        SystemPlatform::mDelete();
    }
}