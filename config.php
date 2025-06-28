<?php

const V2NODES_SUBLINK_FILE = "v2nodes_sublink.txt"; // v2nodes 订阅链接保存文件名
const V2NODES_NODES_FILE = "v2nodes_nodes.txt"; // v2nodes 节点保存文件名

const SHADOWSHARE_FILE = "%s.txt"; // shadowshare 保存文件名，用 %s 代替不同的文件
const SHADOWSHARE_NODES_FILE = "shadowshareserver.txt"; // shadowshare 高匿代理池的节点保存文件名
const SHADOWSHARE_FILES = [ // shadowshare 要获取的文件名称
    "clash_http_encrypt",
    "clash_https_encrypt",
    "clash_socks5_encrypt",
    "shadowshareserver",
    "sub",
    "http_cn_encrypt",
    "https_cn_encrypt",
    "socks5_cn_encrypt",
];

const CNC07_FILE = "cnc07.txt"; // cnc07 保存文件名