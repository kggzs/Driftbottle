# 评论回复功能说明

## 功能概述

评论回复功能允许用户对评论进行回复，实现二级评论结构。此功能已在 v1.3.0 版本中集成到主数据库文件 `driftbottle.sql` 中。

## 数据库结构

### comments 表结构

在 v1.3.0 版本中，`comments` 表已包含以下字段：

- `id`: 评论ID（主键）
- `parent_id`: 父评论ID，NULL表示一级评论
- `reply_to_user_id`: 回复的目标用户ID
- `bottle_id`: 漂流瓶ID
- `user_id`: 评论者ID
- `content`: 评论内容
- `created_at`: 创建时间

### 索引和外键

- 索引：`idx_parent_id`、`idx_reply_to_user_id`
- 外键：
  - `parent_id` 引用 `comments(id)` ON DELETE CASCADE
  - `reply_to_user_id` 引用 `users(id)` ON DELETE SET NULL

## 安装说明

如果您使用的是 v1.3.0 或更高版本，数据库结构已包含评论回复功能，无需额外执行更新脚本。

如果您是从旧版本升级，请执行：

```bash
mysql -u driftbottle_user -p driftbottle < driftbottle.sql
```

或者使用单独的更新脚本（如果存在）：

```bash
mysql -u driftbottle_user -p driftbottle < sql/add_comment_reply_support.sql
```

## 功能特性

### 评论结构

- **一级评论**：`parent_id` 为 NULL，直接评论漂流瓶
- **二级回复**：`parent_id` 不为 NULL，回复某个评论
- **层级显示**：回复以缩进方式显示，左侧有边框标识
- **用户标识**：回复时显示"回复 @用户名"

## 评论功能增强

### 评论模式

在"我捡到的漂流瓶"页面，评论后可以选择两种模式：

1. **仅评论**：添加评论，但漂流瓶不丢回大海
   - 漂流瓶依然显示在"我捡到的漂流瓶"中
   - 漂流瓶状态不变，其他人无法捡起

2. **评论并丢回大海**：添加评论并将漂流瓶丢回大海
   - 漂流瓶依然显示在"我捡到的漂流瓶"中
   - 漂流瓶状态更新为"漂流中"，其他人可以捡起
   - 其他人捡起后可以看到所有评论内容

### API 使用

#### 添加一级评论（原有功能）

```json
POST api.php?action=comment_bottle
{
    "bottle_id": 1,
    "content": "这是一条评论"
}
```

#### 添加二级回复（新功能）

```json
POST api.php?action=comment_bottle
{
    "bottle_id": 1,
    "content": "这是回复内容",
    "parent_id": 5,
    "reply_to_user_id": 3
}
```

#### 仅评论（不丢回大海）

```json
POST api.php?action=comment_bottle
{
    "bottle_id": 1,
    "content": "这是评论内容",
    "throw_back": false
}
```

参数说明：
- `bottle_id`: 漂流瓶ID（必需）
- `content`: 评论/回复内容（必需）
- `parent_id`: 父评论ID（可选，回复时必需）
- `reply_to_user_id`: 被回复的用户ID（可选，回复时建议提供）
- `throw_back`: 是否丢回大海（可选，默认true，false表示仅评论）

## 前端功能

### profile_bottles.html（我扔出的漂流瓶）

- ✅ 显示所有评论（包括回复）
- ✅ 二级回复以缩进方式显示
- ✅ 每个评论都有"回复"按钮
- ✅ 点击回复按钮弹出回复模态框
- ✅ 回复成功后自动刷新列表

### profile_picked.html（我捡到的漂流瓶）

- ✅ 显示所有评论（包括回复）
- ✅ 二级回复以缩进方式显示
- ✅ 每个评论都有"回复"按钮
- ✅ 点击回复按钮弹出回复模态框
- ✅ 回复成功后自动刷新列表

## 注意事项

1. **数据迁移**：现有的一级评论不受影响，`parent_id` 和 `reply_to_user_id` 字段为 NULL
2. **级联删除**：删除父评论时，所有子回复也会被自动删除（CASCADE）
3. **消息通知**：回复评论时，会通知被回复的用户，而不是漂流瓶所有者
4. **积分奖励**：回复评论也会给被回复的用户增加积分
5. **评论显示**：所有评论（包括回复）都会显示在"我扔出的漂流瓶"和"我捡到的漂流瓶"页面
6. **漂流瓶状态**：选择"仅评论"时，漂流瓶状态不变；选择"评论并丢回大海"时，漂流瓶状态更新为"漂流中"

## 测试建议

1. 测试一级评论功能（确保原有功能正常）
2. 测试回复一级评论
3. 测试回复二级评论（嵌套回复）
4. 测试评论显示是否正确（二级回复是否缩进显示）
5. 测试消息通知是否正确发送给被回复的用户

