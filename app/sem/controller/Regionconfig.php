<?php

namespace app\sem\controller;

use app\sem\model\SemRegionConfig;
use app\sem\model\SemUnitConfig;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

class Regionconfig extends  Controller
{
    public  function index(){

        SemRegionConfig::mQuery()->layTable(function () {
            $this->title = '地域词管理';
        }, function (QueryHelper $query) {
            $query->whereRaw('uid = ' . session('user.id') . '  OR status = 1');
        });
    }

    /**
     * 添加地域权限
     * @auth true
     */
    public function add()
    {
        SemRegionConfig::mForm('form');
    }

    /**
     * 编辑地域权限
     * @auth true
     */
    public function edit()
    {
        $this->_auth(input('id'));
        SemRegionConfig::mForm('form');
    }
    /**
     * 表单数据处理
     * @param array $vo
     * @throws \ReflectionException
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isPost()) {
            $vo['regionalwords']=str_replace(' ','',$vo['regionalwords']);
        }
    }
    /**
     * 修改状态
     * @auth true
     */
    public function state()
    {
        $this->_auth(input('id'));
        SemRegionConfig::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除规则
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function remove()
    {
        $this->_auth(input('id'));
        SemRegionConfig::mDelete();
    }

    protected function _auth($id)
    {
        $uid = isset(SemRegionConfig::mk()->where(['id' => $id])->column('uid')[0]) ? SemRegionConfig::mk()->where(['id' => $id])->column('uid')[0] : false;
        if ($uid) {
            if ($uid != session('user.id')) $this->error('禁止操作！', 'javascript:history.go(0)');
        } else {
            $this->error('数据错误');
        }
    }

}