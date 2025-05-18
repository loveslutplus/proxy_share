<?php

$type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : "merge_base64"; // 节点获取模式

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
        error_log("[cURL] {$url} 请求失败 " . curl_error($request) . PHP_EOL);
        curl_close($request);
        return false;
    }
    curl_close($request);
    return $response;
}

function v2nodes()
{
    // 获取订阅链接
    $response = http_get("https://www.v2nodes.com/");
    if ($response === false) {
        error_log("[v2nodes] 获取订阅链接失败" . PHP_EOL);
        return false;
    }
    // 截取订阅链接
    preg_match('/data-config="(.*?)"/', $response, $sublink);
    if (!isset($sublink[1])) {
        error_log("[v2nodes] 订阅链接提取失败" . PHP_EOL);
        return false;
    }
    // 获取节点
    $response = http_get($sublink[1]);
    if ($response === false) {
        error_log("[v2nodes] 获取节点失败，订阅链接: " . $sublink[1] . PHP_EOL);
        return false;
    }
    // 这里不用判断订阅链接是否过期，缓存版的判断见 index.php
    return $response;
}

function shadowshare($file)
{
    foreach (SHADOWSHARE_URLS as $url) {
        $response = http_get(sprintf($url, $file));
        // 如果请求失败就从下一个接口获取
        if ($response === false) {
            error_log("[shadowshare] " . sprintf($url, $file) . " 请求失败" . PHP_EOL);
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
        // 如果成功就直接返回数据
        return $result;
    }
    return false;
}

function cnc07()
{
    $response = http_get("http://cnc07api.cnc07.com/api/cnc07iuapis");
    if ($response === false) {
        error_log("[cnc07] 请求失败" . PHP_EOL);
        return false;
    }
    // JSON 解析
    $decoded = json_decode($response, true);
    if (json_last_error() != JSON_ERROR_NONE) {
        error_log("[cnc07] JSON 解析失败" . PHP_EOL);
        return false;
    }
    // 处理上游 API 返回异常
    if ($decoded["errcode"] != 200 or empty($decoded["servers"])) {
        error_log("[cnc07] 上游 API 返回异常 " . $decoded["errmsg"] . PHP_EOL);
        return false;
    }
    // AES 解密
    $key = "1kv10h7t*C3f8c@$";
    $iv = "@$6l&bxb5n35c2w9";
    $cipher = "AES-128-CBC";
    $decrypted = openssl_decrypt(base64_decode($decoded["servers"]), $cipher, $key, OPENSSL_RAW_DATA, $iv);
    if ($decrypted === false) {
        error_log("[cnc07] AES 解密失败" . PHP_EOL);
        return false;
    }
    // JSON 解析
    $decoded = json_decode($decrypted, true);
    if (json_last_error() != JSON_ERROR_NONE) {
        error_log("[cnc07] JSON 解析失败" . PHP_EOL);
        return false;
    }
    $result = "";
    foreach ($decoded as $config) {
        // 正则表达式提取节点，注意 JSON 里的服务器信息是假的，必须截取 alias 里面的
        // $match 的参数依次为 ip, port, method, password
        preg_match('/SS = ss, ([\\d.]+), (\\d+),encrypt-method=([\\w-]+),password=([\\w\\d]+)/', $config["alias"], $match);
        // 原 python 代码直接这样手动拼接了，只考虑到有 shadowsocks 类型的节点，格式为 ss://method:password@ip:port#name
        $result .= "ss://{$match[3]}:{$match[4]}@{$match[1]}:{$match[2]}#{$config['city_cn']} {$config['city']}" . PHP_EOL;
    }
    return $result;
}


// v2nodes 默认返回数据带 base64 编码
if ($type == "v2nodes_base64") {
    exit(v2nodes());
} elseif ($type == "v2nodes") {
    exit(base64_decode(v2nodes()));
}
// shadowshare 默认返回数据不带 base64 编码
elseif ($type == "shadowshare") {
    exit(shadowshare("shadowshareserver"));
} elseif ($type == "shadowshare_base64") {
    exit(base64_encode(shadowshare("shadowshareserver")));
} elseif ($type == "shadowshare_sub") {
    exit(shadowshare("sub"));
}
// cnc07 默认返回数据不带 base64 编码
elseif ($type == "cnc07") {
    exit(cnc07());
} elseif ($type == "cnc07_base64") {
    exit(base64_encode(cnc07()));
}
// 聚合
elseif ($type == "merge") {
    $v2nodes = base64_decode(v2nodes());
    $shadowshare = shadowshare("shadowshareserver");
    $cnc07 = cnc07();
    // 拼接节点
    exit(implode(PHP_EOL, [$v2nodes, $shadowshare, $cnc07]));
} elseif ($type == "merge_base64") {
    $v2nodes = base64_decode(v2nodes());
    $shadowshare = shadowshare("shadowshareserver");
    $cnc07 = cnc07();
    // 拼接节点
    exit(base64_encode(implode(PHP_EOL, [$v2nodes, $shadowshare, $cnc07])));
}
// shadowshare 普通代理池的 clash 订阅
elseif ($type == "clash_http") {
    exit(shadowshare("clash_http_encrypt"));
} elseif ($type == "clash_https") {
    exit(shadowshare("clash_https_encrypt"));
} elseif ($type == "clash_socks5") {
    exit(shadowshare("clash_socks5_encrypt"));
} else {
    exit("不支持的 type 类型：{$type}");
}
