<?php

namespace app\sem\controller;

use app\sem\model\SemKeywords;
use app\sem\model\SemKeywordsConfig;
use app\sem\model\SemRegionConfig;
use app\sem\model\SemUnitConfig;
use app\sem\model\SemUrlsuffixConfig;
use app\sem\service\ParticipleService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use \PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * 分词工具
 */
class Participle extends Controller
{
    /**
     * 分词工具
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function index()
    {
        $this->type = input('get.type', 'pid');
        // 创建快捷查询工具
        SemKeywords::mQuery()->layTable(function () {
            $this->title = '分词工具';
            $this->total = ParticipleService::instance()->total();
            $rule = ParticipleService::instance()->rule();
            $this->kcon = $rule['kcon'];
            $this->units = $rule['units'];
            $this->region = $rule['region'];
            $this->urlsuffix = $rule['urlsuffix'];
        }, function (QueryHelper $query) {
            if ($this->type === 'allkeywords') $query->where(['deleted' => 0]);
            elseif ($this->type === 'pid') $query->where(['pid' => 0]);
            elseif ($this->type === 'unit') $query->where(['unitid' => 0])->where('pid', '<>', 0);
            elseif ($this->type === 'noall') $query->where(['pid' => 0, 'unitid' => 0]);
            elseif ($this->type === 'all') $query->where('pid', '<>', 0)->where('unitid', '<>', 0);
            // 数据列表搜索过滤
            $query->where(['deleted' => 0, 'uid' => session('user.id')]);
            $query->field('id,uid,pid,unitid,regionid,keywords');
            $query->like('keywords');
            $query->order("unitid asc,pid asc,regionid desc,id");

        });
    }

    /**
     * 列表数据处理
     * @param array $data
     */
    protected function _index_page_filter(array &$data)
    {
        foreach (SemKeywordsConfig::mk()->where('pid', '<>', 0)->whereor(['uid' => session('user.id'), 'status' => 1])->column('id,rootword,match,title') as $val) {
            $SemKeywordsConfig[$val['id']] = array('rootword' => $val['rootword'], 'match' => $val['match'], 'title' => $val['title']);
        }

        foreach (SemUnitConfig::mk()->where('pid', '<>', 0)->whereor(['uid' => session('user.id'), 'status' => 1])->column('id,rootword,match,title') as $val) {
            $SemUnitConfig[$val['id']] = array('rootword' => $val['rootword'], 'match' => $val['match'], 'title' => $val['title']);
        }
        foreach (SemRegionConfig::mk()->whereor(['uid' => session('user.id'), 'status' => 1])->column('id,regionalwords,title') as $val) {
            $SemRegionConfig[$val['id']] = array('regionalwords' => $val['regionalwords'], 'title' => $val['title']);
        }

        foreach ($data as &$vo) {
            if ($vo['pid'] != 0) {
                $vo['plankw'] = $SemKeywordsConfig[$vo['pid']]['rootword'];
                $vo['planmatch'] = $SemKeywordsConfig[$vo['pid']]['match'];
                $vo['pid'] = $SemKeywordsConfig[$vo['pid']]['title'];
            }

            if ($vo['unitid'] != 0) {
                $vo['unitkw'] = $SemUnitConfig[$vo['unitid']]['rootword'];
                $vo['unitmatch'] = $SemUnitConfig[$vo['unitid']]['match'];
                $vo['unitid'] = $SemUnitConfig[$vo['unitid']]['title'];
            }

            if ($vo['regionid'] != 0) {
                $vo['regionkw'] = $SemRegionConfig[$vo['regionid']]['regionalwords'];
                $vo['regionid'] = $SemRegionConfig[$vo['regionid']]['title'];
            }
        }
    }


