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
    /**
     * 每日数据报表
     */
    public function index()
    {
        $this->init($this->request->get('mid'));
        DataDayBasic::mQuery()->layTable(function () {
            $this->title = '每日数据报表';
        }, function (QueryHelper $query) {
            $query->dateBetween('time');
        });
    }

    protected function _index_page_filter(&$data)
    {
        $weekarray = array("日", "一", "二", "三", "四", "五", "六");
        foreach ($data as &$vo) {
            $vo['time'] = date("Y-m-d", $vo['time']) . "  星期" . $weekarray[date("w", $vo['time'])];
        }
    }

    public function init($mid)
    {
        $time = strtotime(date('Y-m-d'));
        if (DataDayBasic::mk()->where(['time'=>$time])->count('id') == 0) {
            DataDayBasic::mk()->insert([
                'time'=>$time,
                'mid'=>$mid
            ]);
        }
    }


    public  function  up(){

       // var_dump($this->request->param());

       DataDayBasic::update([$this->request->param('field')=>$this->request->param('value')],['id'=>$this->request->param('id')]);
       sysoplog('更新日报','更新日报告成功');

    }


}