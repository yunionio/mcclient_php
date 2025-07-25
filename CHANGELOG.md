# 变更日志

## [1.1.0] - 2025-07-25

### 新增功能
- 支持镜像预留和上传功能
- 支持镜像下载功能
- 支持从URL复制镜像功能

### 优化功能
- **智能上传方式**: 合并 `upload()` 和 `upload_stream()` 方法，自动检测数据类型
- **内存优化**: 大文件上传时自动使用流式上传，避免内存溢出
- **向后兼容**: 现有代码无需修改，保持完全兼容

### 技术改进
- 使用 `is_resource()` 和 `get_resource_type()` 检测文件句柄
- 使用 cURL 的 `CURLOPT_INFILE` 实现流式上传
- 优化错误处理和资源管理

### 文档更新
- 更新 README.md 说明新的智能上传功能
- 更新 image_upload_guide.md 提供详细使用指南
- 新增 upload_optimization.md 说明优化细节
- 更新示例代码展示最佳实践

### 修复问题
- 修复大文件上传时的内存溢出问题
- 修复镜像预留时的参数处理问题
- 修复数字类型参数转换为字符串的问题

## [1.0.0] - 2025-07-24

### 初始版本
- 基础 Cloudpods API 客户端功能
- 支持认证和会话管理
- 支持基本的资源管理操作 