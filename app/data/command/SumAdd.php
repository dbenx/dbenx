<?php

namespace app\data\command;
use think\admin\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use app\data\model\DataFrom;

class SumAdd extends Command
{

    protected function configure(){
        $this->setName('xdata:SumAdd');
        $this->addArgument('uuid', Argument::OPTIONAL, '目标用户', '');
        $this->addArgument('puid', Argument::OPTIONAL, '上级代理', '');
        $this->setDescription('每10秒钟自加10');
    }

    protected function execute(Input $input, Output $output)
    {
        $dd=$this->queue->data;
        $this->setQueueSuccess($dd['uuid']);
        //$this->setQueueSuccess("此次共处理1个刷新操作, 其中有1个失败。");
        [$uuid, $puid] = [$input->getArgument('uuid'), $input->getArgument('puid')];
        if (empty($uuid)) $this->setQueueError("参数UID无效，请传入正确的参数!");
        if (empty($puid)) $this->setQueueError("参数PID无效，请传入正确的参数!");

        $arr = array(
            130,131,132,133,134,135,136,137,138,139,
            144,147,
            150,151,152,153,155,156,157,158,159,
            176,177,178,
            180,181,182,183,184,185,186,187,188,189,
        );

        $phonenum = $arr[array_rand($arr)].mt_rand(1000,9999).mt_rand(1000,9999);

       // [$count, $total] = [0, ($result = DataGd::mk()->where($map)->select())->count()];
       // $data[] = [ 'phone'=>$phonenum];
     //   $rs=DataFrom::mk()->insertall($data);
      //  [$count, $total] = [0, $rs];

        $url='http://192.168.2.77/plus/baoming.php';
        $data=array(
            "name"=>'aa',
            'phone'=>$phonenum
        );
        http_post($url,$data);

        $this->queue->message(1, 1, "当前自加10", 1);
        $this->setQueueSuccess("此次共处理1个刷新操作, 其中有1个失败。");

    }

}