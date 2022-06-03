<?php

namespace app\sem\command;
use app\sem\model\SemKeywords;
use app\sem\model\SemKeywordsConfig;
use app\sem\model\SemRegionConfig;
use app\sem\model\SemUnitConfig;
use app\sem\service\ParticipleService;
use think\admin\Command;
use think\admin\Exception;
use  think\session;
use think\console\Input;
use think\console\Output;

class ParticipleTask extends Command
{
    protected function configure()
    {
        $this->setName('xsem:ParticipleTask');
        $this->setDescription('批量清理商城订单数据');
    }

    /**
     * 业务指令执行
     * @param Input $input
     * @param Output $output
     * @return void
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        $data = $this->queue->data;
        if (empty($data['jhid'])) $this->setQueueError("参数计划无效，请传入正确的参数!");
        if (empty($data['unitid'])) $this->setQueueError("参数单元无效，请传入正确的参数!");
        $start_time = microtime(true);
        $this->setQueueProgress("正在分配计划");
        $this->_matching($data['jhid']);

        $this->setQueueProgress("计划分分配完成，开始分配单元");

        $this->_Umatching($data['unitid']);

        if (!empty($data['regionid'])) {
            $this->setQueueProgress("单元分分配完成，开始分配地域".$data['regionid']);
            $this->_Ratching($data['regionid']);
        }

        $end_time = microtime(true);
        $execution_time = sprintf("%.2f", ($end_time - $start_time));
        $total['allkeywords'] = SemKeywords::mk()->where(['uid' => $data['uid']])->count('id');
        $total['jhnum'] = SemKeywords::mk()->field('pid,count(1) total')->where(['uid' => $data['uid']])->where('pid', '<>', 0)->group('pid')->count('pid');
        $total['unitnum'] = SemKeywords::mk()->field('unitid,count(1) total')->where(['uid' => $data['uid']])->where('pid', '<>', 0)->group('unitid')->count('unitid');
        $total['noall'] = SemKeywords::mk()->where(['uid' => $data['uid'], 'pid' => 0, 'unitid' => 0])->count('id');

        $this->setQueueSuccess("分词已完成：本次共计用时间：{$execution_time} 秒，共计{$total['allkeywords']}个词，匹配账户计划 {$total['jhnum']} 个<br>匹配单元 {$total['unitnum']} 个,还剩下 {$total['noall']} 个关键词未分配,<font color='red'>请手动刷新页面查看结果</font>");
    }

    /**
     * 分配计划
     * @param $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _matching($id)
    {
        $data = $this->queue->data;
        $temuid=$data['jhid'];
        $uid=$data['uid'];
        $kon = ParticipleService::instance()->getrootword($id);
        [$count, $total] = [0, ($result = SemKeywordsConfig::mk()->where(['pid' => $id])->select())->count()];
       // $this->queue->message($total, ++$count, "开始分配计划：" . $id);
        // sleep(2);
        //匹配模式
        if (is_array($kon)) {
            foreach ($kon as $key => $val) {
                //如果词根存在，把匹配的词循环出来
                $query = SemKeywords::mQuery()->where(['deleted' => 0, 'uid' => $uid]);
                if ($val['rootword']) {
                    $this->setQueueProgress("正在分配计划" . $val['title']);
                    //  sleep(2);
                    $this->queue->message($total, ++$count, "开始分配计划：" . $val['title']);
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
                    if ($val['pid'] == $temuid) {
                        $query->where(['pid' => 0]);
                        $query->where($map)->update(['pid' => $val['id']]);
                    } else {
                        $query->where(['pid' => $val['pid']]);
                        $query->where($map)->update(['pid' => $val['id']]);
                    }
                    unset($map);
                }
                //   $this->setQueueProgress(SemKeywords::getLastSql());
                #  echo SemKeywords::getLastSql();
              //  $this->setQueueProgress("sql" .SemKeywords::getLastSql());
                $this->_matching($val['id']);
            }
        }
    }

    /**
     * 单元匹配
     * @param $id
     * @throws \think\db\exception\DbException
     */
    protected function _Umatching($id)
    {
        $data = $this->queue->data;
        $uid=$data['uid'];
        $kon = ParticipleService::instance()->getUnit($id);
        [$count, $total] = [0, ($result = SemUnitConfig::mk()->where(['pid' => $id])->select())->count()];
        //匹配模式
        if (is_array($kon)) {
            foreach ($kon as $key => $val) {
                //如果词根存在，把匹配的词循环出来
                $query = SemKeywords::mQuery()->where(['deleted' => 0, 'uid' => $uid]);
                $this->setQueueProgress("正在分配单元" . $val['title']);
                //  sleep(2);
                $this->queue->message($total, ++$count, "开始分配单元：" . $val['title']);
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
                $this->_Umatching($val['id']);
            }
        }
    }

    /**
     * 地域分配
     * @param $regionid
     * @throws \think\db\exception\DbException
     */
    protected  function _Ratching($regionid){
        $data = $this->queue->data;
        $uid=$data['uid'];
        $regionalword=SemRegionConfig::mk()->where(['id'=>$regionid])->column('regionalwords,wregionalwords')[0];
        $regionalwords = explode(PHP_EOL, $regionalword['regionalwords']);
        $arr = array();
        foreach ($regionalwords as $val){
            array_push($arr, '%' . $val . '%');
        }
        $map[] = ['keywords', 'like', $arr, 'or'];
        $query = SemKeywords::mQuery()->where(['deleted' => 0, 'uid' => $uid]);
        $query->where($map)->update(['regionid' => $regionid]);
    }
}