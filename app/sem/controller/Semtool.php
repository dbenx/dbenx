<?php

namespace app\sem\controller;

use think\admin\Controller;

/**
 * 关键词组合工具
 */
class Semtool extends Controller
{
    public function index()
    {
        $this->title = '关键词组合工具';
        if($this->request->isPost()){
            $this->keyword1 = $this->request->param('keyword1');
            $this->keyword2 = $this->request->param('keyword2');
            $this->keyword3 = $this->request->param('keyword3');
            $this->keyword4 = $this->request->param('keyword4');
            $words1 = explode(PHP_EOL, $this->request->param('keyword1'));
            $words2 = explode(PHP_EOL, $this->request->param('keyword2'));
            $words3 = explode(PHP_EOL, $this->request->param('keyword3'));
            $words4 = explode(PHP_EOL, $this->request->param('keyword4'));
            $kwzh ='';
            foreach ($words1 as $v1) {
                foreach ($words2 as $v2) {
                    foreach ($words3 as $v3) {
                        foreach ($words4 as $v4) {
                              $kwzh =$kwzh.$v1 . $v2 . $v3 . $v4.PHP_EOL;
                            //sleep(2);
                        }
                    }
                }
            }
            $this->keywordjg = $kwzh;
        }
            $this->fetch();


    }

    /**
     * 关键词组合
     */
    public function keywordzh()
    {
        $this->keyword1 = $this->request->param('keyword1');
        $this->keyword2 = $this->request->param('keyword2');
        $this->keyword3 = $this->request->param('keyword3');
        $this->keyword4 = $this->request->param('keyword4');
        $words1 = explode(PHP_EOL, $this->request->param('keyword1'));
        $words2 = explode(PHP_EOL, $this->request->param('keyword2'));
        $words3 = explode(PHP_EOL, $this->request->param('keyword3'));
        $words4 = explode(PHP_EOL, $this->request->param('keyword4'));
        $kwzh = array();
        foreach ($words1 as $v1) {
            foreach ($words2 as $v2) {
                foreach ($words3 as $v3) {
                    foreach ($words4 as $v4) {
                        $kwzh[] = $v1 . $v2 . $v3 . $v4;
                    }
                }
            }
        }
        $this->fetch('index');
    }

    public  function  columntorow(){
        $this->fetch();
    }
}