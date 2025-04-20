<?php

const V2NODES_FILE = "v2nodes_nodes.txt";
const SHADOWSHARE_FILE = "shadowshareserver.txt";

$base64 = isset($_REQUEST["base64"]) ? $_REQUEST["base64"] == 'true' : true; // 使用 base64 编码输出, 默认 true
$force = $_REQUEST["force"]; // 强制更新节点

// 如果 v2nodes 节点没被获取或者需要强制更新
if (!file_exists(V2NODES_FILE) or $force) {
    require("v2nodes.php");
}
// 读取并解码 v2nodes 节点
$v2nodes = base64_decode(file_get_contents(V2NODES_FILE));

// 如果 shadowshare 节点没被获取或者需要强制更新
if (!file_exists(SHADOWSHARE_FILE) or $force) {
    require("shadowshare.php");
}
// 读取 shadowshare 节点
$shadowshare = file_get_contents(SHADOWSHARE_FILE);

// 拼接节点
$merged = $v2nodes . PHP_EOL . $shadowshare;
exit($base64 ? base64_encode($merged) : $merged);
