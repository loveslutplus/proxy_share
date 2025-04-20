<?php

const SUBLINK_FILE = "v2nodes_sublink.txt"; // 订阅链接保存文件名
const NODES_FILE = "v2nodes_nodes.txt"; // 节点保存文件名

// 获取订阅链接（会过期）
function get_sublink()
{
    // 发起请求
    $request = curl_init("https://www.v2nodes.com/");
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($request);
    // 如果请求失败
    if (curl_errno($request)) {
        error_log("v2nodes 订阅链接请求失败" . curl_error($request));
        curl_close($request);
        return false;
    }
    curl_close($request);
    // 截取订阅链接
    preg_match('/data-config="(.*?)"/', $response, $sublink);
    if (!isset($sublink[1])) {
        error_log("v2nodes 订阅链接提取失败");
        return false;
    }
    // 保存订阅链接
    file_put_contents(SUBLINK_FILE, $sublink[1]);
    return $sublink[1];
}

// 从订阅链接获取节点
function get_nodes()
{
    // 判断订阅链接保存的文件是否存在
    if (file_exists(SUBLINK_FILE)) {
        // 如果存在就读取订阅链接
        $sublink = file_get_contents(SUBLINK_FILE);
    } else {
        // 如果不存在就获取订阅链接
        $sublink = get_sublink();
    }
    // 发起请求
    $request = curl_init($sublink);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($request);
    // 如果请求失败
    if (curl_errno($request)) {
        error_log("v2nodes 节点请求失败" . curl_error($request));
        curl_close($request);
        return false;
    }
    curl_close($request);
    // Base64 解码判断订阅链接是否过期
    if (str_contains(base64_decode($response), 'Please%20get%20new%20subscription%20link')) {
        // 如果过期了就获取新的订阅链接
        get_sublink();
        get_nodes();
    } else {
        // 如果没过期就保存节点
        file_put_contents(NODES_FILE, $response);
        return $response;
    }
}

get_nodes();