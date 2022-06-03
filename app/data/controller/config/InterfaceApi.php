<?php
namespace app\data\controller\config;
use app\data\model\DataConfigApi;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
class InterfaceApi extends  Controller
{
    /**
     * 接口参数配置
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */

    public function  index(){
          DataConfigApi::mQuery()->layTable(function () {
             $this->title = '接口参数配置';
          }, function (QueryHelper $query) {
            $query->where(['deleted' => 0])->equal('status')->like('name');
            $query->dateBetween('create_at');
        });
    }

    /**
     * 添加内容
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function add()
    {
        $this->title = '添加接口内容';
        DataConfigApi::mForm('form');
    }

    /**
     * 编辑内容
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function edit()
    {
        $this->title = '编辑接口内容';
        DataConfigApi::mForm('form');
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
        if ($result && $this->request->isPost()) {
            $this->success('接口编辑成功！', 'javascript:history.back()');
        }
    }


    /**
     * 修改标签状态
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function state()
    {
        DataConfigApi::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除标签
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function remove()
    {
        DataConfigApi::mDelete();
    }


    /**
     * 监控任务 自动拉去数据 此任务为多例任务
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public  function  queue(){
        if($this->app->request->param('id')){
            $rs=DataConfigApi::mk()->where(['id'=>$this->app->request->param('id')])->find()->toArray();
            if($rs['status']==1)$this->error('任务已经启动，不用重复启动。');
            $data=array('id'=>$rs['id']);
            DataConfigApi::mQuery()->where(['id'=>$rs['id']])->update(['status'=>1]);
            $this->_queue('定时获取接口【'.$rs['name'].'】的数据', "xdata:GetfeiyuData", 0,$data, 1, 600);
        }else{
            $this->error('传入的ID错误，任务启动失败');
        }
    }
}