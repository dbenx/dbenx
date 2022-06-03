<?php

namespace app\work\service;
use think\admin\model\SystemUser;
use think\admin\Service;

class WordOrderService extends  Service
{
    /**
     * 获取提交人数据
     * @return array
     */
    public function getUserData(): array
    {
        $map = ['status' => 1];
        return SystemUser::mk()->where($map)->order('sort desc,id desc')->column('nickname,id');
    }


}