<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2021 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免费声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\admin\controller;

use Exception;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\model\SystemQueue;
use think\admin\service\AdminService;
use think\admin\service\ProcessService;
use think\admin\service\QueueService;
use think\exception\HttpResponseException;

/**
 * 系统任务管理
 * Class Queue
 * @package app\admin\controller
 */
class Queue extends Controller
{
    /**
     * 系统任务管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        SystemQueue::mQuery()->layTable(function () {
            $this->title = '系统任务管理';
            $this->iswin = ProcessService::instance()->iswin();
            // 超级管理面板
            if ($this->isSuper = AdminService::instance()->isSuper()) {
                $process = ProcessService::instance();
                if ($process->iswin() || empty($_SERVER['USER'])) {
                    $this->command = $process->think('xadmin:queue start');
                } else {
                    $this->command = "sudo -u {$_SERVER['USER']} {$process->think('xadmin:queue start')}";
                }
            }
            // 任务状态统计
            $this->total = ['dos' => 0, 'pre' => 0, 'oks' => 0, 'ers' => 0];
            SystemQueue::mk()->field('status,count(1) count')->group('status')->select()->map(function ($item) {
                if ($item['status'] === 1) $this->total['pre'] = $item['count'];
                if ($item['status'] === 2) $this->total['dos'] = $item['count'];
                if ($item['status'] === 3) $this->total['oks'] = $item['count'];
                if ($item['status'] === 4) $this->total['ers'] = $item['count'];
            });
        }, function (QueryHelper $query) {
            $query->equal('status')->like('code,title,command');
            $query->timeBetween('enter_time,exec_time')->dateBetween('create_at');
        });
    }

    /**
     * 重启系统任务
     * @auth true
     */
    public function redo()
    {
        try {
            $data = $this->_vali(['code.require' => '任务编号不能为空！']);
            $queue = QueueService::instance()->initialize($data['code'])->reset();
            $queue->progress(1, '>>> 任务重置成功 <<<', 0.00);
            $this->success('任务重置成功！', $queue->code);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 清理运行数据
     * @auth true
     */
    public function clean()
    {
        $this->_queue('定时清理系统运行数据', "xadmin:queue clean", 0, [], 0, 3600);
    }

    /**
     * 删除系统任务
     * @auth true
     */
    public function remove()
    {
        SystemQueue::mDelete();
    }
    /**
     * 获取信息
     * @auth true
     */
    public function getdata()
    {
        $this->_queue('定时获取抖音数据', "xdata:GetfeiyuData", 0, [], 0, 600);
    }

    public  function  dbenx(){
        $data=array('uuid'=>1000,'puid'=>2);
        $this->_queue('测试任务', "xdata:SumAdd", 0, $data, 0, 0);


       // $url='http://127.0.0.1:315/push/receiveBodyRecord';
      //  $data = array( "mobile" =>'13523262514', "nickName"=>'aa');
    //  $dd=  $this->postcurl($data,$url);
     // var_dump($dd);
    }



}
