<?php

namespace app\sem\controller;

use app\sem\model\SemUnitConfig;
use app\sem\service\UnitConfigService;
use think\admin\Controller;
use \PhpOffice\PhpSpreadsheet\IOFactory;
use think\admin\helper\QueryHelper;

/**
 * 单元结构
 */
class Unitconfig extends Controller
{
    /**
     * 单元结构管理
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function index()
    {
        SemUnitConfig::mQuery()->layTable(function () {
            $this->title = '单元结构管理';
        }, function (QueryHelper $query) {
            $query->dateBetween('create_at')->like('title')->equal('status,id');
        });
    }


    public function export()
    {
        $this->pid = input('get.pid', '0');
        SemUnitConfig::mQuery()->layTable(function () {
            $this->title = '单元结构管理';
        }, function (QueryHelper $query) {
            if ($this->pid != 0) {
                $query->whereOr(['pid' => $this->pid, 'id' => $this->pid]);
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
            // $vo['match'] = $this->_matchtostr($vo['match']);
            $vo['match'] = UnitConfigService::instance()->MatchNumtoStr($vo['match']);
            $vo['rootword'] = str_replace(PHP_EOL, "-", $vo['rootword']);
        }
    }

    /**
     * 单元结构JSON 表
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function json()
    {
       // $rs=SemUnitConfig::mk()->whereRaw('uid = ' . session('user.id') . '  OR status = 1')->order('pid,sort desc,id')->select()->toArray();
        $rs = SemUnitConfig::mk()
            ->alias('a')
            ->leftJoin('system_user w', 'a.uid = w.id')
            ->whereRaw('a.uid = ' . session('user.id') . '  OR a.status = 1')
            ->order('pid,sort desc,id')
            ->column('a.id,pid,uid,title,match,rootword,a.status,a.sort,w.nickname');
        $this->success('获取分类成功',$rs , 0);
    }

    public function import()
    {
        $file = $this->app->request->post('file');
        if (!$file) $this->error('文件不能为空');
        $file = '.' . str_replace($this->app->request->domain(), '', $file);
        //表格字段对应
        $cellName = [
            'A' => 'title',//序号
            'B' => 'match',//题目
            'C' => 'rootword',//题目
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

        if (!isset($sheetData)) $this->error('数据不能为空！');
        if (is_array($sheetData)) {
            if (!isset($sheetData[0]['title'])) $this->error('数据不能为空！');
            $id = SemUnitConfig::mk()->insertGetId(['uid' => session('user.id'), 'title' => $sheetData[0]['title']]);

            if (!$id) $this->error('导入数据错误！');
            try {
                foreach ($sheetData as $key => $val) {
                    if ($key > 0) {
                        $rs[$key]['pid'] = $id;
                        $rs[$key]['uid'] = session('user.id');
                        $rs[$key]['title'] = $val['title'];
                        // $rs[$key]['match'] = $this->_match($val['B']);
                        $rs[$key]['match'] = UnitConfigService::instance()->MatchStrtoNum($val['match']);
                        $rs[$key]['rootword'] = UnitConfigService::instance()->rootword($val['rootword']);
                    }
                }
            } catch (\Exception $exception) {
                SemUnitConfig::mk()->where(['id' => $id])->delete();
                $this->error($exception->getMessage());
            }
            $this->app->db->name('SemUnitConfig')->data($rs)->strict(false)->insertAll();
            unset($rs);
            unlink($file);//因为之前使用的是上传的文件进行操作，这里把它删除，看个人情况具体处理
            $this->success('数据导入成功！');
        } else {
            $this->error('导入数据有误！');
        }
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
        SemUnitConfig::mForm('form');
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
        SemUnitConfig::mForm('form');
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
            $this->tmenu = SemUnitConfig::mk()->where(['pid' => 0, 'uid' => session('user.id')])->select()->toArray();
        } else {
            $vo['rootword'] = str_replace(' ', '', $vo['rootword']);
        }
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
        SemUnitConfig::mQuery()->whereOr(['id' => input('id'), 'pid' => input('id')])->delete();
        $this->success('删除数据成功！');
        #SemUnitConfig::mDelete();
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
        SemUnitConfig::mQuery()->whereOr(['id' => input('id'), 'pid' => input('id')])->update([
            'status' => input('status')
        ]);
        $this->success('更新数据成功！');
    }

    protected function _auth($id)
    {
        $uid = isset(SemUnitConfig::mk()->where(['id' => $id])->column('uid')[0]) ? SemUnitConfig::mk()->where(['id' => $id])->column('uid')[0] : false;
        if ($uid) {
            if ($uid != session('user.id')) $this->error('禁止操作！', 'javascript:history.go(0)');
        }
    }


}