<?php

namespace app\sem\controller;

use app\sem\model\SemUrlsuffixConfig;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
/**
 * URL 后辍设置
 * @auth true  # 表示需要验证权限
 * @menu true  # 添加系统菜单节点
 * @login true # 强制登录才可访问
 */
class Urlsuffix extends Controller
{
    /**
     * URL后辍管理
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public  function index(){
        SemUrlsuffixConfig::mQuery()->layTable(function () {
            $this->title = 'URL后辍管理';
        }, function (QueryHelper $query) {
            $query->whereRaw('uid = ' . session('user.id') . '  OR status = 1');
            $query->dateBetween('create_at')->like('title')->equal('status,id');
        });
    }

    public  function  add(){
        SemUrlsuffixConfig::mForm('form');
    }
    public  function  edit(){
        SemUrlsuffixConfig::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $vo
     * @throws \ReflectionException
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isGet()) {
            if(isset($vo['content'])){
                $vo['content']=str_replace("&", PHP_EOL,$vo['content']);
            }
        }else{
            $vo['content']=str_replace(PHP_EOL, '&',$vo['content']);
        }
    }
    /**
     * 表单结果处理
     * @param boolean $result
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _form_result(bool $result)
    {
        if ($result) {
            $this->success('数据更新成功！', 'javascript:history.back()');
        }
    }
    /**
     * 删除商品数据
     * @auth true
     */
    public function remove()
    {
        SemUrlsuffixConfig::mDelete();
    }

    /**
     * 修改规则状态
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function state()
    {
        //$this->_auth(input('id'));
        SemUrlsuffixConfig::mQuery()->whereOr(['id' => input('id')])->update([
            'status' => input('status')
        ]);
        $this->success('更新数据成功！');
    }

}