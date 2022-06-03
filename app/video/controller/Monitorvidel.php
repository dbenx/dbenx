<?php

namespace app\video\controller;
use think\admin\Controller;
use QL\QueryList;
/**
 * 监控视频列表
 */
class Monitorvidel extends Controller
{
    /**
     * 监控列表
     */
    public  function videllist(){

        $ql = QueryList::get('https://www.ithome.com/html/discovery/358585.htm');

        $rt = [];
// 采集文章标题
        $rt['title'] = $ql->find('h1')->text();
// 采集文章作者
        $rt['author'] = $ql->find('#author_baidu>strong')->text();
// 采集文章内容
        $rt['content'] = $ql->find('.post_content')->html();

        print_r($rt);
    }


}