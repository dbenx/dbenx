<?php

namespace app\sem\controller;

use app\sem\model\SemKeywords;
use app\sem\model\SemKeywordsCs;
use app\sem\model\SemKeywordsConfig;
use app\sem\model\SemRegionConfig;
use app\sem\model\SemUnitConfig;
use app\sem\service\ParticipleCsService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use \PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * 分词工具
 */
class ParticipleCs extends Controller
{
    /**
     * 分词工具
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function index()
    {
        // 创建快捷查询工具
        SemKeywordsCs::mQuery()->layTable(function () {
            $this->title = '分词工具';
            $this->allkeyword = SemKeywordsCs::mk()->where(['deleted' => 0, 'uid' => session('user.id')])->field('id')->count();
            $this->Notkeyword = SemKeywordsCs::mk()->where(['deleted' => 0, 'uid' => session('user.id'), 'unitid' => 0])->field('id')->count();
            $this->kcon = SemKeywordsConfig::mk()
                ->where(['pid' => 0])
                ->whereRaw('uid = ' . session('user.id') . '  OR status = 1 AND pid=0')
                ->order('sort desc,id')
                ->column('id,title');
            $this->units = SemUnitConfig::mk()
                ->where(['pid' => 0])
                ->whereRaw('uid = ' . session('user.id') . '  OR status = 1 AND pid=0')
                ->order('sort desc,id')->column('id,title');
            $this->region = SemRegionConfig::mk()->whereRaw('uid = ' . session('user.id') . '  OR status = 1')->column('id,title');
        }, function (QueryHelper $query) {
            // 数据列表搜索过滤
            $query->where(['deleted' => 0, 'uid' => session('user.id')])->equal('id')->order("unitid asc,pid asc,regionid desc,id");
        });

    }

    /**
     * 列表数据处理
     * @param array $data
     */
    protected function _index_page_filter(array &$data)
    {
        foreach ($data as &$vo) {
            try {
                $SemKeywordsConfig = SemKeywordsConfig::mk()->where(['id' => $vo['pid']])->column('rootword,match,title')[0];
                $vo['plankw'] = $SemKeywordsConfig['rootword'];
                $vo['planmatch'] = $SemKeywordsConfig['match'];
                $vo['pid'] = $SemKeywordsConfig['title'];
            } catch (\Exception $exception) {
            }

            try {
                $SemUnitConfig = SemUnitConfig::mk()->where(['id' => $vo['unitid']])->column('rootword,match,title')[0];
                $vo['unitkw'] = $SemUnitConfig['rootword'];
                $vo['unitmatch'] = $SemUnitConfig['match'];
                $vo['unitid'] = $SemUnitConfig['title'];
            } catch (\Exception $exception) {
                $vo['unitid'] = '未分配单元';
            }
            try {
                $SemRegionConfig = SemRegionConfig::mk()->where(['id' => $vo['regionid']])->column('title,regionalwords')[0];
                $vo['regionid'] = $SemRegionConfig['title'];
                $vo['regionkw'] = $SemRegionConfig['regionalwords'];
            } catch (\Exception $exception) {
                $vo['regionid'] = '';
            }
            if ($vo['regionid']) {
                $vo['danyuan'] = $vo['unitid'] . '-' . $vo['regionid'];
            } else {
                $vo['danyuan'] = $vo['unitid'];
            }
        }
    }


    /**
     * exl 数据导入
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
            SemKeywordsCs::mk()->where('id', '>', 0)->delete();
            $this->error($exception->getMessage());
        }
        $num = SemKeywordsCs::mk()->insertAll($rs);
        $this->success('导入成功' . $num . '条');
    }

    public function findall()
    {
        $this->title = "分词已完成预览";
        $this->type = $this->request->get('type', 'pid');
        $start_time = microtime(true);
        if ($this->request->isPost()) {

            $jhid = $this->request->param('id');//计划规则ID
            $unitid = $this->request->param('unitid');//单元规则ID
            $regionid = $this->request->param('regionid');//单元规则ID
            if (empty($jhid)) $this->error('请选择计划规则！');
            if (empty($unitid)) $this->error('请选择单元规则！');

            /*计划分配*/
            SemKeywordsCs::mk()->where(['uid' => session('user.id')])->update(['pid' => 0]);
            ParticipleCsService::instance()->matching($jhid);


