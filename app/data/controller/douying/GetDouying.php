<?php
namespace app\data\controller\Douying;
use think\admin\Controller;
use think\admin\extend\QueryList;
class GetDouying extends Controller
{
     public function index()
    {
        #$pattern = array("title" => array(".news_main .black", "text"),"pinglun"=>array(".news_main p","text"),"href" => array(".black a", "href"));
        #$url = "https://www.szxdfpr.com/about/news/";
       $pattern = array("title" => array(".Uvaas5kD div", "text"),"pinglun"=>array(".RHiEl2d8 .VD5Aa1A1","text"),"href" => array(".nEg6zlpW a", "href"));
        $url = "https://v.douyin.com/NLhk1vb/";
        $qy = new QueryList;
        $qy->QueryList($url, $pattern, '', '', 'utf-8');
        $rs = $qy->jsonArr;
        var_dump($rs);
    }

}