# 语音漂流瓶功能

## 功能概述

语音漂流瓶功能允许用户录制语音并发布为语音漂流瓶，其他用户捡起后可以播放语音内容。

## 安装步骤

### 1. 数据库更新

执行以下SQL文件来更新数据库结构：

```bash
mysql -u driftbottle_user -p driftbottle < sql/add_voice_bottle_fields.sql
```

这个SQL文件会为 `bottles` 表添加以下字段：
- `bottle_type`: 漂流瓶类型（'text' 文字漂流瓶 或 'voice' 语音漂流瓶）
- `audio_file`: 语音文件路径
- `audio_duration`: 语音时长（秒）

### 2. 目录权限

确保 `uploads/audio/` 目录存在且具有写入权限：

```bash
# Windows PowerShell
New-Item -ItemType Directory -Path "uploads\audio" -Force

# Linux/Mac
mkdir -p uploads/audio
chmod 755 uploads/audio
```

## 使用说明

### 发布语音漂流瓶

1. 访问 `throw.html` 页面
2. 选择"语音漂流瓶"选项
3. 点击"开始录音"按钮（首次使用需要授权麦克风权限）
4. 录制完成后点击"停止录音"
5. 可以点击"播放"预览录音
6. 确认无误后点击"扔向大海"提交

### 捡起语音漂流瓶

1. 访问 `pick.html` 页面
2. 点击"随机捡一个漂流瓶"
3. 如果捡到的是语音漂流瓶，会显示音频播放器
4. 点击播放按钮即可播放语音内容

### 查看历史记录

- 在"我扔出的漂流瓶"页面可以播放自己发布的语音
- 在"我捡到的漂流瓶"页面可以播放捡到的语音漂流瓶

## 技术实现

### 前端

- 使用 `MediaRecorder API` 进行录音
- 录音格式：WebM（浏览器原生支持）
- 录音数据转换为 Base64 格式上传
- 支持音频播放控制和时长显示

### 后端

- 音频文件存储在 `uploads/audio/` 目录
- 文件命名格式：`voice_{user_id}_{timestamp}_{uniqid}.webm`
- 支持的文件类型：webm, mp3, wav, ogg
- 文件大小限制：5MB
- 删除漂流瓶时自动删除关联的语音文件

### 数据库

- `bottles` 表新增字段：
  - `bottle_type`: ENUM('text', 'voice') DEFAULT 'text'
  - `audio_file`: VARCHAR(255) NULL
  - `audio_duration`: INT(11) NULL

## 浏览器兼容性

| 浏览器 | 支持情况 |
|--------|---------|
| Chrome/Edge | 完全支持 |
| Firefox | 完全支持 |
| Safari | 部分支持（可能需要额外配置） |
| 移动浏览器 | 需要HTTPS环境才能使用录音功能 |

## 注意事项

1. **HTTPS要求**: 在生产环境中，录音功能需要HTTPS协议才能正常工作
2. **文件大小**: 建议录音时长不超过60秒，文件大小控制在5MB以内
3. **浏览器权限**: 首次使用需要用户授权麦克风权限
4. **音频格式**: 默认使用WebM格式，这是浏览器原生支持的格式
5. **存储空间**: 定期清理旧的语音文件，避免占用过多存储空间

## 故障排除

### 无法录音

1. 检查浏览器是否支持 `MediaRecorder API`
2. 确认已授权麦克风权限
3. 检查是否为HTTPS环境（生产环境要求）
4. 检查浏览器控制台是否有错误信息

### 音频无法播放

1. 检查音频文件路径是否正确
2. 确认 `uploads/audio/` 目录权限
3. 检查浏览器是否支持音频格式
4. 检查文件是否完整上传

### 上传失败

1. 检查文件大小是否超过5MB限制
2. 确认服务器 `uploads/audio/` 目录写入权限
3. 检查PHP `upload_max_filesize` 和 `post_max_size` 配置
4. 查看服务器错误日志

## API端点

### 上传音频

```
POST api.php?action=upload_audio
Content-Type: multipart/form-data

参数:
- audio: 音频文件（FormData）

返回:
{
    "success": true,
    "audio_file": "uploads/audio/voice_1_1234567890_abc123.webm",
    "audio_duration": 30
}
```

### 创建漂流瓶（支持语音）

```
POST api.php?action=create_bottle
Content-Type: application/json

参数（文字漂流瓶）:
{
    "bottle_type": "text",
    "content": "漂流瓶内容",
    "mood": "开心",
    "is_anonymous": 0,
    "include_location": 1
}

参数（语音漂流瓶）:
{
    "bottle_type": "voice",
    "audio_file": "uploads/audio/voice_1_1234567890_abc123.webm",
    "audio_duration": 30,
    "mood": "开心",
    "is_anonymous": 0,
    "include_location": 1
}
```

## 更新日志

- **2024-12-13**: 初始版本发布
  - 支持语音录制和播放
  - 支持文字和语音两种漂流瓶类型
  - 音频文件上传和管理
  - 前端页面全面支持语音播放

