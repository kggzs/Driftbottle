# 升级指南

本文档提供从旧版本升级到新版本的详细步骤和注意事项。

## 升级前准备

1. **备份数据**
   ```bash
   # 备份数据库
   mysqldump -u driftbottle_user -p driftbottle > backup_$(date +%Y%m%d).sql
   
   # 备份文件
   tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/web/driftbottle
   ```

2. **查看当前版本**
   - 查看 `CHANGELOG.md` 了解当前版本信息
   - 检查数据库版本（如果有版本记录表）

## 升级步骤

### 通用升级流程

1. **下载新版本代码**
   ```bash
   # 如果使用 Git
   git pull origin main
   
   # 或下载新版本 ZIP 包并解压
   ```

2. **执行数据库更新脚本**
   - 根据升级版本，执行相应的 SQL 脚本
   - 脚本位于 `sql/` 目录

3. **更新配置文件**
   - 检查 `includes/config.php` 是否有新增配置项
   - 对比新旧版本的配置文件差异

4. **清除缓存**
   - 清除浏览器缓存
   - 清除服务器缓存（如果有）

5. **测试功能**
   - 测试主要功能是否正常
   - 检查后台管理功能
   - 验证用户功能

## 版本升级指南

### 升级到 v1.2.0（用户等级系统）

**新增功能：**
- 用户等级系统
- 经验值获取和等级计算
- 等级显示和经验条

**数据库更新：**
```bash
mysql -u driftbottle_user -p driftbottle < sql/add_user_level_system.sql
```

**主要变更：**
- `users` 表新增 `experience` 和 `level` 字段
- 新增 `experience_history` 表
- 新增经验值相关 API 端点

### 升级到 v1.1.0（语音漂流瓶）

**新增功能：**
- 语音漂流瓶录制和播放
- 语音文件上传和管理

**数据库更新：**
```bash
mysql -u driftbottle_user -p driftbottle < sql/add_voice_bottle_fields.sql
```

**目录权限：**
```bash
mkdir -p uploads/audio
chmod 777 uploads/audio
```

**主要变更：**
- `bottles` 表新增 `bottle_type`、`audio_file`、`audio_duration` 字段
- 新增 `upload_audio` API 端点

### 升级到 v1.0.2（系统设置）

**新增功能：**
- 系统设置数据库存储
- 后台系统设置页面
- VIP 积分配置

**数据库更新：**
```bash
mysql -u driftbottle_user -p driftbottle < sql/system_settings.sql
mysql -u driftbottle_user -p driftbottle < sql/update_admin_roles.sql
mysql -u driftbottle_user -p driftbottle < sql/vip_points_settings.sql
```

**主要变更：**
- 新增 `system_settings` 表
- 配置从 PHP 常量改为数据库存储
- 管理员需要 `settings` 权限才能访问系统设置

### 升级到 v1.0.1（安全增强）

**新增功能：**
- 增强安全功能
- VIP 会员系统
- 签到系统
- 用户封禁功能

**数据库更新：**
```bash
mysql -u driftbottle_user -p driftbottle < sql/update_user_status.sql
```

**主要变更：**
- 新增安全中间件类
- 新增用户状态字段
- 新增 VIP 相关表
- 新增签到相关表

## 系统设置迁移指南

### 从配置文件迁移到数据库

如果您是从 v1.0.1 或更早版本升级，需要将系统配置迁移到数据库：

1. **执行系统设置表创建脚本**
   ```bash
   mysql -u driftbottle_user -p driftbottle < sql/system_settings.sql
   ```

2. **执行管理员权限更新脚本**
   ```bash
   mysql -u driftbottle_user -p driftbottle < sql/update_admin_roles.sql
   ```

3. **执行 VIP 积分配置脚本**
   ```bash
   mysql -u driftbottle_user -p driftbottle < sql/vip_points_settings.sql
   ```

4. **通过后台配置**
   - 登录管理员后台
   - 访问"系统设置"页面
   - 配置各项系统参数

### VIP 会员价格配置

执行以下脚本初始化 VIP 积分配置：

```bash
mysql -u driftbottle_user -p driftbottle < sql/vip_points_settings.sql
```

配置项包括：
- VIP会员1个月开通积分（默认：100）
- VIP会员3个月开通积分（默认：250）
- VIP会员6个月开通积分（默认：450）
- VIP会员12个月开通积分（默认：800）

## 升级后检查清单

- [ ] 数据库更新脚本已执行
- [ ] 配置文件已更新
- [ ] 目录权限已设置
- [ ] 用户登录功能正常
- [ ] 漂流瓶功能正常
- [ ] 管理员后台正常
- [ ] 新功能测试通过
- [ ] 错误日志无异常
- [ ] 性能测试通过

## 回滚方案

如果升级后出现问题，可以按以下步骤回滚：

1. **恢复数据库**
   ```bash
   mysql -u driftbottle_user -p driftbottle < backup_YYYYMMDD.sql
   ```

2. **恢复文件**
   ```bash
   tar -xzf backup_files_YYYYMMDD.tar.gz
   ```

3. **检查功能**
   - 验证所有功能是否恢复正常
   - 检查数据完整性

## 常见问题

### 升级后功能异常

1. 检查数据库更新脚本是否全部执行
2. 检查配置文件是否正确
3. 查看错误日志定位问题
4. 清除浏览器缓存

### 数据丢失

1. 立即停止使用系统
2. 从备份恢复数据
3. 检查升级脚本是否有问题
4. 联系技术支持

### 权限问题

1. 检查目录权限是否正确
2. 检查数据库用户权限
3. 检查 Web 服务器用户权限

## 技术支持

如遇到升级问题，请：
- 查看 [故障排除文档](troubleshooting.md)
- 查看 [GitHub Issues](https://github.com/kggzs/Driftbottle/issues)
- 联系技术支持：QQ 1724464998

