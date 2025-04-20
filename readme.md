# 代理分享

自动爬取公共代理节点并整合。

v2nodes：来源 https://www.v2nodes.com/

shadowshare：来源 https://shadowshare.v2cross.com/ 的 APP，抓包得到地址，逆向得到 AES 加密密钥。  
逆向方法：[使用 blutter 逆向 flutter 制作的安卓软件记录/教程](https://blog.jibukeshi.tech/archives/shi-yong-blutter-ni-xiang-flutter-zhi-zuo-de-an-zhuo-ruan-jian-ji-lu-jiao-cheng)

## 部署教程

- 下载所有 php 文件并上传到 php 主机。
- 配置自动更新：使用定时任务执行 `v2nodes.php` 和 `shadowshare.php` 以自动爬取节点。
- 当脚本执行完成后会自动下载节点信息。

## 订阅教程

如无特殊情况直接使用 `index.php` 订阅即可，会自动整合两个来源的节点。

GET 请求可选参数：
- `base64`：是否使用 base64 编码输出, 默认 `true`
- `force`：是否强制更新一次代理节点（不推荐，如果频繁请求源站可能会被拉黑，建议使用定时任务自动爬取并在本地缓存，如果你的主机不支持定时任务而且用的人少可以在客户端里将它设置成 `true`

定时爬取还会输出其它文件，拿它们订阅也是可以的：
- `v2nodes_sublink.txt`：v2nodes 的订阅链接
- `v2nodes_nodes.txt`：v2nodes 
- `sub.txt`：shadowshare 高匿代理池的订阅链接
- `shadowshareserver.txt`：shadowshare 高匿代理池的节点
- `clash_http_encrypt.txt`：shadowshare http 普通代理池的 clash 格式配置文件
- `clash_https_encrypt.txt`：shadowshare https 普通代理池的 clash 格式配置文件
- `clash_socks5_encrypt.txt`：shadowshare socks5 普通代理池的 clash 格式配置文件
- `http_cn_encrypt.txt`：hadowshare http 普通代理池的节点列表（更新不及时）
- `https_cn_encrypt.txt`：hadowshare https 普通代理池的节点列表（更新不及时）
- `socks5_cn_encrypt.txt`：hadowshare socks5 普通代理池的节点列表（更新不及时）