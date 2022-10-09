<?php

namespace app\Datacenter\controller\reportform;

use app\Datacenter\model\DataSysMedium;
use QL\QueryList;
use think\admin\Controller;

class Center extends Controller
{
    /**
     * 数据中心
     */
    public function index()
    {
        $this->title = '每日报表';
        $columns = DataSysMedium::mk()->where(['deleted' => 0, 'status' => 1])->order('sort')->column('id,network,channel,media,userid,pay,deleted', 'id');
        //媒体分类
        $this->media = array_unique(array_column($columns, 'media'));
        //渠道分类
        $this->channel = array_unique(array_column($columns, 'channel'));
        //账户分类
        $this->user = array_unique(array_column($columns, 'userid'));
        //付费分类
        $this->pay = array_unique(array_column($columns, 'pay'));
        DataSysMedium::mQuery()->where(['deleted' => 0, 'status' => 1])->page('flase');
        $this->fetch();
    }

    /**
     * 初始化当天数据
     */
    public function init()
    {

    }

    // 采集
    public function parsing()
    {
        // $ql = QueryList::get($url);
        $url = 'https://mp.weixin.qq.com/s/IuWRfCUgg6KZURAyLsJ7kw';
        //$ql = QueryList::Query($url);
        // 获取文章内容

        $rules = array(
            'title' => array('.rich_media_title', 'text'),
            'content' => array('.rich_media_content', 'html'),
           // 'img' => array('.rich_pages wxw-img', 'html'),
            'img' => ['img','data-src'],

        );
        $data = QueryList::Query($url, $rules)->data;
        $data[0]['content']=str_replace('data-src','src', $data[0]['content']);
echo $data[0]['content'];








 }



}