    /**
     * 导入excel数据
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function import()
    {
        $file = $this->app->request->post('file');
        if (!$file) $this->error('文件不能为空');
        $file = '.' . str_replace($this->app->request->domain(), '', $file);
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

        for ($currentColumn = 'A'; $currentColumn != $allColumn; $currentColumn++) {
            for ($currentRow = 0; $currentRow <= $allRow; $currentRow++) {
                //数据坐标
                $address = $currentColumn . $currentRow;
                //读取到的数据，保存到数组$data中
                $cell = $sheet->getCell($address)->getValue();
                if (!$cell) continue;
                $data[$currentColumn][$currentRow] = $cell;
            }

        }
        $data = array_values($data);
        try {
            foreach ($data[0] as $key => $val) {
                $rs[] = array(
                    'keywords' => $val,
                    'uid' => session('user.id')
                );
            }
        } catch (\Exception $exception) {
            SemKeywords::mk()->where('id', '>', 0)->delete();
            $this->error($exception->getMessage());
        }
        $num = SemKeywords::mk()->insertAll($rs);
        $this->success('导入成功' . $num . '条');
    }

    /**
     * 导出数据
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function export()
    {
        SemKeywords::mQuery()->layTable(function () {

        }, function (QueryHelper $query) {
            // $query->with('SemKeywordsConfig');
            $query->where(['deleted' => 0, 'uid' => session('user.id')])->field('id,pid,unitid,regionid,keywords')->order("pid asc,unitid asc,regionid desc,id");
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
        [$SemKeywordsConfig, $SemUnitConfig, $SemRegionConfig] = [[], [], []];
        foreach (SemKeywordsConfig::mk()->whereor(['uid' => session('user.id'), 'status' => 1])->column('id,title') as $val) {
            $SemKeywordsConfig[$val['id']] = $val['title'];
        }
        foreach (SemUnitConfig::mk()->whereor(['uid' => session('user.id'), 'status' => 1])->column('id,title') as $val) {
            $SemUnitConfig[$val['id']] = $val['title'];
        }
        foreach (SemRegionConfig::mk()->whereor(['uid' => session('user.id'), 'status' => 1])->column('id,title') as $val) {
            $SemRegionConfig[$val['id']] = $val['title'];
        }

        foreach ($data as &$vo) {
            if ($vo['pid'] != 0) {
                $vo['pid'] = $SemKeywordsConfig[$vo['pid']];
            } else {
                $vo['pid'] = '默认计划';
            }
            if ($vo['unitid'] != 0) {
                $vo['unitid'] = $SemUnitConfig[$vo['unitid']];
            } else {
                $vo['unitid'] = '默认单元';
            }
            if ($vo['regionid']) {
                $vo['danyuan'] = $vo['unitid'] . '-' . $SemRegionConfig[$vo['regionid']];
            } else {
                $vo['danyuan'] = $vo['unitid'];
            }

        }
    }

    public function geturlsuffix()
    {
        SemKeywords::mQuery()->layTable(function () {
            $this->title = '系统日志管理';
        }, function (QueryHelper $query) {
            $query->dateBetween('create_at')->equal('username,action')->like('keywords');
        });
    }

    /**
     * 删除数据
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function remove()
    {
        $num = SemKeywords::mk()->where(['uid' => session('user.id')])->where('id', '>', 0)->delete();
        #SemKeywords::mQuery()->empty();
        $this->success('删除数据成功!');
    }

    /**
     * 清空匹配规则
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function clear()
    {
        SemKeywords::mQuery()->where(['uid' => session('user.id')])->update([
            'pid' => 0,
            'unitid' => 0,
            'regionid' => 0
        ]);
        $this->success('数据成功!');
    }

    /**
     * 分词任务
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function fcrw()
    {
        if ($this->request->isPost()) {
            $jhid = $this->request->post('id');//计划规则ID
            $unitid = $this->request->post('unitid');//单元规则ID
            $regionid = $this->request->post('regionid');//单元规则ID
            if (empty($jhid)) $this->error('请选择计划规则！');
            if (empty($unitid)) $this->error('请选择单元规则！');
            // SemKeywords::mk()->where(['uid' => session('user.id')])->update(['pid' => 0]);
            //  SemKeywords::mk()->where(['uid' => session('user.id')])->update(['unitid' => 0]);
            $data = array('jhid' => $jhid, 'unitid' => $unitid, 'regionid' => $regionid, 'uid' => session('user.id'));
            $this->_queue('分词任务', 'xsem:ParticipleTask', 0, $data);
        }
    }


}