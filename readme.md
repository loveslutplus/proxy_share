# 🚀 代理分享

自动爬取一些普通的爬虫难以爬取的公共代理节点并整合。

对于普通的代理节点，请前往查看本 README 底部的更多项目。

本项目使用 PHP 编写，任何具有 PHP8+ 且安装了 cURL 扩展的虚拟主机均可部署使用。本项目有缓存版和无缓存版两个版本，可根据需求决定使用哪种版本。

## 📂 支持来源

- **v2nodes:** 来源 [https://www.v2nodes.com/](https://www.v2nodes.com/) 该网站会不定期过期订阅链接，本项目会自动获取最新的订阅链接。
- **shadowshare:** 来源 [https://shadowshare.v2cross.com/](https://shadowshare.v2cross.com/) 的 APP，抓包得到地址，逆向得到 AES 加密密钥。
  - 逆向方法：[使用 blutter 逆向 flutter 制作的安卓软件记录/教程](https://blog.jibukeshi.tech/archives/shi-yong-blutter-ni-xiang-flutter-zhi-zuo-de-an-zhuo-ruan-jian-ji-lu-jiao-cheng)
- **cnc07:** 来源频道分享 python 脚本改编。[Py明文脚本](https://t.me/helloworld_1024/8214)

## ⚡️ 无缓存版

### 特点

- 单文件，无需计划任务
- 客户端每次订阅都会请求源站，如果频繁请求可能会遇到速率限制

### 部署

下载 `no_cache.php` 并上传到 php 主机

### 订阅

客户端订阅链接填写 `http(s)://yourdomain.com/path/to/your/no_cache.php`

可以通过 `type` 参数指定订阅来源，支持的取值如下表。不指定时，默认使用 `merge_base64`。

| `type` 的取值        | 说明                                                              |
| :-------------------- | :---------------------------------------------------------------- |
| `v2nodes_base64`     | www.v2nodes.com，base64 编码输出   |
| `v2nodes`            | www.v2nodes.com，明文输出             |
| `shadowshare`        | shadowshare 的高匿代理池，明文输出                                 |
| `shadowshare_base64` | shadowshare 的高匿代理池，base64 编码输出                           |
| `shadowshare_sub`    | shadowshare 的高匿代理池订阅链接，需要二次订阅                       |
| `cnc07_base64` | cnc07，base64 编码输出 |
| `cnc07` | cnc07，明文输出 |
| `merge` | 聚合上面三个来源，明文输出 |
| `merge_base64` | 聚合上面三个来源，base64 编码输出 |
| `clash_http`         | shadowshare 的 http 普通代理池 clash 格式 yaml 订阅                |
| `clash_https`        | shadowshare 的 https 普通代理池 clash 格式 yaml 订阅               |
| `clash_socks5`       | shadowshare 的 socks5 普通代理池 clash 格式 yaml 订阅              |

## 💾 缓存版

### 特点

- 通过定时任务自动获取节点并保存到主机
- 客户端订阅时从本地缓存读取，响应速度快
- 多人订阅时不会频繁请求源站，适合公共使用

### 部署

1. 下载 `get_nodes.php`, `config.php`,`index.php` 并上传到 php 主机（可以从 Release 页面下载压缩包上传后在线解压）
2. 编辑 `config.php`，设置缓存文件名和 shadowshare 要获取的文件名称
3. 设置定时任务，定时执行 `get_nodes.php` 获取节点并缓存到本地（如果主机不支持定时任务可以配合第三方探针等实现定时执行）（推荐设置每 6 小时执行一次）

### 订阅

一般直接使用 `index.php` 订阅即可，会自动整合两个来源的节点。客户端订阅链接填写 `http(s)://yourdomain.com/path/to/your/index.php`

| 可选参数 | 默认值  | 说明                                   |
| :------- | :------ | :------------------------------------- |
| `base64` | `true`  | base64 编码输出                        |
| `force`  | `false` | 忽略本地缓存，强制从源站获取节点信息并更新缓存 |

另外还可以订阅定时爬取输出的缓存文件，这些可以在 `config.php` 中设置。默认设置如下：

- `v2nodes_sublink.txt`：v2nodes 的订阅链接
- `v2nodes_nodes.txt`：v2nodes 的节点
- `cnc07.txt`：cnc07 的节点
- `sub.txt`：shadowshare 高匿代理池的订阅链接
- `shadowshareserver.txt`：shadowshare 高匿代理池的节点
- `clash_http_encrypt.txt`：shadowshare http 普通代理池的 clash 格式配置文件
- `clash_https_encrypt.txt`：shadowshare https 普通代理池的 clash 格式配置文件
- `clash_socks5_encrypt.txt`：shadowshare socks5 普通代理池的 clash 格式配置文件
- `http_cn_encrypt.txt`：shadowshare http 普通代理池的节点列表（更新不及时）
- `https_cn_encrypt.txt`：shadowshare https 普通代理池的节点列表（更新不及时）
- `socks5_cn_encrypt.txt`：shadowshare socks5 普通代理池的节点列表（更新不及时）

## 🔗 更多项目

- 使用 PHP 编写的 [简易订阅整合工具](https://dev.oneall.eu.org/archives/43/)，支持合并订阅，零散节点，去重，以普通或 base64 格式返回，可搭配本项目使用

其它自动爬取并整合的项目：

- <https://github.com/barry-far/V2ray-Config>
- <https://github.com/peasoft/NoMoreWalls>
- <https://github.com/mfuu/v2ray>
- <https://github.com/Edudotnexx/multi-proxy-config-fetcher>
- <https://github.com/4n0nymou3/multi-proxy-config-fetcher>

其它代理节点分享的项目：

- <https://github.com/roosterkid/openproxylist>
- <https://github.com/ripaojiedian/freenode>
- <https://github.com/aiboboxx/v2rayfree>
- <https://github.com/free18/v2ray>
- <https://github.com/go4sharing/sub>
- <https://github.com/ermaozi01/free_clash_vpn>
- <https://github.com/ssrsub/ssr>
