<?php

$type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : "merge_base64"; // 节点获取模式

const SHADOWSHARE_URLS = [ // git 仓库地址，原软件还有几个 GitHub 反代站，这里就不加了
    "https://gitee.com/api/v5/repos/configshare/share/raw/%s?access_token=9019dae4f65bd15afba8888f95d7ebcc&ref=hotfix",
    "https://raw.githubusercontent.com/configshare/share/hotfix/%s",
    "https://shadowshare.v2cross.com/servers/%s",
];

// http 请求封装
function http_get($url)
{
    $request = curl_init($url);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($request);
    // 检查请求是否成功
    if ($response === false) {
        $error = curl_error($request);
        curl_close($request);
        throw new Exception("[cURL] {$url} 请求失败：" . $error);
    }
    curl_close($request);
    return $response;
}

function v2nodes()
{
    // 获取订阅链接
    $response = http_get("https://www.v2nodes.com/");
    try {
        // 截取订阅链接
        if (!preg_match('/data-config="(.*?)"/', $response, $sublink) or !isset($sublink[1])) {
            throw new Exception("订阅链接截取失败");
        }
        // 获取节点
        // v2nodes 默认返回 base64 编码的数据，这里不用判断订阅链接是否过期
        return http_get($sublink[1]);
    } catch (Exception $e) {
        throw new Exception("[v2nodes] 处理失败：" . $e->getMessage());
    }
}

function shadowshare($file)
{
    foreach (SHADOWSHARE_URLS as $url) {
        $response = http_get(sprintf($url, $file));
        try {
            // AES 解密
            $key = "8YfiQ8wrkziZ5YFW";
            $iv = "8YfiQ8wrkziZ5YFW";
            $cipher = "AES-128-CBC";
            $decoded = base64_decode($response, true);
            if ($decoded === false) {
                throw new Exception("Base64 解码失败");
            }
            $result = openssl_decrypt($decoded, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            if ($result === false) {
                throw new Exception("AES 解密失败");
            }
            // 如果成功就直接返回数据
            return $result;
        } catch (Exception $e) {
            // 如果请求失败就记录错误并从下一个接口获取
            $error = $e->getMessage();
            continue;
        }
    }
    // 所有接口都失败了才抛出异常
    throw new Exception("[shadowshare] 所有接口请求均失败：" . $error);
}

function cnc07()
{
    $response = http_get("http://cnc07api.cnc07.com/api/cnc07iuapis");
    try {
        // JSON 解析
        $decoded_initial = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE or !isset($decoded_initial["servers"])) {
            throw new Exception("[cnc07] 初始 JSON 解析失败");
        }
        // AES 解密
        $key = "1kv10h7t*C3f8c@$";
        $iv = "@$6l&bxb5n35c2w9";
        $cipher = "AES-128-CBC";
        $decoded_base64 = base64_decode($decoded_initial["servers"], true);
        if ($decoded_base64 === false) {
            throw new Exception("Base64 解码失败");
        }
        $decrypted = openssl_decrypt($decoded_base64, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            throw new Exception("AES 解密失败");
        }
        // JSON 解析
        $decoded_final = json_decode($decrypted, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("解密后 JSON 解析失败");
        }
        $result = "";
        foreach ($decoded_final as $config) {
            // 正则表达式提取节点，注意 JSON 里的服务器信息是假的，必须截取 alias 里面的
            // $match 的参数依次为 ip, port, method, password
            if (isset($config["alias"]) and preg_match('/SS = ss, ([\\d.]+), (\\d+),encrypt-method=([\\w-]+),password=([\\w\\d]+)/', $config["alias"], $match)) {
                // 原 python 代码直接这样手动拼接了，只考虑到有 shadowsocks 类型的节点，格式为 ss://method:password@ip:port#name
                $result .= "ss://{$match[3]}:{$match[4]}@{$match[1]}:{$match[2]}#{$config['city_cn']} {$config['city']}" . PHP_EOL;
            }
        }
        return $result;
    } catch (Exception $e) {
        throw new Exception("[cnc07] 处理失败：" . $e->getMessage());
    }
}


try {
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
} catch (Exception $e) {
    exit($e->getMessage());
}
