# PHP 静态代码分析

本项目配置了完整的 PHP 静态代码分析工具，帮助确保代码质量和一致性。

## 工具概览

### 1. GitHub Actions 自动化检查
- **触发条件**: 推送到 main/master/develop 分支或创建 Pull Request
- **PHP 版本**: 支持 7.4, 8.0, 8.1, 8.2
- **检查工具**: PHPStan, PHP_CodeSniffer, 语法检查, 基础检查

### 2. 本地检查脚本
- **脚本位置**: `scripts/static-analysis.sh`
- **功能**: 语法检查, 基础检查, 安全检查
- **输出**: 彩色输出，易于阅读

## 本地使用

### 运行本地检查

```bash
# 运行完整的静态分析
./scripts/static-analysis.sh

# 或者直接运行 PHP 语法检查
find src/ examples/ -name "*.php" -exec php -l {} \;
```

### 安装工具（可选）

如果你想在本地运行更详细的检查：

```bash
# 安装 PHPStan
wget https://github.com/phpstan/phpstan/releases/latest/download/phpstan.phar
chmod +x phpstan.phar
sudo mv phpstan.phar /usr/local/bin/phpstan

# 安装 PHP_CodeSniffer
composer global require squizlabs/php_codesniffer

# 运行 PHPStan
phpstan analyse

# 运行 PHP_CodeSniffer
phpcs --standard=PSR12 src/ examples/
```

## 检查内容

### 1. 语法检查
- PHP 语法错误
- 文件格式问题
- 基本语法验证

### 2. 代码风格检查
- PSR-12 标准
- 行长度限制（120字符）
- 命名规范
- 代码格式

### 3. 静态分析
- 未定义变量
- 未定义方法
- 类型错误
- 潜在问题

### 4. 安全检查
- 危险函数检测（eval, exec, system）
- 硬编码路径检查
- 调试代码检测

### 5. 基础检查
- 未闭合的引号
- 未闭合的括号
- 缺少分号
- 文件结构

## 配置说明

### PHPStan 配置 (phpstan.neon)
```yaml
parameters:
  level: 3                    # 检查级别 (0-9)
  paths:
    - src/                    # 检查目录
    - examples/
  excludePaths:
    - src/vendor/            # 排除目录
  ignoreErrors:              # 忽略的错误模式
    - '#Call to an undefined method#'
    - '#Access to an undefined property#'
```

### PHP_CodeSniffer 配置 (phpcs.xml)
```xml
<ruleset name="Cloudpods PHP SDK">
  <file>src/</file>
  <file>examples/</file>
  <rule ref="PSR12"/>                    <!-- PSR-12 标准 -->
  <rule ref="Generic.Files.LineLength">  <!-- 行长度限制 -->
    <properties>
      <property name="lineLimit" value="120"/>
    </properties>
  </rule>
</ruleset>
```

## 常见问题

### 1. 忽略特定错误
在 `phpstan.neon` 中添加 `ignoreErrors` 规则：
```yaml
ignoreErrors:
  - '#Specific error message#'
```

### 2. 排除特定文件
在 `phpcs.xml` 中添加排除规则：
```xml
<exclude-pattern>*/specific-file.php</exclude-pattern>
```

### 3. 自定义规则
可以修改配置文件来适应项目需求：
- 调整行长度限制
- 修改检查级别
- 添加自定义规则

## 最佳实践

### 1. 开发流程
1. 在本地运行 `./scripts/static-analysis.sh`
2. 修复发现的问题
3. 提交代码
4. GitHub Actions 自动检查

### 2. 代码质量
- 遵循 PSR-12 标准
- 避免使用危险函数
- 及时清理调试代码
- 保持代码简洁

### 3. 持续改进
- 定期检查分析报告
- 根据项目需求调整配置
- 培训团队成员使用工具

## 故障排除

### 1. 脚本权限问题
```bash
chmod +x scripts/static-analysis.sh
```

### 2. PHP 扩展缺失
确保安装必要的 PHP 扩展：
```bash
# Ubuntu/Debian
sudo apt-get install php-curl php-json php-mbstring php-openssl

# CentOS/RHEL
sudo yum install php-curl php-json php-mbstring php-openssl
```

### 3. 工具安装失败
如果工具安装失败，可以跳过详细检查，只运行基础检查：
```bash
# 只运行语法检查
find src/ examples/ -name "*.php" -exec php -l {} \;
```

## 贡献指南

1. 在提交代码前运行静态分析
2. 修复所有错误和警告
3. 如果某些警告是误报，在配置中忽略
4. 保持代码质量标准的连续性

通过使用这些工具，我们可以确保代码质量，减少错误，提高开发效率。 