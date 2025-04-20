<?php

const GIT_URL = [ // git 仓库地址，原软件还有几个 GitHub 反代站，这里就不加了
    "https://gitee.com/api/v5/repos/configshare/share/raw/%s?access_token=9019dae4f65bd15afba8888f95d7ebcc&ref=hotfix",
    "https://raw.githubusercontent.com/configshare/share/hotfix/%s",
    "https://shadowshare.v2cross.com/servers/%s",
];
const GIT_FILE = [ // 要获取的文件名称
    "clash_http_encrypt",
    "clash_https_encrypt",
    "clash_socks5_encrypt",
    "shadowshareserver",
    "sub",
    "http_cn_encrypt",
    "https_cn_encrypt",
    "socks5_cn_encrypt",
];

// 获取文件
function get_file($file)
{
    foreach (GIT_URL as $url) {
        // 发起请求
        $request = curl_init(sprintf($url, $file));
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($request);
        // 如果请求失败
        if (curl_errno($request)) {
            error_log(sprintf($url, $file) . " 请求失败 " . curl_error($request));
            curl_close($request);
            continue;
        }
        curl_close($request);
        // AES 解密
        $key = "8YfiQ8wrkziZ5YFW";
        $iv = "8YfiQ8wrkziZ5YFW";
        $cipher = "AES-128-CBC";
        $result = openssl_decrypt(base64_decode($response), $cipher, $key, OPENSSL_RAW_DATA, $iv);
        // 保存订阅链接
        file_put_contents("{$file}.txt", $result);
        return $result;
    }
    return false;
}

foreach (GIT_FILE as $file) {
    get_file($file);
}
