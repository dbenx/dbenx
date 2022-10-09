<?php

namespace app\work\controller;

use app\work\model\WordBaiduurl;
use app\work\model\WordBaiduzd;
use app\work\service\BaiduService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\model\SystemUser;

/**
 * 百度知道
 */
class Baiduzd extends Controller
{
    /**
     * 我的知道，只自己可见
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function index()
    {
        $this->type = input('get.type', 'index');

        // 创建快捷查询工具
        WordBaiduzd::mQuery()->layTable(function () {
            $this->title = '百度知道';
        }, function (QueryHelper $query) {
            // 加载对应数据列表
            if ($this->type === 'index') {
                $this->count = 100;
                $query->where('status', '<>', '0');
                $query->where(['is_deleted' => 0, 'uid' => session('user.id')]);
            } elseif ($this->type === 'recycle') {
                $query->where(['is_deleted' => 0, 'status' => 0, 'uid' => session('user.id')]);
            } elseif ($this->type === 'noanswer') {
                $query->where('status', '<>', '0');
                $query->where(['is_deleted' => 0, 'username' => '', 'uid' => session('user.id')]);
            } elseif ($this->type === 'handin') {
                $query->where(['is_deleted' => 0, 'status' => 2, 'uid' => session('user.id')]);
            } elseif ($this->type === 'yanswer') {
                $query->where(['is_deleted' => 0, 'uid' => session('user.id')]);
                $query->where('username', '<>', '');
                $query->where('status', '<>', 2);
            }
            // 数据列表搜索过滤
            $query->dateBetween('create_at')->equal('status,robot')->like('title,username,url');
        });
    }

    /**
     * 知道列表 所有人可见
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function zhidaolist()
    {
        WordBaiduzd::mQuery()->layTable(function () {
            $this->title = '百度知道';

        }, function (QueryHelper $query) {
            $query->where('status', '<>', '0');
            $query->where(['is_deleted' => 0]);
            // 数据列表搜索过滤
            $query->dateBetween('create_at')->equal('status')->like('title,username,url');
        });
    }

    protected function _zhidaolist_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['uid'] = SystemUser::mk()->where(['id' => $vo['uid']])->column('nickname')[0];
            $vo['huida'] = $vo['url'] . '?fr=iks&word=' . rawurldecode($vo['title']) . '&ie=utf-8';

        }
    }

    /**
     * 修改链接，移动到回收站
     * @auth true
     */
    public function state()
    {
        WordBaiduzd::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['asklist'] = $vo['content'];
            $vo['huida'] = $vo['url'] . '?fr=iks&word=' . $vo['title'] . '&ie=utf-8';
        }
    }

    /**
     *批量更新
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function updataall()
    {
        $ids = $this->request->param('id');
        $idal = explode(",", $ids);
        foreach ($idal as $vo) {
            BaiduService::instance()->updatazhidao($vo);
            sleep(1);//暂停1秒
        }
        $this->success('更新成功！', 'javascript:history.back()');
    }

    /**
     * 更新信息
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     * @param $id
     */
    public function upurl($id)
    {
        BaiduService::instance()->updatazhidao($id);
        $this->success('更新成功！', 'javascript:history.back()');
    }

    /**
     * 添加文章内容
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function add()
    {
        $this->title = '添加链接';
        WordBaiduzd::mForm('form');
    }

    /**
     * 编辑链接
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function edit()
    {
        $this->title = '编辑链接';
        WordBaiduzd::mForm('form');
    }

    /**
     * 批量添加链接
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function insdata()
    {
        [$state, $rep, $error] = [0, 0, 0];
        if ($this->request->isPost()) {
            if ($this->request->param('url') == '') $this->error('数据不能为空！');
            $url = explode(PHP_EOL, $this->request->param('url'));
            foreach ($url as $vo) {
                if ($vo != '') {
                    if (WordBaiduzd::mk()->where(['url' => $vo, 'is_deleted' => 0])->field('id')->count() > 0) {
                        $rep++;
                    } else {
                        $insdata[] = array(
                            'uid' => session('user.id'),
                            'url' => trim($vo)
                        );
                    }
                }
            }
            if (!empty($insdata)) {
                $state = WordBaiduzd::mk()->insertAll($insdata);
            }
            $this->success('本次更新' . $state . '条信息，重复信息' . $rep . '条,错误' . $error . '条', 'javascript:history.back()');
        }
    }


    protected function _ask_form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            $data['content'] = json_decode($data['content'], true);
        }
    }

    /**
     * 删除
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function remove()
    {
        WordBaiduzd::mDelete();
    }

    /**
     * 标记链接
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function mark($id, $mark)
    {
        $state = WordBaiduzd::mk()->where('id', 'in', $id)->update(['status' => $mark]);
        $this->success('更新成功！', $state);
    }

    /**
     * 表单结果处理
     * @param boolean $state
     */
    protected function _form_result(bool $state)
    {
        if ($state) {
            $this->success('保存成功！', 'javascript:history.back()');
        }
    }

    public function Baidu()
    {
        $data=['uid'=>session('user.id')];
        $this->_queue('百度问题采集', "xsem:GetBaiduTask", 1, $data, 0);
    }

    protected function getParams($url)
    {
        $refer_url = parse_url($url);
        $params = $refer_url['query'];
        $arr = array();
        if (!empty($params)) {
            $paramsArr = explode('&', $params);
            foreach ($paramsArr as $k => $v) {
                $a = explode('=', $v);
                $arr[$a[0]] = $a[1];
            }
        }
        return $arr;
    }


    public function Cjbaidu()
    {
        $this->title = '采集连接';
        WordBaiduurl::mQuery()->where(['is_deleted' => 0, 'uid' => session('user.id')])->order('id desc')->page();
    }

    public function addCj()
    {
        WordBaiduurl::mForm('addCj');
    }

    public function cjedit()
    {
        WordBaiduurl::mForm('addCj');
    }

    public function cjremove()
    {
        WordBaiduurl::mDelete();
    }

    /**
     * 修改通知状态
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */

    public function cjstate()
    {
        WordBaiduurl::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }


}