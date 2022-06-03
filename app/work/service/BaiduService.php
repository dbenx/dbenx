<?php

namespace app\work\service;

use app\work\model\WordBaiduzd;
use QL\QueryList;
use think\admin\Service;
use function MongoDB\BSON\fromJSON;

class BaiduService extends Service
{


    public function updatazhidao($id)
    {
        [$title, $data, $username] = ['链接已删除', array(), ''];
        $data = $this->GetData($this->GetZhidaourl($id));
        if (!empty($data)) $title = $data[0]['title'];

        if (!empty($data[0]['ask'])) {
            foreach ($data as &$vo) {
                $username = $vo['ask'].' T:'.$vo['time'] . '|' . $username;
            }
        }
        if (!empty($data)) $data = json_encode($data);
        return WordBaiduzd::mk()->where(['id' => $id])->update(['title' => $title, 'content' => $data, 'username' => $username]);
    }
    /**
     * 获取答案
     * @param $id
     * @return array
     */
    public function upask($id)
    {
        $data = $this->GetData($this->GetZhidaourl($id));
        if (isset($data[0]['ask'])) {
            return $data;
        }
    }

    /**
     * 通过传递的URL 获取百度知道标题与回答内容
     * @param $url 传过来的url
     */
    private  function GetData($url)
    {
        $rules = array(
            'title' => array('.ask-title', 'text'),
            'ask' => array('.wgt-replyer-all-uname', 'text'),
            'content' => array('.content', 'text'),
            'time'=>array('.wgt-replyer-all-time','text')
        );
        $data = QueryList::Query($url, $rules, '', 'UTF-8', 'GB2312')->data;
        #$data = QueryList::Query($url, $rules)->data;
        if (!empty($data)) {
            return $data;
        } else {
            return false;
        }
    }

    /**
     * 通过传递的ID，查询出url
     * @param $id
     * @return url
     */
    public function GetZhidaourl($id)
    {
        return WordBaiduzd::mk()->where(['id' => $id])->column('url')[0];
    }

    //post请求
    function post($url,$post,$header=[]){
        if(empty($header)){
            $header['Content-type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_HEADER, false);
        //curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        //curl_setopt($ch,CURLOPT_HEADER,true);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
       // curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        //curl_setopt($ch,CURLOPT_COOKIESESSION,true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36');//来路

        $output = curl_exec($ch);
        $aStatus = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);
   // echo "<pre>";
   var_dump($error);
       return $output;
        //return json_decode($output,true);
    }

    public function curlPost($url, $post_data = array(), $timeout = 5, $header = "", $data_type = "") {
        $header = empty($header) ? '' : $header;
        //支持json数据数据提交
        if($data_type == 'json'){
            $post_string = json_encode($post_data);
        }elseif($data_type == 'array') {
            $post_string = $post_data;
        }elseif(is_array($post_data)){
            $post_string = http_build_query($post_data, '', '&');
        }

        $ch = curl_init();    // 启动一个CURL会话
        curl_setopt($ch, CURLOPT_URL, $url);     // 要访问的地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 对认证证书来源的检查   // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        //curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($ch, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);     // Post提交的数据包
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);     // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        //curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // 获取的信息以文件流的形式返回
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
        $result = curl_exec($ch);

        // 打印请求的header信息
        //$a = curl_getinfo($ch);
        //var_dump($a);

        curl_close($ch);
        return $result;
    }



}