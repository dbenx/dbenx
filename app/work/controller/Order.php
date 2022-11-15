<?php

namespace app\work\controller;
use app\work\model\WordOrder;
use app\work\service\WordOrderService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\model\SystemUser;

/**
 * 工单列表
 * @auth true  # 表示需要验证权限
 * @menu true  # 添加系统菜单节点
 * @login true # 强制登录才可访问
 */
class Order extends Controller

{

    /**
     * 工单列表
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */

    public function index()
    {

        WordOrder::mQuery()->layTable(function () {
            $this->now = date('Y-m-d h:i:s', time());
            $this->title = '工单列表';
            $this->zznames = WordOrderService::instance()->getUserData();
            $this->allnum = WordOrder::mk()->where(['deleted' => 0])->field('id')->count();
            $this->wwc = WordOrder::mk()->where(['deleted' => 0, 'finishtime' => Null])->field('id')->count();
            $this->ysgd = WordOrder::mk()->where(['deleted' => 0, 'finishtime' => Null])->where('endtime', '<', $this->now)->field('id')->count();

            $this->uid = WordOrder::mk()->where('finishtime', '<>', Null)->where(['deleted' => 0])->field('zzname,count(*) as num')->group('zzname')->order('num desc')->find()['zzname'];
            if (isset($this->uid)) {
                # $this->uid= SystemUser::mk()->where(['id' =>10002])->column('nickname')[0];
            }
        }, function (QueryHelper $query) {
            $query->where(['deleted' => 0])->dateBetween('create_at')->equal('status,zzname,tjname')->like('title');
        });

    }


    /**
     * 这里可以对 $data 进行二次处理，注意是引用
     * @param $data
     */

    protected function _index_page_filter(&$data)

    {

        if ($this->request->isGet()) {

            $this->zznames = WordOrderService::instance()->getUserData();

            foreach ($data as &$vo) {

                try {
                    $vo['tjname'] = SystemUser::mk()->where(['id' => $vo['tjname']])->column('nickname')[0];
                    $vo['zzname'] = SystemUser::mk()->where(['id' => $vo['zzname']])->column('nickname')[0];
                    $vo['dbenx'] = date('Y-m-d h:i:s', time());
                } catch (\Exception $exception) {

                }

            }

        }


    }


    /**
     * 添加工单
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */

    public function add()
    {
        WordOrder::mForm('form');
    }


    /**
     * 编辑工单
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */

    public function edit()

    {

        WordOrder::mForm('form');

    }


    /**
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     * @param boolean $state
     */

    protected function _form_result(bool $state)

    {

        if ($state) {

            $this->success('内容保存成功！', 'javascript:history.back()');

        }

    }


    /**
     * 修改通知状态
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */

    public function state()

    {

        WordOrder::mSave($this->_vali([

            'status.in:0,1' => '状态值范围异常！',

            'status.require' => '状态值不能为空！',

        ]));

    }


    /**
     * 删除工单
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */

    public function remove()

    {

        WordOrder::mDelete();

    }


    /**
     * 查看详情
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */

    public function view()
    {


        WordOrder::mForm('view');


    }

    /**
     * 提交见证性材料
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */

    public function send()
    {

        WordOrder::mForm('sendcl');

    }


    /**
     * 提交见证性材料,更新工单状态为完成
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */

    public function sendup()
    {

        if (session('user.id') != $this->request->param('zzname')) {

            $this->error('非制作人，不能提交材料和结束工单');

        }

        WordOrder::mk()->where(['id' => $this->request->param('id')])->update([

            'scontent' => $this->request->param('scontent'),

            'status' => 1,

            'finishtime' => date('Y-m-d h:i:s', time())

        ]);

        $this->success('内容保存成功！', 'javascript:history.back()');

    }

    /**
     * 转发工单
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function forward()
    {
        WordOrder::mForm('forward');
    }


    protected function _form_filter(array &$data)

    {

        if ($this->request->isGet()) {

            $this->zznames = WordOrderService::instance()->getUserData();

        } else {

            if (empty($data['zzname'])) $this->error('请选择制作人！');

            if (empty($data['module'])) $this->error('请选择物料模块！');

            if ($data['level'] == '') $this->error('请选择紧急程度！');

        }

    }


    protected function _view_form_filter(array &$data)

    {

        if ($this->app->request->isGet()) {

            if (session('user.id') != $data['zzname']) {

                $data['view'] = 'view';

            }

            if (WordOrder::mk()->where(['id' => $data['id']])->column('status')[0]) {

                $day = round((strtotime($data['create_at']) - strtotime($data['finishtime'])) / 3600 / 24) + 1;

                $data['day'] = ' 工单已经结束，用时' . $day . '天';

            } else {

                $day = round((strtotime($data['endtime']) - strtotime('now')) / 3600 / 24) + 1;

                $data['day'] = ' 距离结束日期还剩下' . $day . '天';

            }

            try {

                $data['zzname'] = SystemUser::mk()->where(['id' => $data['zzname']])->column('nickname')[0];

                $data['tjname'] = SystemUser::mk()->where(['id' => $data['tjname']])->column('nickname')[0];

            } catch (\Exception $exception) {

            }
        }
    }

    /**
     * 工单统计
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function portal()
    {
        foreach (SystemUser::mk()->column('id,nickname') as $val) {
            $username[$val['id']] =$val['nickname'];
        }
       // var_dump($username);
        $this->allorder = WordOrder::mk()->where(['deleted' => 0])->count('*');
        $this->endorder = WordOrder::mk()->where(['deleted' => 0,'status' => 1])->count('*');
        $this->nendorder=$this->allorder- $this->endorder;
        $this->now = date('Y-m-d h:i:s', time());
        $this->csorder = WordOrder::mk()->where(['deleted' => 0, 'finishtime' => Null])->where('endtime', '<', $this->now)->field('id')->count();

        $this->tjname=WordOrder::mk()->field('count(id) as value,tjname as name')->group('tjname')->select()->toArray();

        $this->zzname=WordOrder::mk()->field('count(id) as value,zzname as name')->group('zzname')->select()->toArray();
        $this->wwcname=WordOrder::mk()->field('count(id) as value,zzname as name')->where(['status'=>1])->group('zzname')->select()->toArray();

        foreach ($this->tjname as $key=>$value){
            $this->tjname[$key]['name']=$username[$value['name']];
        }
        foreach ($this->zzname as $key=>$value){
             $this->zzname[$key]['name']=$username[$value['name']];
        }

        for ($i = 30; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i}days"));
            $this->days[] = [
                '当天日期' => date('m-d', strtotime("-{$i}days")),
                '新提交工单' => WordOrder::mk()->whereLike('create_at', "{$date}%")->where(['deleted'=>0])->count(),
                '已完成工单' => WordOrder::mk()->whereLike('finishtime', "{$date}%")->where(['deleted'=>0,'status'=>1])->count(),
            ];
        }
        $this->fetch();
    }


}

