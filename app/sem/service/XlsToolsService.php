<?php

namespace app\sem\service;

use app\sem\model\SemKeywords;
use app\sem\model\SemKeywordsConfig;
use app\sem\model\SemRegionConfig;
use app\sem\model\SemUnitConfig;
use think\admin\Service;

class XlsToolsService extends Service
{
    public function ExportBigXls()
    {
        [$SemKeywordsConfig, $SemUnitConfig, $SemRegionConfig] = [[], [], []];
        //SemKeywordsConfig::mk()->whereor(['uid' => session('user.id'), 'status' => 1])->column('id,title')
        foreach (SemKeywordsConfig::mk()->column('id,title') as $val) {
            $SemKeywordsConfig[$val['id']] = $val['title'];
        }
        foreach (SemUnitConfig::mk()->whereor(['uid' => session('user.id'), 'status' => 1])->column('id,title') as $val) {
            $SemUnitConfig[$val['id']] = $val['title'];
        }
        foreach (SemRegionConfig::mk()->whereor(['uid' => session('user.id'), 'status' => 1])->column('id,title') as $val) {
            $SemRegionConfig[$val['id']] = $val['title'];
        }
        //让程序一直运行
        set_time_limit(0);
        //设置程序运行内存
        ini_set('memory_limit', '128M');
        $fileName = '账户关键词-' . date('Y-m-d H:i:s');
        header('Content-Encoding: UTF-8');
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
        //打开php标准输出流
        $fp = fopen('php://output', 'a');
        //添加BOM头，以UTF8编码导出CSV文件，如果文件头未添加BOM头，打开会出现乱码。
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        //添加导出标题
        fputcsv($fp, ['ID', '计划', '单元', '关键词']);
        $nums = 10000; //每次导出数量where(['deleted' => 0]);
        $allnum = SemKeywords::mk()->where(['uid' => session('user.id'), 'deleted' => 0])->field('id')->count();
        $step = ceil($allnum / $nums);
        for ($i = 0; $i < $step; $i++) {
            $start = $i * 10000;
            $result = SemKeywords::mk()->where(['deleted' => 0, 'uid' => session('user.id')])->limit($start, $nums)->column('id,pid,unitid,keywords,regionid');
            foreach ($result as $item) {
                if ($item['pid'] != 0) {
                    $item['pid'] = $SemKeywordsConfig[$item['pid']];
                } else {
                    $item['pid'] = '默认计划';
                }
                if ($item['unitid'] != 0) {
                    $item['unitid'] = $SemUnitConfig[$item['unitid']];
                } else {
                    $item['unitid'] = '默认单元';
                }
                if ($item['regionid']) {
                    $item['unitid'] = $item['unitid'] . '-' . $SemRegionConfig[$item['regionid']];
                    $item['regionid']='';
                }else{
                    $item['regionid']='';
                }
                fputcsv($fp, $item);
            }
            ob_flush();
            flush();

        }
    }
}