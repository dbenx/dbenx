<?php

namespace app\sem\controller;

use app\sem\model\SemKeywordsConfig;
use app\sem\model\SemKeywordsConfigcs;
use app\sem\service\KeywordConfigService;
use app\sem\service\UnitConfigService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\admin\Controller;
use think\admin\extend\DataExtend;
use think\admin\helper\QueryHelper;

/**
 * 账户结构
 */
class Keywordsconfig extends Controller
{
    /**
     * 账户结构管理
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        SemKeywordsConfig::mQuery()->layTable(function () {
            $this->title = '账户结构管理';
        }, function (QueryHelper $query) {
            $query->dateBetween('create_at')->like('title')->equal('status');
        });
    }


    /**
     * 数据导出管理
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function export()
    {
        $this->pid = input('get.pid', '0');

        SemKeywordsConfig::mQuery()->layTable(function () {

        }, function (QueryHelper $query) {
            $data = SemKeywordsConfig::mk()->select()->toArray();
            $ids = join(',', DataExtend::getArrSubIds($data, $this->pid));
            if ($this->pid != 0) {
                $query->whereIn('id', $ids);
            }
            $query->dateBetween('create_at')->like('title')->equal('status,id')->order('pid,sort desc,id');
        });
    }

    /**
     * 列表数据处理
     * @auth true
     * @param array $data
     * @throws \Exception
     */
    protected function _export_page_filter(array &$data)
    {

        foreach ($data as &$vo) {
            $vo['match'] = UnitConfigService::instance()->MatchNumtoStr($vo['match']);
            $vo['rootword'] = str_replace(PHP_EOL, "-", $vo['rootword']);
        }
    }

    public function json()
    {
        /*
        $rs = SemKeywordsConfig::mk()
            ->whereRaw('uid = ' . session('user.id') . '  OR status = 1')
            ->order('pid,sort desc,id')
            ->column('id,pid,uid,title,match,rootword,status,sort');
        */
        $rs = SemKeywordsConfig::mk()
            ->alias('a')
            ->leftJoin('system_user w', 'a.uid = w.id')
            ->whereRaw('a.uid = ' . session('user.id') . '  OR a.status = 1')
            ->order('pid,sort desc,id')
            ->column('a.id,pid,uid,title,match,rootword,a.status,a.sort,w.nickname');
        $this->success('获取分类成功', $rs, 0);
    }


    public function cs()
    {

    }


    /**
     * 添加规则
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function add()
    {
        $this->_auth(input('pid'));
        SemKeywordsConfig::mForm('form');
    }

    /**
     * 编辑规则
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function edit()
    {
        $this->_auth(input('id'));
        SemKeywordsConfig::mForm('form');
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
        $data = SemKeywordsConfig::mk()->select()->toArray();
        $ids = join(',', DataExtend::getArrSubIds($data, input('id')));
        SemKeywordsConfig::mQuery()->whereIn('id', $ids)->delete();
        $this->success('删除数据成功！');
    }

    /**
     * 修改规则状态
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function state()
    {
        $this->_auth(input('id'));
        $data = SemKeywordsConfig::mk()->select()->toArray();
        $ids = join(',', DataExtend::getArrSubIds($data, input('id')));
        SemKeywordsConfig::mQuery()->whereIn('id', $ids)->update([
            'status' => input('status')
        ]);
        $this->success('更新数据成功！');
    }

    /**
     * 表单数据处理
     * @param array $vo
     * @throws \ReflectionException
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isGet()) {
            /* 选择自己的上级菜单 */
            $vo['pid'] = $vo['pid'] ?? input('pid', '0');

