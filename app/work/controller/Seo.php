<?php

namespace app\work\controller;

use app\work\model\SeoArticle;
use app\work\model\SeoArticleData;
use think\admin\Controller;

class Seo extends Controller
{
    public function index()
    {
        if ($this->request->isPost()) {
            $titles = explode(PHP_EOL, $this->request->param('title'));
            foreach ($titles as $vo) {
                $data=array(
                    'title'=>$vo,//标题
                    'cid'=>1,//栏目ID
                    'related'=>'',
                    'postype'=>1,
                );
               $id=SeoArticle::mk()->insertGetId($data);
               $body=array(
                   'aid'=>$id,
                   'body'=>str_replace("{title}", $vo, $this->request->param('body'))
               );
               SeoArticleData::mk()->insert($body);
            }
        } else {
            $this->fetch();
        }
    }
}
