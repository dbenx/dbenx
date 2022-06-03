<?php

namespace app\sem\service;

use app\sem\model\SemKeywordsConfig;
use app\sem\model\SemKeywords;
use app\sem\model\SemRegionConfig;
use app\sem\model\SemUnitConfig;
use think\admin\Service;

class UnitConfigService extends Service
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
     * 单元匹配
     * @param $id
     * @throws \think\db\exception\DbException
     */
    public function Umatching($id)
    {
        $kon = ParticipleService::instance()->getUnit($id);
        //匹配模式
        if (is_array($kon)) {
            foreach ($kon as $key => $val) {
                //如果词根存在，把匹配的词循环出来
                $query = SemKeywords::mQuery()->where(['deleted' => 0, 'uid' => session('user.id')]);
                if ($val['rootword']) {
                    $match = $val['match'];//匹配规则
                    $words = explode(PHP_EOL, $val['rootword']);
                    $arr = array();
                    if ($match === 1) {
                        foreach ($words as $v) {
                            array_push($arr, '%' . $v . '%');
                        }
                        $map[] = ['keywords', 'like', $arr, 'or'];
                    } elseif ($match === 2) {
                        foreach ($words as $v) {
                            array_push($arr, '%' . $v . '%');
                        }
                        $map[] = ['keywords', 'notlike', $arr, 'or'];
                    } elseif ($match === 3) {
                        foreach ($words as $v) {
                            $map[] = ['keywords', '=', $v, 'or'];
                        }
                    } elseif ($match === 4) {
                        foreach ($words as $v) {
                            $map[] = ['keywords', '<>', $v];
                        }
                    } elseif ($match === 5) {
                        foreach ($words as $v) {
                            array_push($arr, $v . '%');
                        }
                        $map[] = ['keywords', 'like', $arr, 'or'];
                    } elseif ($match === 6) {
                        foreach ($words as $v) {
                            array_push($arr, '%' . $v);
                        }
                        $map[] = ['keywords', 'like', $arr, 'or'];
                    }
                    $query->where(['unitid' => 0]);
                    $query->where('pid', '<>', 0);
                    $query->where($map)->update(['unitid' => $val['id']]);
                    unset($map);
                }
                #echo SemKeywords::getLastSql();
                $this->Umatching($val['id']);
            }
        }
    }

    /**
     * 计划匹配
     * @param $id
     * @throws \think\db\exception\DbException
     */
    public function matching($id)
    {
        $kon = ParticipleService::instance()->getrootword($id);
        //匹配模式
        if (is_array($kon)) {
            foreach ($kon as $key => $val) {
                //如果词根存在，把匹配的词循环出来
                $query = SemKeywords::mQuery()->where(['deleted' => 0, 'uid' => session('user.id')]);
                if ($val['rootword']) {
                    $match = $val['match'];//匹配规则
                    $words = explode(PHP_EOL, $val['rootword']);
                    $arr = array();
                    if ($match === 1) {
                        foreach ($words as $v) {
                            array_push($arr, '%' . $v . '%');
                        }
                        $map[] = ['keywords', 'like', $arr, 'or'];
                    } elseif ($match === 2) {
                        foreach ($words as $v) {
                            array_push($arr, '%' . $v . '%');
                        }
                        $map[] = ['keywords', 'notlike', $arr, 'or'];
                    } elseif ($match === 3) {
                        foreach ($words as $v) {
                            $map[] = ['keywords', '=', $v, 'or'];
                        }
                    } elseif ($match === 4) {
                        foreach ($words as $v) {
                            $map[] = ['keywords', '<>', $v];
                        }
                    } elseif ($match === 5) {
                        foreach ($words as $v) {
                            array_push($arr, $v . '%');
                        }
                        $map[] = ['keywords', 'like', $arr, 'or'];
                    } elseif ($match === 6) {
                        foreach ($words as $v) {
                            array_push($arr, '%' . $v);
                        }
                        $map[] = ['keywords', 'like', $arr, 'or'];
                    }
                    if ($val['pid'] == input('id')) {
                        $query->where(['pid' => 0]);
                        $query->where($map)->update(['pid' => $val['id']]);
                    } else {
                        $query->where(['pid' => $val['pid']]);
                        $query->where($map)->update(['pid' => $val['id']]);
                    }
                    unset($map);
                }
                #  echo SemKeywords::getLastSql();
                $this->matching($val['id']);
            }
        }
    }

    public function Ratching($regionid)
    {
        $regionalword = SemRegionConfig::mk()->where(['id' => $regionid])->column('regionalwords,wregionalwords')[0];
        $regionalwords = explode(PHP_EOL, $regionalword['regionalwords']);
        $arr = array();
        foreach ($regionalwords as $val) {
            array_push($arr, '%' . $val . '%');
        }
        $map[] = ['keywords', 'like', $arr, 'or'];
        $query = SemKeywords::mQuery();
        $query->where($map)->update(['regionid' => $regionid]);
    }


    /**
     * 匹配模式 数字改成文字
     * @param $num
     */
    public function MatchNumtoStr($num)
    {
        switch ($num) {
            case 1:
                return '包含';
            case 2:
                return '不包含';
            case 3:
                return '等于';
            case 4:
                return '不等于';
            case 5:
                return '开始于';
            case 6:
                return '截止于';
            default:
                return '';
        }
    }



}