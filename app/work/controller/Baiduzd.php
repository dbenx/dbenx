<?php

namespace app\work\controller;

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
                $query->where('status','<>',2);
            }
            // 数据列表搜索过滤
            $query->dateBetween('create_at')->equal('status')->like('title,username,url');
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

    public function logo()
    {
        $url = 'https://zhidao.baidu.com/submit/ajax/';

        $header = array(
            "Content-Type" => "application/x-www-form-urlencoded; charset=UTF-8",
            "X-ik-token" => "a913575159b88f6df452b56ed039be45",
            "Cookie" => array(
                "BAIDUID" => "A1E5A137E0536B9B717CAC3C72252D18:FG=1",
                "BDUSS" => "FZPSHB0NmV0eXFwd3JDY3dNZGRQeFRPc2hXUnFaazJtY3VQNndxS1BKRHBKNXRpSVFBQUFBJ",
                "BDUSS_BFESS" => "FZPSHB0NmV0eXFwd3JDY3dNZGRQeFRPc2hXUnFaazJtY3VQNndxS1BKRHBKNXRpSVFBQUFBJ",
                "BIDUPSID" => "A1E5A137E0536B9B717CAC3C72252D18",
                "PSTM" => "652106681",
                "Hm_lvt_6859ce5aaf00fb00387e6434e4fcc925" => "1652413417,1654132512",
                "cflag" => "13 % 3A3",
                "shitong_key_id" => "2",
                "ZD_ENTRY" => "other",
                "BAIDUID_BFESS" => "54D113BC436885D61368E083AF6394A6:FG = 1",
                "session_id" => "165415684312737243812809752397",
                "ab_sr" => "1.0.1_YjJiYjk5MzI4OTI2ODEzOWJjMThlNmZlMmU0YTBiNzlkOTczZGUwMzM3ZDFkOWVmNTZlMTNi",
                "Hm_lpvt_6859ce5aaf00fb00387e6434e4fcc925" => "1654156843",
                "shitong_data" => "5027c3cd26506aa62faca8208cc71c13d4a3608bf77997ff2a4fdbd65dfc85fec35c8074ff8a880064de3",
                "shitong_sign" => "8d1d25da"
            ),
        );
        $body = array(
            "qid"=>592332214999148805,
            "co"=>'<p data-diagnose-id="6ac73c4ee5459edf8a45459ed94c2e1f">深圳新东方烹饪学校是经深圳市坪山区人力资源局批准成立的大型烹饪专业学校。学校专业设置齐全，涵盖以粤菜、川菜、湘菜为代表的传统八大菜系，以及西点西餐教育,是培养高级烹调师、中西面点师和烹饪管理人才的餐饮教育基地。</p><p data-diagnose-id="3e745e0caa3522135fc20ac8b35641f6">深圳市坪山区新东方烹饪职业培训学校于2015年创立，经深圳市坪山区人力资源局批准成立的专业烹饪培训学校。</p>',
            "stoken"=>"a913575159b88f6df452b56ed039be45"
        );
        $result = BaiduService::instance()->post($url, $body, $header);
        var_dump($result);

    }


    public function getdata()
    {
        $url = "https://www.douyin.com/aweme/v1/web/comment/list/?device_platform=webapp&aid=6383&channel=channel_pc_web&aweme_id=7091618202253298956&cursor=70&count=5&item_type=0&rcFT=AAO2wQu7w&version_code=170400&version_name=17.4.0&cookie_enabled=true&screen_width=1920&screen_height=1080&browser_language=zh-CN&browser_platform=Win32&browser_name=Chrome&browser_version=100.0.4896.127&browser_online=true&engine_name=Blink&engine_version=100.0.4896.127&os_name=Windows&os_version=10&cpu_core_num=8&device_memory=8&platform=PC&downlink=10&effective_type=4g&round_trip_time=100&webid=7098588012937905664&msToken=qySNnKuK_0RxqvNpYKvondHiLbHuBXDBn36FLJ3Py2yRijQ_MmVkhBIgX7jdGIq7N0a-omkpvsROnFnDJ1H6Uulcv3vYVaeBcqlAtf6VBKlPPxMoJ5waCUrpZc0j2nrq&X-Bogus=DFSzswVuy-tANnrUSwlOORXAIQRL&_signature=_02B4Z6wo000012r4afgAAIDCCfKpkW0NJp9q-G1AALg4bfujojL.ftgN3nu3ZgIZhulhd.7FmRxF3xSPMH.UuG1QfFlaLOgc.Vlg2w9fMkT0FCoklUSWWfnS15dJSsa5p.oShctPi4ts.jll29";
        $body = array(
            "aid" => 6383,
            "aweme_id" => 7091618202253298956,
            "cursor" => 110,
            "count" => 5,
            "rcFT" => "AAQffZzBg",
            "webid" => 7098588012937905664,
            "msToken" => "e-6wtHh5r732Lz7Lu3h_xk7U1QYQZMYRulQG0vIKT0ihV-GY-HZLgdG5T64EDrqVnB17G9gfeIY0ct_XDlwUWNxd9ZrGdUxjdDRABkfcmxCgMRMQljclwqki7lwVSe8=
        X-Bogus: DFSzKwVLo40ANVLnSwluhRXAIQ20",
            "_signature" => " _02B4Z6wo00001f-JuOQAAIDAnIN4jFjSrln.ibxAAB1zd92V4xgVJLwdESMaTSA7KpxDGtH-rVubm9JVtlzqW0EbvZ2IIb5xnAOdPj.UxUqve1XRO.UJMoq5M1ZMOwdruT1YFH5IyrKzghnF24"
        );

        $rs = $this->curl_get($url);
        var_dump($rs);
    }


    public function curl_get($url)
    {
        $info = curl_init();
        curl_setopt($info, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($info, CURLOPT_HEADER, 0);
        curl_setopt($info, CURLOPT_NOBODY, 0);
        curl_setopt($info, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info, CURLOPT_URL, $url);
        $output = curl_exec($info);
        $aStatus = curl_getinfo($info);
        curl_close($info);
        echo "<pre>";
        var_dump($aStatus);
        return json_decode($output, true);
    }


    public function FormatHeader($url, $useragent)
    {
        // 解析url
        $temp = parse_url($url);
        $query = isset($temp['query']) ? $temp['query'] : '';
        $path = isset($temp['path']) ? $temp['path'] : '/';
        $header = array(
            "POST {$path}?{$query} HTTP/1.1",
            "Host: {$temp['host']}",
            "Referer: http://{$temp['host']}/",
            "Content-Type: text/xml; charset=utf-8",
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding:gzip, deflate, br',
            'Accept-Language:zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2',
            'Connection:keep-alive',
            'X-Requested-With: XMLHttpRequest',
            'User-Agent: ' . $useragent,
        );
        return $header;

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
     * 删除系统任务
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

}