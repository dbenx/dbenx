<?php

namespace app\Datacenter\controller\reportform;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use app\Datacenter\model\DataDayBasic;


/**
 * 每个账用户 每日数数据汇总
 */
class DayBasic extends Controller
{
    public function index()
    {


        DataDayBasic::mQuery()->layTable(function () {
            $this->title='每日数据报表';
        }, function (QueryHelper $query) {
            $query->dateBetween('create_at');
        });

    }

    public function json()
    {
        //  echo json_encode(DataDayBasic::mk()->select()->toArray());
       $this->success('获取分类成功', DataDayBasic::mk()->select()->toArray(), 0);
    }

}