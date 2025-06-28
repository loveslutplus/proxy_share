# 🚀 代理分享
不会PHP，所以重写了一下，具体功能和支持来源看原项目

本仓库包含了从多个来源获取代理服务器配置的两种实现版本：Go 语言版本 (`main.go`) 和 JavaScript 版本 (`worker.js`)，后者可部署在 Cloudflare Workers 上。

懒货直接用我搭建好的worker url：`https://proxyshare.liaoyuan6666.workers.dev/`

## 仓库结构

- `main.go`：Go 语言实现，作为独立程序运行。
- `worker.js`：JavaScript 实现，用于 Cloudflare Workers。

## JavaScript 版本 (`worker.js`)

### 前提条件

- 拥有启用了 Workers 的 Cloudflare 账户。

### 部署

复制worker.js的代码到Cloudflare worker中部署
```
https://your-worker.workers.dev/  # 返回 merge_base64 类型数据
https://your-worker.workers.dev/?force_refresh=true  # 强制刷新缓存并返回最新数据
```

### 示例
```
https://proxyshare.liaoyuan6666.workers.dev/
```
返回 merge_base64 编码的代理配置。


## Go 版本 (`main.go`)

### 前提条件

- 系统上安装了 Go 1.16 或更高版本。

### 使用方法

1. **克隆仓库**
   ```bash
   git clone https://github.com/beck-8/proxy_share
   cd proxy_share
   ```

2. **运行程序**
   设置 `TYPE` 环境变量以指定输出类型，然后运行程序：
   ```bash
   TYPE="merge_base64" go run main.go
   ```
   如果未指定 `TYPE`，则默认为 `merge_base64`。

3. **可用的类型**
   - `v2nodes_base64`：从 v2nodes 获取 base64 编码的数据。
   - `v2nodes`：从 v2nodes 获取解码后的数据。
   - `shadowshare`：从 shadowshare 获取解密后的数据。
   - `shadowshare_base64`：从 shadowshare 获取 base64 编码的数据。
   - `shadowshare_sub`：从 shadowshare 获取订阅数据。
   - `cnc07`：从 cnc07 获取 Shadowsocks URI。
   - `cnc07_base64`：从 cnc07 获取 base64 编码的 Shadowsocks URI。
   - `merge`：合并所有来源的解码数据。
   - `merge_base64`：合并并 base64 编码所有来源的数据。
   - `clash_http`：shadowshare 的 http 普通代理池 clash 格式 yaml 订阅。
   - `clash_https`：shadowshare 的 https 普通代理池 clash 格式 yaml 订阅。
   - `clash_socks5`：shadowshare 的 socks5 普通代理池 clash 格式 yaml 订阅。

### 示例
```bash
# 直接运行
go run main.go

# 选择类型
TYPE="cnc07" go run main.go
```

## 注意事项

- 两个实现都会处理错误，如果请求或处理失败，将返回错误消息。
- Go 版本输出到控制台，而 JavaScript 版本响应 HTTP 请求。
- 确保可以访问外部 API（v2nodes、shadowshare、cnc07）。