            /*单元分配*/
            SemKeywordsCs::mk()->where(['uid' => session('user.id')])->update(['unitid' => 0]);
            ParticipleCsService::instance()->Umatching($unitid);

            if (!empty($regionid)) {
                SemKeywordsCs::mk()->where(['uid' => session('user.id')])->update(['regionid' => 0]);
                ParticipleCsService::instance()->Ratching($regionid);
            }
            // $this->fetch();

        }
        $end_time = microtime(true);
        $this->execution_time = sprintf("%.2f", ($end_time - $start_time));
        // 状态数据统计
        $this->total = ['pid' => 0, 'unit' => 0, 'noall' => 0, 'all' => 0, 'jhnum' => 0, 'unitnum' => 0];
        $this->total['allkeywords'] = SemKeywordsCs::mk()->count('id');
        $this->total['pid'] = SemKeywordsCs::mk()->where(['uid' => session('user.id'), 'pid' => 0])->count('id');
        $this->total['unit'] = SemKeywordsCs::mk()->where(['uid' => session('user.id'), 'unitid' => 0])->where('pid', '<>', 0)->count('id');
        $this->total['noall'] = SemKeywordsCs::mk()->where(['uid' => session('user.id'), 'pid' => 0, 'unitid' => 0])->count('id');
        $this->total['all'] = SemKeywordsCs::mk()->where(['uid' => session('user.id')])->where('pid', '<>', 0)->where('unitid', '<>', 0)->count('id');

        $this->total['jhnum'] = SemKeywordsCs::mk()->field('pid,count(1) total')->where(['uid' => session('user.id')])->where('pid', '<>', 0)->group('pid')->count('pid');
        $this->total['unitnum'] = SemKeywordsCs::mk()->field('unitid,count(1) total')->where(['uid' => session('user.id')])->where('pid', '<>', 0)->group('unitid')->count('unitid');

        $query = SemKeywordsCs::mQuery();
        if ($this->type === 'allkeywords') $query->where(['deleted' => 0]);
        elseif ($this->type === 'pid') $query->where(['pid' => 0]);
        elseif ($this->type === 'unit') $query->where(['unitid' => 0])->where('pid', '<>', 0);
        elseif ($this->type === 'noall') $query->where(['pid' => 0, 'unitid' => 0]);
        elseif ($this->type === 'all') $query->where('pid', '<>', 0)->where('unitid', '<>', 0);
        else $this->error("无法加载 {$this->type} 数据列表！");
        $query->like('keyworod')->dateBetween('create_at');
        $query->where(['uid' => session('user.id')])->order('pid desc,unitid desc')->page();

    }

    /**
     * 查找匹配的词
     * 1 包含  2不包含  3等于  4不等于  5 开始于 6截止于
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function find($id)
    {
      //  if (empty($id)) $this->error('请选择计划规则！');
      //  SemKeywordsCs::mk()->where(['uid' => session('user.id')])->update(['pid' => 0]);
        echo $id;
      //  ParticipleCsService::instance()->matching($id);
     //   $this->success('数据更新成功！', 'javascript:history.back()');
    }



    public function ufind($unitid)
    {
       // echo $unitid;

      //  if (empty($unitid)) $this->error('请选择单元规则！');
       // SemKeywordsCs::mk()->where(['uid' => session('user.id')])->update(['unitid' => 0]);
        //  $this->_Umatching($unitid);
        ParticipleCsService::instance()->Umatching($unitid);

       // $this->success('数据更新成功！', 'javascript:history.back()');
    }

    public function rfind($regionid)
    {
        if (empty($regionid)) $this->error('请选择地域规则！');
        SemKeywordsCs::mk()->where(['uid' => session('user.id')])->update(['regionid' => 0]);
        ParticipleService::instance()->Ratching($regionid);
        # echo  SemKeywordsCs::getLastSql();
        $this->success('数据更新成功！', 'javascript:history.back()');
    }


    public function cs()
    {
        $map[] = ['keywords', '=', '初级厨师证报考多少钱'];
        $map[] = ['keywords', '=', '厨师'];
        $cc = SemKeywords::mk()->whereOr($map)->select()->toArray();
        echo SemKeywords::getLastSql();
        var_dump($cc);
    }

    /**
     * 删除数据
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     */
    public function remove()
    {
        #$num = SemKeywordsCs::mk()->where('id', '>', 0)->delete();
        SemKeywordsCs::mQuery()->empty();
        $this->success('删除数据成功!');
    }

}