<?php

use think\exception\HttpResponseException;

/**
 * 媒体分类
 * @return int
 */
function media($id){
    try {
        return \app\Datacenter\model\DataSysMedia::mk()->where(['id'=>$id])->column('name')[0];
    } catch (\Exception $exception) {
        return '-';
    }

}

/**
 * 用户
 * @param $id
 * @return mixed
 */
function username($id){
    try {
        return \think\admin\model\SystemUser::mk()->where(['id'=>$id])->column('nickname')[0];
    } catch (\Exception $exception) {
        return '-';
    }

}

/**
 * 渠道信息
 * @param $id
 */
function channel($id){
    try {
        return \app\Datacenter\model\DataSysChannel::mk()->where(['id'=>$id])->column('name')[0];
    } catch (\Exception $exception) {
        return '-';
    }
}

function pay($id){
    if($id===1){
        return '付费';
    }else{
        return  '免费';
    }
}



