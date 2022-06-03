<?php

$res = [
    'code' => 0,
    'msg'  => '成功',
    'count'=> 6,
    'data' => [],
];

$e = $_GET['pid'] ?? 0;
$pid = $e;

$dbData = [

];

$length = 50;
for($i=1; $i<=$length; $i++) {
	$id = $e * $length + $i;
	$dbData[] = ['id'=>$id, 'pid'=>$pid, 'username'=>'张三', 'age'=>20, 'sign'=>'你好,这是我的签名'];
}

$res['data'] = $dbData;
$res = re($res);
die($res);

function re($res) {
    $json = json_encode($res, JSON_UNESCAPED_UNICODE);
    return $json;
}