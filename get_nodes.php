<?php

require_once("config.php"); // 导入配置

const SHADOWSHARE_URLS = [ // git 仓库地址，原软件还有几个 GitHub 反代站，这里就不加了
    "https://gitee.com/api/v5/repos/configshare/share/raw/%s?access_token=9019dae4f65bd15afba8888f95d7ebcc&ref=hotfix",
    "https://raw.githubusercontent.com/configshare/share/hotfix/%s",
    "https://shadowshare.v2cross.com/servers/%s",
];

function http_get($url)
{
    $request = curl_init($url);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($request);
    if (curl_errno($request)) {
        error_log("[cURL] {$url} 请求失败 " . curl_error($request));
        curl_close($request);
        return false;
    }
    curl_close($request);
    return $response;
}


// v2nodes 部分
function v2nodes_sublink()
{
    // 获取订阅链接
    $response = http_get("https://www.v2nodes.com/");
    if ($response === false) {
        error_log("[v2nodes] 获取订阅链接失败");
        return false;
    }
    // 截取订阅链接
    preg_match('/data-config="(.*?)"/', $response, $sublink);
    if (!isset($sublink[1])) {
        error_log("[v2nodes] 订阅链接提取失败");
        return false;
    }
    return $sublink[1];
}
// 如果订阅链接保存的文件不存在就获取订阅链接
if (!file_exists(V2NODES_SUBLINK_FILE)) {
    file_put_contents(V2NODES_SUBLINK_FILE, v2nodes_sublink());
}
// 获取节点
$v2nodes =  http_get(file_get_contents(V2NODES_SUBLINK_FILE));
// Base64 解码判断订阅链接是否过期
if (str_contains(base64_decode($v2nodes), 'Please%20get%20new%20subscription%20link')) {
    // 如果过期了就获取新的订阅链接
    file_put_contents(V2NODES_SUBLINK_FILE, v2nodes_sublink());
    $v2nodes = http_get(file_get_contents(V2NODES_SUBLINK_FILE));
}
// 写入文件
file_put_contents(V2NODES_NODES_FILE, $v2nodes);

// shadowshare 部分
foreach (SHADOWSHARE_FILES as $file) {
    foreach (SHADOWSHARE_URLS as $url) {
        // 获取节点
        $response = http_get(sprintf($url, $file));
        // 如果请求失败就从下一个接口获取
        if ($response === false) {
            error_log("[shadowshare] " . sprintf($url, $file) . " 请求失败");
            continue;
        }
        // AES 解密
        $key = "8YfiQ8wrkziZ5YFW";
        $iv = "8YfiQ8wrkziZ5YFW";
        $cipher = "AES-128-CBC";
        $result = openssl_decrypt(base64_decode($response), $cipher, $key, OPENSSL_RAW_DATA, $iv);
        // 如果 AES 解密失败也从下一个接口获取
        if ($result === false) {
            error_log("[shadowshare] {$file} AES 解密失败");
            continue;
        }
        // 如果成功就写入文件并停止后续循环
        file_put_contents(sprintf(SHADOWSHARE_FILE, $file), $result);
        break;
    }
}
