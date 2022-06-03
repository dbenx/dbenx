<?php

namespace app\data\command;

use app\data\model\DataConfigApi;
use app\data\model\DataFrom;
use app\data\service\FeiYuService;
use think\admin\Command;
use think\admin\Exception;
use think\console\Input;
use think\console\Output;


/*
 * 获取接口中数据定时任务
 */

class GetfeiyuData extends Command
{
    public $zhid;
    private $error = 0;
    private $total = 0;

    protected function configure()
    {
        $this->setName('xdata:GetfeiyuData');
        $this->setDescription('获取配置接口中的数据');
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
        if (empty($data['id'])) $this->setQueueError("参数ID无效，请传入正确的参数!");
        $this->zhid = $data['id'];
        $this->_getData($data['id']);
    }

    private function _getData($id = 0)
    {
        $end_time = date("Y-m-d H:i:s");//当前时间;
        // $id=  $this->request->Get('id');
        $res = DataConfigApi::mk()->where(["id" => $id])->find()->toArray();
        $start_time = date("Y-m-d H:i:s", strtotime("-" . $res['cycledata'] . " hours"));//获取当前事件前多少时间的数据
        //$start_time = date("Y-m-d H:i:s",strtotime("-".$res['cycledata']." days"));//获取当前事件前多少时间的数据

        $Feiyu = new FeiYuService([
                'host' => 'https://feiyu.oceanengine.com',
                'pull_route' => '/crm/v2/openapi/pull-clues/',
                'push_route' => '/crm/v2/openapi/clue/callback/',
                'signature_key' => $res['secret'],
                'token' => $res['token'],
            ]
        );

        $Feiyu->pullData($start_time, $end_time, 100)->run(function ($customers) {
            try {
                foreach ($customers as $customer) {
                    $rsid = DataFrom::mk()->where(["phone" => $customer['telphone'], "create_time" => $customer['create_time']])->count();
                    if ($rsid == 0) {
                        $data[] = array(
                            'code' => $customer['clue_id'],
                            'name' => $customer['name'],
                            'phone' => $customer['telphone'],
                            'create_time' => $customer['create_time'],
                            'wechat' => $customer['weixin'],
                            'source' => $customer['appname'],
                            'url' => $customer['external_url'],
                            'system_tags' => $customer['system_tags'],
                            'zhid' => $this->zhid,
                            'address' => $customer['location']
                        );
                    }
                }
                if (!empty($data)) {
                    $this->total = DataFrom::mk()->insertAll($data) + $this->total;
                }
            } catch (\Exception $exception) {
                $this->error++;
                $this->queue->message(1, 1, "处理出错", 1);
            }

        });
        $this->setQueueSuccess("本次共更新 {$this->total} 条信息, 其中有 {$this->error} 个刷新失败。");

    }
}