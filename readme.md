# 代理分享

自动爬取公共代理节点并整合。

本项目有缓存版和无缓存版两个版本，具体要用哪种看你的需求。

v2nodes：来源 https://www.v2nodes.com/
shadowshare：来源 https://shadowshare.v2cross.com/ 的 APP，抓包得到地址，逆向得到 AES 加密密钥。  
逆向方法：[使用 blutter 逆向 flutter 制作的安卓软件记录/教程](https://blog.jibukeshi.tech/archives/shi-yong-blutter-ni-xiang-flutter-zhi-zuo-de-an-zhuo-ruan-jian-ji-lu-jiao-cheng)

## 无缓存版

### 特点

- 单文件，无需计划任务
- 适合功能极少的虚拟主机
- 客户端每次订阅都会请求源站，如果频繁请求可能会遇到速率限制

### 部署

下载 `no_cache.php` 并上传到 php 主机

### 订阅

客户端订阅链接填写 `http(s)://yourdomain.com/path/to/your/no_cache.php`

可以通过 `type` 参数指定订阅来源，支持的取值如下表。不指定时，默认使用 `merge_base64`。

| `type` 的取值 | 说明 |
| --- | --- |
| v2nodes_base64 | www.v2nodes.com，base64 编码输出 |
| v2nodes | www.v2nodes.com，明文输出 |
| shadowshare | shadowshare 的高匿代理池，明文输出 |
| shadowshare_base64 | shadowshare 的高匿代理池，base64 编码输出 |
| shadowshare_sub | shadowshare 的高匿代理池订阅链接，需要二次订阅 |
| merge | 聚合上面两个来源，明文输出 |
| merge_base64 | 聚合上面两个来源，base64 编码输出 |
| clash_http | shadowshare 的 http 普通代理池 clash 格式 yaml 订阅 |
| clash_https | shadowshare 的 https 普通代理池 clash 格式 yaml 订阅 |
| clash_socks5 | shadowshare 的 socks5 普通代理池 clash 格式 yaml 订阅 |


## 缓存版

### 特点

- 通过定时任务自动获取节点并保存到主机（需要你的主机支持设置定时任务）
- 客户端订阅时从本地缓存读取，响应速度快
- 多人订阅时不会频繁请求源站，适合公共使用

### 部署

1. 下载 `get_nodes.php`, `config.php`,`index.php` 并上传到 php 主机
2. 编辑 `config.php`，设置缓存文件名和 shadowshare 要获取的文件名称
3. 设置定时任务，定时执行 `get_nodes.php` 获取节点并缓存到本地（推荐设置每 6 小时执行一次）

### 订阅

一般直接使用 `index.php` 订阅即可，会自动整合两个来源的节点。客户端订阅链接填写 `http(s)://yourdomain.com/path/to/your/index.php`

| 可选参数 | 默认值 | 说明 |
| --- | --- | --- |
| base64 | true | base64 编码输出 |
| force | false | 忽略本地缓存，强制从源站获取节点信息并更新缓存 |

另外还可以订阅定时爬取输出的缓存文件，这些可以在 `config.php` 中设置。默认设置如下：
- `v2nodes_sublink.txt`：v2nodes 的订阅链接
- `v2nodes_nodes.txt`：v2nodes 的节点
- `sub.txt`：shadowshare 高匿代理池的订阅链接
- `shadowshareserver.txt`：shadowshare 高匿代理池的节点
- `clash_http_encrypt.txt`：shadowshare http 普通代理池的 clash 格式配置文件
- `clash_https_encrypt.txt`：shadowshare https 普通代理池的 clash 格式配置文件
- `clash_socks5_encrypt.txt`：shadowshare socks5 普通代理池的 clash 格式配置文件
- `http_cn_encrypt.txt`：hadowshare http 普通代理池的节点列表（更新不及时）
- `https_cn_encrypt.txt`：hadowshare https 普通代理池的节点列表（更新不及时）
- `socks5_cn_encrypt.txt`：hadowshare socks5 普通代理池的节点列表（更新不及时）