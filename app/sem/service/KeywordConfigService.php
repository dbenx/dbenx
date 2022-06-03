<?php

namespace app\sem\service;

use app\sem\model\SemKeywordsConfig;
use think\admin\Service;

class KeywordConfigService extends Service
{

    /**
     * 导入的数据递归存入数据库
     * @param $arr
     * @param int $pid
     */
    public function DiguiDb($arr, $pid = 0)
    {
        $data['uid'] = session('user.id');
        $data['title'] = $arr['title'];
        $data['match'] = $arr['match'];
        $data['rootword'] = $arr['rootword']??'';
        $data['pid'] = $pid;
        $pid =SemKeywordsConfig::mk()->insertGetId($data);
        if(isset($arr['son'])){
            for ($i = 0; $i < count($arr['son']); $i++) {
                $this->DiguiDb($arr['son'][$i], $pid);
            }
        }
    }

    /**
     * 格式化数组 ，分成层级组数
     * @param $data
     * @return array
     */
    public function generateTree($data)
    {
        $items = array();
        foreach ($data as $v) {
            $items[$v['id']] = $v;
        }
        $tree = array();
        foreach ($items as $k => $item) {
            if (isset($items[$item['pid']])) {
                $items[$item['pid']]['son'][] = &$items[$k];
            } else {
                $tree[] = &$items[$k];
            }
        }
        return $tree;
    }




}