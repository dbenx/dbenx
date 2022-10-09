<?php

namespace app\sem\service;

use app\sem\model\SemKeywordsConfig;
use app\sem\model\SemKeywords;
use app\sem\model\SemKeywordsCs;
use app\sem\model\SemRegionConfig;
use app\sem\model\SemUnitConfig;
use app\sem\model\SemUrlsuffixConfig;
use think\admin\Service;

class ParticipleService extends Service
{

    /**
     * @param $id
     * @return array
     * 获取计划词根
     */
    public function getrootword($id): array
    {
        return SemKeywordsConfig::mk()->where(['pid' => $id])->order('sort desc,id')->column('id,pid,title,rootword,match');
    }

    /**
     * 获取单元词根
     * @param $id
     * @return array
     */
    public function getUnit($id): array
    {
        return SemUnitConfig::mk()->where(['pid' => $id])->order('sort desc,id')->column('id,pid,title,rootword,match');
    }

    /**
     * 返回信息面板所要数据
     * @return int[]
     */
    public function total()
    {
        $this->total = ['pid' => 0, 'unit' => 0, 'noall' => 0, 'all' => 0, 'jhnum' => 0, 'unitnum' => 0];
        $this->total['allkeywords'] = SemKeywords::mk()->where(['uid' => session('user.id')])->count('*');
        $this->total['pid'] = SemKeywords::mk()->where(['uid' => session('user.id'), 'pid' => 0])->count('*');
        $this->total['unit'] = SemKeywords::mk()->where(['uid' => session('user.id'), 'unitid' => 0])->where('pid', '<>', 0)->count('id');
        $this->total['noall'] = SemKeywords::mk()->where(['uid' => session('user.id'), 'pid' => 0, 'unitid' => 0])->count('*');
        $this->total['all'] = SemKeywords::mk()->where(['uid' => session('user.id')])->where('pid', '<>', 0)->where('unitid', '<>', 0)->count('*');
        $this->total['jhnum'] = SemKeywords::mk()->field('pid,count(1) total')->where(['uid' => session('user.id')])->where('pid', '<>', 0)->group('pid')->count('*');
        $this->total['unitnum'] = SemKeywords::mk()->field('unitid,count(1) total')->where(['uid' => session('user.id')])->where('pid', '<>', 0)->group('unitid')->count('*');

        return $this->total;
    }

    /**
     * 规则数据
     * @return array
     */
    public function rule()
    {
        $rule = [];
        $rule['kcon'] = SemKeywordsConfig::mk()
            ->where(['pid' => 0])
            ->whereRaw('uid = ' . session('user.id') . '  OR status = 1 AND pid=0')
            ->order('sort desc,id')
            ->column('id,title');
        $rule['units'] = SemUnitConfig::mk()
            ->where(['pid' => 0])
            ->whereRaw('uid = ' . session('user.id') . '  OR status = 1 AND pid=0')
            ->order('sort desc,id')->column('id,title');
        $rule['region'] = SemRegionConfig::mk()
            ->whereRaw('uid = ' . session('user.id') . '  OR status = 1')
            ->order('sort desc,id')
            ->column('id,title');
        $rule['urlsuffix'] = SemUrlsuffixConfig::mk()
            ->whereRaw('uid = ' . session('user.id') . '  OR status = 1')
            ->order('sort desc,id')
            ->column('id,title,url,content');
        return $rule;
    }


}