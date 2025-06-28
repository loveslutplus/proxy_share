<?php

$base64 = isset($_REQUEST["base64"]) ? $_REQUEST["base64"] == "true" : true; // 使用 base64 编码输出, 默认 true
$force = isset($_REQUEST["force"]) ? $_REQUEST["force"] == "true" : false; // 强制更新节点

require_once("config.php"); // 导入配置

// 如果节点没被获取或者需要强制更新
if (!file_exists(V2NODES_NODES_FILE) or !file_exists(SHADOWSHARE_NODES_FILE) or !file_exists(CNC07_FILE) or $force) {
    require("get_nodes.php");
}

// 读取并解码 v2nodes 节点
$v2nodes = base64_decode(file_get_contents(V2NODES_NODES_FILE));
// 读取 shadowshare 节点
$shadowshare = file_get_contents(SHADOWSHARE_NODES_FILE);
// 读取 cnc07 节点
$cnc07 = file_get_contents(CNC07_FILE);
// 拼接节点
$result = implode(PHP_EOL, [$v2nodes, $shadowshare, $cnc07]);

exit($base64 ? base64_encode($result) : $result);