<?php

namespace app\sem\command;

use app\sem\service\XlsToolsService;
use think\admin\Command;
use think\admin\Exception;
use think\console\Input;
use think\console\Output;

class XlsToolsTask extends Command
{

    protected function configure()
    {
        $this->setName('xsem:XlsToolsTask');
        $this->setDescription('导出大数据XLS');
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

        XlsToolsService::instance()->start([
            'title' => ['列名1', '列名2'],//列名
            'type' => 'xls', //导出的excel的类型
            'name' => '测试1000W导出' //导出的excel的文件名
        ]);


        for ($i = 0; $i < 1000; $i++) {
            $row = ['列值1' => 1, '列值2' => 'x'];
            $this->setQueueProgress("第{$i}页面本次共计100000要任务"); // 设置运行进度并继续执行
            XlsToolsService::instance()->multiData($row);
            XlsToolsService::instance()->end();
        }

    }


}