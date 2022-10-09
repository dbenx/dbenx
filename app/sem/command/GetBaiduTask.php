<?php

namespace app\sem\command;

use app\work\model\WordBaiduurl;
use app\work\model\WordBaiduzd;
use think\admin\Command;
use think\admin\Exception;
use think\console\Input;
use think\console\Output;

class GetBaiduTask extends Command
{

    protected function configure()
    {
        $this->setName('xsem:GetBaiduTask');
        $this->setDescription('采集百度问题');
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
        $this->allreplylist();
    }

    /**
     * 采集知道伙合人问题
     * @throws Exception
     */
    protected function allreplylist()
    {
        $data = $this->queue->data;
        $uid = $data['uid'];
        $rs = WordBaiduurl::mk()->where(['is_deleted' => 0, 'uid' => $uid, 'status' => 1])->column('id,url,uid');
        list($state, $page,$allnum) = [0, 0,1000];
        foreach ($rs as $zh => $val) {
            $zh = $zh + 1;
            $this->setQueueProgress('任务开始'); // 设置运行进度并继续执行
            if(isset($this->getParams($val['url'])['id'])){
            //知道合伙人
                $url = 'https://zhidao.baidu.com/business/ajax/allreplylist?businessId=' . $this->getParams($val['url'])['id'] . '&pn=' . $state . '&rn=20<br>';
                $rsd = http_get($url);
                $r = json_decode($rsd, true);
                if($r['totalNum']<1000){
                    $allnum=$r['totalNum'];
                }
                while ($state < $allnum) {
                    $url = 'https://zhidao.baidu.com/business/ajax/allreplylist?businessId=' . $this->getParams($val['url'])['id'] . '&pn=' . $state . '&rn=20<br>';
                    $rsd = http_get($url);
                    $r = json_decode($rsd, true);
                    usleep(5000);

                    foreach ($r['data'] as $key => $v) {
                        $this->setQueueProgress("账户{$zh},第" . ($page) . "页，第" . ($state + $key + 1) . "条，标题：{$v['title']}", ($state / 1000) * 100);
                        if (WordBaiduzd::mk()->where('url', 'like', '%' . $v['encode_qid'] . '%')->where(['is_deleted' => 0, 'uid' => $uid])->field('id')->count() > 0) {
                            //   $this->setQueueSuccess('采集到重复信息，任务结束。'.$v['encode_qid']); // 设置成功的消息并结束执行
                        } else {
                            $insdata[] = array(
                                'uid' => $val['uid'],
                                'title' => $v['title'],
                                'robot' => 1,
                                'url' => 'https://zhidao.baidu.com/question/' . $v['encode_qid'] . '.html'
                            );
                        }
                        if (!empty($insdata)) {
                            WordBaiduzd::mk()->insertAll($insdata);
                        }
                        usleep(500);
                        unset($insdata);
                    }
                    $page++;
                    $state = $page * 20;
                }

            }else if(isset($this->getParams($val['url'])['uid'])){

               // $url = 'https://zhidao.baidu.com/business/ajax/allreplylist?businessId=' . $this->getParams($val['url'])['id'] . '&pn=' . $state . '&rn=20<br>';
                $url='https://zhidao.baidu.com/ihome/api/myanswer?pn='.$state.'&rn=20&&uid='.$this->getParams($val['url'])['uid'].'&type=default';
                $rsd = http_get($url);
                $r = json_decode($rsd, true);
                if($r['data']['question']['listNum']<1000){
                    $allnum=$r['data']['question']['listNum'];
                }
                while ($state < $allnum) {
                  //  $url = 'https://zhidao.baidu.com/business/ajax/allreplylist?businessId=' . $this->getParams($val['url'])['id'] . '&pn=' . $state . '&rn=20<br>';
                    $url='https://zhidao.baidu.com/ihome/api/myanswer?pn='.$state.'&rn=20&&uid='.$this->getParams($val['url'])['uid'].'&type=default';
                    $rsd = http_get($url);
                    $r = json_decode($rsd, true);
                    usleep(5000);

                    foreach ($r['data']['question']['list'] as $key => $v) {
                        $this->setQueueProgress("账户{$zh},第" . ($page) . "页，第" . ($state + $key + 1) . "条，标题：{$v['title']}", ($state / 1000) * 100);
                        if (WordBaiduzd::mk()->where('url', 'like', '%' . $v['qid'] . '%')->where(['is_deleted' => 0, 'uid' => $uid])->field('id')->count() > 0) {
                            //   $this->setQueueSuccess('采集到重复信息，任务结束。'.$v['encode_qid']); // 设置成功的消息并结束执行
                        } else {
                            $insdata[] = array(
                                'uid' => $val['uid'],
                                'title' => $v['title'],
                                'robot' => 1,
                                'url' => 'https://zhidao.baidu.com/question/' . $v['qid'] . '.html'
                            );
                        }
                        if (!empty($insdata)) {
                            WordBaiduzd::mk()->insertAll($insdata);
                        }
                        usleep(500);
                        unset($insdata);
                    }
                    $page++;
                    $state = $page * 20;
                }
            }else{
                $this->setQueueProgress("url 设置错误！");
            }
            $this->setQueueProgress("账户{$zh}数据采集已完成!");
            usleep(2000);
        }

        $this->setQueueSuccess('本次任务结束!'); // 设置成功的消息并结束执行

    }

    /**
     * 采集个人知道问题
     */
    protected  function myanswer(){


    }

    protected function getParams($url)
    {
        $refer_url = parse_url($url);
        $params = $refer_url['query'];
        $arr = array();
        if (!empty($params)) {
            $paramsArr = explode('&', $params);
            foreach ($paramsArr as $k => $v) {
                $a = explode('=', $v);
                $arr[$a[0]] = $a[1];
            }
        }
        return $arr;
    }

}