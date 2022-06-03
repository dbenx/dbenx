<?php

use app\data\command\OrderClean;
use app\data\command\UserAgent;
use app\data\command\UserAmount;
use app\data\command\UserTransfer;
use app\data\command\UserUpgrade;
use app\sem\command\ParticipleTask;
use app\data\command\SumAdd;
use app\data\service\OrderService;
use app\data\service\RebateService;
use app\data\service\UserBalanceService;
use app\data\service\UserRebateService;
use think\Console;

$app = app();

if ($app->request->isCli()) {
    // 分词任务
    Console::starting(function (Console $console) {
        $console->addCommand(ParticipleTask::class);
    });
} else {
    // 分词任务
    $app->event->listen('ShopOrderConfirm', function ($orderNo) use ($app) {
        $app->log->notice("订单 {$orderNo} 确认事件，执行返利确认行为");
        UserRebateService::instance()->confirm($orderNo);
    });
}
