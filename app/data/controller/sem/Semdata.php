<?php

namespace app\data\controller\sem;

use think\admin\Controller;
use \PhpOffice\PhpSpreadsheet\IOFactory;
use app\data\model\DataSem;
use think\db\Where;

class Semdata extends Controller
{

    public function index()
    {
        $this->title = 'SEM 关键词格式化工具';
        DataSem::mQuery()->like('danyuan')->where(['deleted' => 0])->order('id asc')->page();
    }

    public function import()
    {
        $fugai = $this->app->request->post('fugai');
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
        if ($this->request->isPost() && $fugai == '') {
            DataSem::mk()->where('id', '>', 0)->delete();
        }
        try {
            foreach ($data as $key => $val) {
                if (($key % 2) == 0) {
                    for ($i = 0; $i <= count($data[$key + 1]) - 1; $i++) {
                        $rs[$i]['danyuan'] = $val[1];
                        $rs[$i]['keywords'] = $data[$key + 1][$i + 1];//$val[1];
                    }
                    $this->app->db->name('DataSem')->data($rs)->strict(false)->insertAll();
                    unset($rs);
                }
            }
        } catch (\Exception $exception) {
            DataSem::mk()->where('id', '>', 0)->delete();
            $this->error($exception->getMessage());
        }
        $this->success('导入成功');
    }
    /**
     * 删除系统任务
     * @auth true
     */
    /**
     * 删除商品数据
     * @auth true
     */
    public function remove()
    {
        DataSem::mDelete();
        /*
        DataSem::mSave($this->_vali([
            'deleted.in:0,1'  => '状态值范围异常！',
            'deleted.require' => '状态值不能为空！',
        ]), 'id');
        */
    }

}