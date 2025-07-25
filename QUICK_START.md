# 快速开始指南

## 项目概述

Cloudpods PHP SDK 提供了访问 Cloudpods API 的完整 PHP 客户端库，支持镜像管理、计算资源管理等功能。

## 快速开始

### 1. 环境要求

- PHP >= 7.4
- 必要的 PHP 扩展：curl, json, mbstring, openssl

### 2. 基本使用

```php
<?php
include_once("src/client.php");
include_once("src/tokenv3.php");
include_once("src/session.php");
include_once("src/services/image/images.php");

// 创建客户端
$client = new Client("https://your-cloudpods-endpoint/v3");

// 认证
$token = $client->auth("Default", "username", "password", "", "project", "Default");

// 获取会话
$session = $client->get_session($token, "public", "region");

// 使用镜像管理
$image_manager = new ImageManager();
$images = $image_manager->list_items($session, array());
?>
```

### 3. 镜像上传示例

```php
// 镜像预留
$reserve_params = array(
    "name" => "my-image",
    "disk_format" => "qcow2",
    "container_format" => "bare"
);
$reserved_image = $image_manager->create($session, $reserve_params);

// 文件流上传（推荐用于大文件）
$file_handle = fopen("/path/to/image.qcow2", 'rb');
$uploaded_image = $image_manager->upload($session, $upload_params, $file_handle, $file_size);
fclose($file_handle);
```

## 代码质量检查

### 本地检查

```bash
# 运行完整的静态分析
./scripts/static-analysis.sh

# 只检查语法
find src/ examples/ -name "*.php" -exec php -l {} \;
```

### GitHub Actions

项目配置了自动化的代码质量检查：

- **触发条件**: 推送到 main/master/develop 分支或创建 Pull Request
- **检查工具**: PHPStan, PHP_CodeSniffer, 语法检查
- **PHP 版本**: 7.4, 8.0, 8.1, 8.2

### 检查内容

- ✅ PHP 语法检查
- ✅ 代码风格检查 (PSR-12)
- ✅ 静态分析 (PHPStan Level 3)
- ✅ 基础安全检查
- ✅ 调试代码检测

## 项目结构

```
mcclient_php/
├── src/                    # 核心源代码
│   ├── client.php         # 主客户端类
│   ├── session.php        # 会话管理
│   ├── tokenv3.php        # Token 管理
│   └── services/          # 服务模块
│       ├── image/         # 镜像管理
│       ├── compute/       # 计算资源
│       └── ...
├── examples/              # 使用示例
├── scripts/               # 工具脚本
│   └── static-analysis.sh # 静态分析脚本
├── docs/                  # 文档
└── .github/workflows/     # GitHub Actions
```

## 主要功能

### 镜像管理
- ✅ 镜像预留和上传
- ✅ 智能上传方式（自动检测文件句柄）
- ✅ 镜像下载
- ✅ 从 URL 复制镜像

### 计算资源管理
- ✅ 虚拟机管理
- ✅ 服务器管理
- ✅ 网络管理

### 其他功能
- ✅ 认证和会话管理
- ✅ 错误处理
- ✅ 内存优化

## 开发指南

### 代码规范
- 遵循 PSR-12 标准
- 使用有意义的变量和函数名
- 添加适当的注释
- 避免使用调试代码

### 提交前检查
1. 运行 `./scripts/static-analysis.sh`
2. 修复所有错误和警告
3. 确保代码通过所有检查

### 错误处理
- 使用异常而不是 `die()` 或 `exit()`
- 提供有意义的错误信息
- 正确处理资源清理

## 故障排除

### 常见问题

1. **内存溢出**: 使用文件流上传而不是 `file_get_contents()`
2. **认证失败**: 检查认证参数和网络连接
3. **权限问题**: 确保有足够的 API 权限

### 调试技巧

```php
// 启用调试模式
$client = new Client($auth_url, 300, true, true);

// 查看详细错误信息
try {
    $result = $image_manager->upload($session, $params, $file_handle, $size);
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "状态码: " . $e->getCode() . "\n";
}
```

## 贡献指南

1. Fork 项目
2. 创建功能分支
3. 运行代码质量检查
4. 提交 Pull Request

## 许可证

Apache License 2.0

## 支持

- 文档: [docs/](docs/)
- 示例: [examples/](examples/)
- 问题反馈: GitHub Issues 