            /* 列出可选上级菜单 */
            $menus = SemKeywordsConfig::mk()->order('sort desc,id asc')->where(['uid' => session('user.id')])->column('id,pid,rootword,title', 'id');
            # var_dump($menus);
            $this->menus = DataExtend::arr2table(array_merge($menus, [['id' => '0', 'pid' => '-1', 'title' => '顶部菜单']]));
            if (isset($vo['id'])) foreach ($this->menus as $menu) if ($menu['id'] === $vo['id']) $vo = $menu;
            foreach ($this->menus as $key => $menu) if ($menu['spt'] >= 4) unset($this->menus[$key]);
            if (isset($vo['spt']) && isset($vo['spc']) && in_array($vo['spt'], [1, 2, 3]) && $vo['spc'] > 0) {
                foreach ($this->menus as $key => $menu) if ($vo['spt'] <= $menu['spt']) unset($this->menus[$key]);
            }
        } else {
            $vo['rootword'] = str_replace(' ', '', $vo['rootword']);
        }
    }

    public function import()
    {
        $file = $this->app->request->post('file');
        if (!$file) $this->error('文件不能为空');
        $file = '.' . str_replace($this->app->request->domain(), '', $file);
        //表格字段对应
        $cellName = [
            'A' => 'id',//序号
            'B' => 'pid',//上级ID
            'C' => 'title',//标题
            'D' => 'match',//匹配模式
            'E' => 'rootword',//词根
        ];
        //加载文件
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();               // 获取表格
        $highestRow = $sheet->getHighestRow();                 // 取得总行数
        $sheetData = [];
        for ($row = 2; $row <= $highestRow; $row++) {          // $row表示从第几行开始读取
            foreach ($cellName as $cell => $field) {
                $value = $sheet->getCell($cell . $row)->getValue();
                $value = trim($value);
                $sheetData[$row][$field] = $value;
            }
        }
        $sheetData = array_values($sheetData);


        if (is_array($sheetData)) {
            if (!isset($sheetData[0]['id'])) $this->error('数据不能为空！');
            try {
                foreach ($sheetData as $key => $val) {
                    $rs[$key]['id'] = $val['id'];
                    $rs[$key]['pid'] = $val['pid'];
                    $rs[$key]['uid'] = session('user.id');
                    $rs[$key]['title'] = $val['title'];
                    $rs[$key]['match'] = UnitConfigService::instance()->MatchStrtoNum($val['match']);
                    $rs[$key]['rootword'] = isset($val['rootword']) ? UnitConfigService::instance()->rootword($val['rootword']) : '';
                }
            } catch (\Exception $exception) {
                $this->error($exception->getMessage());
            }
            $rds = KeywordConfigService::instance()->generateTree($rs);
            for ($i = 0; $i < count($rds); $i++) {
                KeywordConfigService::instance()->DiguiDb($rds[$i]);
            }
            unset($rs);
            unlink($file);//因为之前使用的是上传的文件进行操作，这里把它删除，看个人情况具体处理
            $this->success('数据导入成功！');
        } else {
            $this->error('导入数据有误！');
        }


    }


    public function import1()
    {
        $file = $this->app->request->post('file');
        if (!$file) $this->error('文件不能为空');
        $file = '.' . str_replace($this->app->request->domain(), '', $file);
        //$file = './upload/ff/bab700928fa0ca68e50abe630bb276.xlsx';
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();               // 获取表格
        $highestRow = $sheet->getHighestRow();                 // 取得总行数
        $sheetData = [];
        //获取总列数
        $allColumn = $sheet->getHighestColumn();
        $allColumn++;
        //获取总行数
        $allRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        for ($currentRow = 0; $currentRow <= $allRow; $currentRow++) {
            for ($currentColumn = 'A'; $currentColumn != $allColumn; $currentColumn++) {
                //数据坐标
                $address = $currentColumn . $currentRow;
                //读取到的数据，保存到数组$data中
                $cell = (string)$sheet->getCell($address)->getValue();
                if ($cell === '') continue;
                // $data[$currentColumn][$currentRow] = $cell;
                $data[$currentRow][$currentColumn] = $cell;
            }
        }
        unset($data[1]);//前两列数据不需要
        if (!isset($data)) $this->error('数据不能为空！');
        if (is_array($data)) {
            if (!isset($data[2]['A'])) $this->error('数据不能为空！');
            try {
                foreach ($data as $key => $val) {
                    $rs[$key]['id'] = $val['A'];
                    $rs[$key]['pid'] = $val['B'];
                    $rs[$key]['uid'] = session('user.id');
                    $rs[$key]['title'] = $val['C'];
                    $rs[$key]['match'] = UnitConfigService::instance()->MatchStrtoNum($val['D']);
                    $rs[$key]['rootword'] = isset($val['E']) ? UnitConfigService::instance()->rootword($val['E']) : '';
                }
            } catch (\Exception $exception) {
                $this->error($exception->getMessage());
            }


            $rds = KeywordConfigService::instance()->generateTree($rs);
            for ($i = 0; $i < count($rds); $i++) {
                KeywordConfigService::instance()->DiguiDb($rds[$i]);
            }
            unset($rs);
            unlink($file);//因为之前使用的是上传的文件进行操作，这里把它删除，看个人情况具体处理
            $this->success('数据导入成功！');
        } else {
            $this->error('导入数据有误！');
        }
    }


    protected function _auth($id)
    {
        $uid = isset(SemKeywordsConfig::mk()->where(['id' => $id])->column('uid')[0]) ? SemKeywordsConfig::mk()->where(['id' => $id])->column('uid')[0] : false;
        if ($uid) {
            if ($uid != session('user.id')) $this->error('禁止操作！', 'javascript:history.go(0)');
        } else {
            // $this->error('数据错误');
        }
    }


}