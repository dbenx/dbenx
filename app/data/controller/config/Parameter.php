<?php
namespace app\data\controller\config;

use app\data\service\FeiYuService;
use app\data\model\DataConfig;
use think\admin\Controller;

/*
 * 参数配置
 * 匹配字符串与3++编码
 */
class Parameter extends  Controller
{
    /**
     * 参数配置
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public  function  index(){
        $this->title = '表单管理';
        $query = DataConfig::mQuery();
        $query->where(['deleted' => 0])->order('sort desc,id desc');
        $query->like('name,phone')->dateBetween('create_at')->page();
    }

    /**
     * 添加文章内容
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function add()
    {
        $this->title = '添加文章内容';
        DataConfig::mForm('form');
    }


    /**
     * 编辑文章内容
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function edit()
    {
        $this->title = '编辑文章内容';
        DataConfig::mForm('form');
    }


    /**
     * 修改文章标签状态
     * @auth true
     */
    public function state()
    {
        DataConfig::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除文章标签
     * @auth true
     */
    public function remove()
    {
        DataConfig::mDelete();
    }
}