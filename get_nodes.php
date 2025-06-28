<?php

require_once("config.php"); // 导入配置

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


// v2nodes 部分
function v2nodes_sublink()
{
    // 获取订阅链接
    $response = http_get("https://www.v2nodes.com/");
    try {
        // 截取订阅链接
        if (!preg_match('/data-config="(.*?)"/', $response, $sublink) or !isset($sublink[1])) {
           throw new Exception("订阅链接截取失败");
        }
        return $sublink[1];
    } catch (Exception $e) {
        throw new Exception("[v2nodes] 订阅链接获取失败：" . $e->getMessage());
    }
}

try {
    // 如果订阅链接保存的文件不存在或者文件为空，就获取订阅链接
    if (!file_exists(V2NODES_SUBLINK_FILE) or filesize(V2NODES_SUBLINK_FILE) === 0) {
        file_put_contents(V2NODES_SUBLINK_FILE, v2nodes_sublink());
    }
    // 获取节点
    $v2nodes =  http_get(file_get_contents(V2NODES_SUBLINK_FILE));
    // Base64 解码判断订阅链接是否过期
    $decoded = base64_decode($v2nodes, true);
    if ($decoded === false or str_contains($decoded, 'Please%20get%20new%20subscription%20link')) {
        // 如果过期了就获取新的订阅链接
        file_put_contents(V2NODES_SUBLINK_FILE, v2nodes_sublink());
        $v2nodes = http_get(file_get_contents(V2NODES_SUBLINK_FILE));
    }
    // 写入文件
    file_put_contents(V2NODES_NODES_FILE, $v2nodes);
} catch (Exception $e) {
    error_log("[v2nodes] 节点获取失败：" . $e->getMessage());
}


// shadowshare 部分
foreach (SHADOWSHARE_FILES as $file) {
    $success = false;
    $error = "";
    foreach (SHADOWSHARE_URLS as $url) {
        try {
            $response = http_get(sprintf($url, $file));
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
            // 如果成功就写入文件并停止后续循环
            file_put_contents(sprintf(SHADOWSHARE_FILE, $file), $result);
            $success = true;
            break;
        } catch (Exception $e) {
            // 如果请求或解密失败就记录错误并从下一个接口获取
            $error = $e->getMessage();
            continue;
        }
    }
    // 如果所有接口都失败了才抛出异常
    if (!$success) {
        throw new Exception("[shadowshare] 所有接口请求均失败：" . $error);
    }
}

// cnc07 部分
$response = http_get("http://cnc07api.cnc07.com/api/cnc07iuapis");
try {
    // JSON 解析
    $decoded_initial = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE or !isset($decoded_initial["servers"])) {
        throw new Exception("初始 JSON 解析失败");
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
        throw new Exception("[cnc07] 解密后 JSON 解析失败");
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
    // 写入文件
    file_put_contents(CNC07_FILE, $result);
} catch (Exception $e) {
    throw new Exception("[cnc07] 处理失败：" . $e->getMessage());
}
