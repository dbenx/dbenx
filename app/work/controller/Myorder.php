<?php



namespace app\work\controller;



use app\work\model\WordOrder;

use app\work\service\WordOrderService;

use think\admin\Controller;

use think\admin\helper\QueryHelper;

use think\admin\model\SystemUser;



/**

 * 个人工单中心

 * @auth true

 * @menu true

 */

class Myorder extends  Controller

{

    /**

     * 个人工单中心

     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问

     */

    public  function  index(){
        $this->title="收到工单";
        $this->type = input('get.type', 'index');
        WordOrder::mQuery()->layTable(function () {
            $this->zznames = WordOrderService::instance()->getUserData();
        }, function (QueryHelper $query) {

            if(   $this->type==='index'){
                $where = ['deleted' => 0,'zzname'=>session('user.id')];
            }else{
                $where = ['deleted' => 0,'tjname'=>session('user.id')];
            }
            $query->where($where)->dateBetween('create_at')->equal('status,zzname,tjname')->like('title');
        });
    }

    /**

     * 这里可以对 $data 进行二次处理，注意是引用

     * @param $data

     */

    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {

            try {

                $vo['tjname'] = SystemUser::mk()->where(['id' => $vo['tjname']])->column('nickname')[0];

                $vo['zzname'] = SystemUser::mk()->where(['id' => $vo['zzname']])->column('nickname')[0];

            } catch (\Exception $exception) {

            }
        }
    }



}