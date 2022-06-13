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
            $vo['rootword'] =str_replace(PHP_EOL, "-", $vo['rootword']);
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
        $this->success('获取分类成功', SemUnitConfig::mk()->whereRaw('uid = ' . session('user.id') . '  OR status = 1')->order('pid,sort desc,id')->select()->toArray(), 0);
    }

    public function import()
    {
        $file = $this->app->request->post('file');
        if (!$file) $this->error('文件不能为空');
        $file = '.' . str_replace($this->app->request->domain(), '', $file);
        // $file = './upload/75/bed21bdc40abe61c0397cf2182a118.xls';

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
                $cell = $sheet->getCell($address)->getValue();
                if (!$cell) continue;
                // $data[$currentColumn][$currentRow] = $cell;
                $data[$currentRow][$currentColumn] = $cell;
            }
        }
        if (!isset($data)) $this->error('数据不能为空！');


        if (is_array($data)) {
            if (!isset($data[2]['A'])) $this->error('数据不能为空！');
            $id = SemUnitConfig::mk()->insertGetId(['uid' => session('user.id'), 'title' => $data[2]['A']]);
            if (!$id) $this->error('导入数据错误！');
            try {
                foreach ($data as $key => $val) {
                    if ($key > 2) {
                        $rs[$key]['pid'] = $id;
                        $rs[$key]['uid'] = session('user.id');
                        $rs[$key]['title'] = $val['A'];
                       // $rs[$key]['match'] = $this->_match($val['B']);
                        $rs[$key]['match'] = UnitConfigService::instance()->MatchStrtoNum($val['B']);

                        $rs[$key]['rootword'] = UnitConfigService::instance()->rootword($val['C']);
                    }
                }
            } catch (\Exception $exception) {
                SemUnitConfig::mk()->where(['id' => $id])->delete();
                $this->error($exception->getMessage());
            }
            $this->app->db->name('SemUnitConfig')->data($rs)->strict(false)->insertAll();
            unset($rs);
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
            $this->tmenu = SemUnitConfig::mk()->where(['pid' => 0,'uid'=>session('user.id')])->select()->toArray();
        }else{
            $vo['rootword']=str_replace(' ','',$vo['rootword']);
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