<?php

namespace app\work\controller;

use app\work\model\WordSemWechat;
use app\work\service\WXBizDataCryptService;
use think\admin\Controller;
use think\db\Where;

class Wechat extends Controller
{
    protected $appid = 'wxad5177e1459563c8';
    protected $secret = 'a35eede1eadfb9d114837909bc34b0f9';

    public function index()
    {
        if ($this->request->isPost()) {
            WordSemWechat::mForm('index');
        }
        $this->Wechat = WordSemWechat::mk()->where(['id' => 0])->column('id,headimg')[0];
        WordSemWechat::mQuery()->where('id', '>', 0)->order('id desc')->page();
    }

    /**
     * 生成url
     */
    public function UrllinkGenerate()
    {
        $access_token = $this->getaccessToken();
        //$url = 'https://api.weixin.qq.com/wxa/generate_urllink?access_token='.$access_token;
        $url = "https://api.weixin.qq.com/wxa/generatescheme?access_token=" . $access_token;
        $path = 'pages/index/index';
        $scene = 'url='.$this->request->param('url');
        $post_data1 = [
            'path' => $path,
            'query' => $scene,
        ];
        $post_data = [
            'jump_wxa' => [
                'path' => $path,
                'query' => $scene,
                'env_version' => 'trial'
            ],
            'is_expire' => true,
            //'expire_time' => 1642780800
        ];
     //   var_dump($post_data);
        $post_data = json_encode($post_data);
        return http_post($url, $post_data);
    }

    public function cs()
    {
        $this->fetch();
    }


    public function getaccessToken()
    {
        if (!isset($_COOKIE['accessToken'])) {
            $data = array(
                'grant_type' => 'client_credential',
                'appid' => $this->appid,
                'secret' => $this->secret
            );
            //$url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->secret;
            $url = 'https://api.weixin.qq.com/cgi-bin/token';
            $res = json_decode(http_get($url, $data), true);
            if (isset($res['access_token'])) {
                setcookie('accessToken', $res['access_token'], 7000);
                return $res['access_token'];
            }
        } else {
            return cookie('accessToken');
        }
    }

    /**
     * 记录授权登录小程序的微信用户
     */
    public function insteruserinfo()
    {
        $userinfo = $this->request->param('userinfo');
        var_dump($userinfo);
        $code = $this->request->param('code');
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$this->appid&secret=$this->secret&js_code=$code&grant_type=authorization_code";
        $res = json_decode(http_get($url), true);

        if (isset($res['openid'])) {
            $data = [
                'openid' => $res['openid'],
                'headimg' => $userinfo['avatarUrl'],
                'gender' => $userinfo['gender'],
                'nickname' => $userinfo['nickName'],
                'address' => $userinfo['country'] . ' ' . $userinfo['city'],
                'path'=>$this->request->param('url'),
            ];
            if (!WordSemWechat::mk()->where(['openid' => $res['openid']])->count('openid')) {
                WordSemWechat::mk()->insert($data);
            }
        }
    }


    public function GetUserPhonenumber()
    {
        $obj = $this->request->param('obj');
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$this->appid&secret=$this->secret&js_code={$obj['code']}&grant_type=authorization_code";
        $sessionKeyrs = json_decode(http_get($url), true);
        $sessionKey = $sessionKeyrs['session_key'];
        $encryptedData = $obj['encryptedData'];
        $iv = $obj['iv'];
        $pc = new WXBizDataCryptService($this->appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            $rs = json_decode($data, true);
            if (WordSemWechat::mk()->where(['phone' => $rs['phoneNumber']])->count('phone') > 0) {
                return 201;
            } else {
                WordSemWechat::mQuery()->where(['openid' => $sessionKeyrs['openid']])->update(['phone' => $rs['phoneNumber']]);
                return 0;
            }
        } else {
            return $errCode;
        }
    }

    /**
     * 获取微信二微码
     */
    public function getWechat()
    {
        return WordSemWechat::mk()->where(['id' => 0])->column('headimg')[0];
    }

}