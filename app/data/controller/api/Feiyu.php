<?php

namespace app\data\controller\api;

use app\data\model\DataFrom;
use app\data\service\FeiYuService;
use think\admin\Controller;
use think\cache\driver\Redis;
use app\data\model\DataConfigApi;

class Feiyu extends Controller
{
    public $zhid;
    /*
     * 手动拉取数据
     */
    public  function getData($id=0){
        $end_time = date("Y-m-d H:i:s");//当前时间;
        $this->zhid=$id;
        $res=DataConfigApi::mk()->where(["id"=>$id])->find()->toArray();
        //$start_time = date("Y-m-d H:i:s",strtotime("-".$res['cycledata']." hours"));//获取当前事件前多少时间的数据
        $start_time = date("Y-m-d H:i:s",strtotime("-".$res['cycledata']." days"));//获取当前事件前多少时间的数据
        $Feiyu=new FeiYuService([
            'host' => 'https://feiyu.oceanengine.com',
            'pull_route' => '/crm/v2/openapi/pull-clues/',
            'push_route' => '/crm/v2/openapi/clue/callback/',
            'signature_key' => $res['secret'],
            'token' => $res['token'],
            ]
        );
       $Feiyu->pullData($start_time, $end_time, 100)->run(function($customers){

            // 这里是一个闭包，会在取完一整页的数据后执行
            foreach ($customers as $customer) {
                $rsid= DataFrom::mk()->where(["phone"=>$customer['telphone'],"create_time"=>$customer['create_time']])->count();
                if($rsid==0){
                    $data=array(
                        'code'=>$customer['clue_id'],
                        'name' => $customer['name'],
                        'phone' => $customer['telphone'],
                        'create_time' => $customer['create_time'],
                        'wechat' => $customer['weixin'],
                        'source' => $customer['appname'],
                        'url' => $customer['external_url'],
                        'zhid' =>$this->zhid,
                        'system_tags' => $customer['system_tags'],
                        'address' => $customer['location']
                    );
                    DataFrom::mk()->insert($data);
                }
            }
        });
       $this->success('数据拉取成功！');
    }
}