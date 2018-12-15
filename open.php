<?php
include 'config.php';
include 'Medoo.php';
use Medoo\Medoo;
include 'Telegram.php';
$database = new medoo($config);
$telegram = new Telegram($bot_token);

$data = file_get_contents('http://f.apiplus.net/pl3-1.json');
$data = json_decode($data,true);
$opencode = explode(",",$data['data'][0]['opencode']);
$expect = $data['data'][0]['expect'];
$lastopen = $database->get("lottery", [
    "expect",
    "opencode"
], [
    "id" => 1
]);
$opendates = $lastopen["expect"] - 1;
if($opendates == $expect and $lastopen["opencode"] == 0){
$data = $database->select("game", [
    "id",
    "user",
    "project",
    "type",
    "result",
    "data"
], [
    "expect" => $expect
]);
$database->insert("lottery", [
    "expect" => $expect,
    "opencode" => $opencode[0]
]);
if ($opencode[0]%2 == 0) {
    $result1 = '双';
} else {
    $result1 = '单';
}
if ($opencode[0] >= 5) {
    $result2 = '大';
} else {
    $result2 = '小';
}
//流量输掉的奖池
$data1 = $database->sum("game","data", [
    "expect" => $expect,
    "project[!]" => [$result1,$result2],
    "type" => '流量',
    "result" => '未开奖'
]);
$data2 = $database->sum("game","data", [
    "expect" => $expect,
    "project" => [$result1,$result2],
    "type" => '流量',
    "result" => '未开奖'
]);
//余额输掉的奖池
$data3 = $database->sum("game","data", [
    "expect" => $expect,
    "project[!]" => [$result1,$result2],
    "type" => '余额',
    "result" => '未开奖'
]);
$data4 = $database->sum("game","data", [
    "expect" => $expect,
    "project" => [$result1,$result2],
    "type" => '余额',
    "result" => '未开奖'
]);
$text = '本次开奖数字：'.$opencode[0].'
流量奖池共 '.$data1.' M 倍率：'.round($data1/$data2,2).'
余额奖池共 '.$data3.' 元倍率：'.round($data3/$data4,2).'
';
foreach ($data as $datas) {
  if ($datas['result'] == '未开奖') {
    if ($datas['project'] == $result1 OR $datas['project'] == $result2) {
        $result = '已中奖';
        if ($datas['type'] == '余额') {
            $rrr1 = round(($datas['data']/$data4)*$data3,2); 
            $rrr = $datas['data'] + $rrr1;
            $database->update("user", [
                "money[+]" => $rrr
            ], [
                "im_value" => $datas['user']
            ]);
        } elseif ($datas['type'] == '流量') {
            $rrr1 = ceil(($datas['data']/$data2)*$data1);
            $rrr = ($datas['data'] + $rrr1)*1024*1024;
            $database->update("user", [
                "transfer_enable[+]" => $rrr
            ], [
                "im_value" => $datas['user']
            ]);
        }
        $text = $text.'
@'.$datas['user'].' 第'.$expect.'期彩票已中奖，获得'.$datas['type'].' '.$rrr1;

    } else {
        $text = $text.'

@'.$datas['user'].' 第'.$expect.'期彩票未中奖';
        $result = '未中奖';
    }
    $database->update("game", [
        "result" => $result
    ], [
        "id" => $datas['id']
    ]);
}
  }
    $content = array('chat_id' => '@liangchenyunss', 'text' => $text);
    $telegram->sendMessage($content);
    $content = array('chat_id' => '@lcykj', 'text' => $text);
    $telegram->sendMessage($content);
    $database->update("lottery", [
                "opencode" => 1
            ], [
                "id" => 1
            ]);
}
echo 'success';
?>