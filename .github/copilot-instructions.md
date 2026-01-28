# Copilot / AI Agent 指南 — Driftbottle 仓库

快速说明：本文件为在此仓库中工作的代码生成/修改 AI（如 Copilot/Agents）提供可操作、仓库特定的上下文和示例。

1) 大局（Big Picture）
- 这是一个轻量级 PHP 单体应用（前端为静态 HTML/JS，后端为 PHP + MySQL）。主要交互通过页面（如 `index.html`, `throw.html`, `pick.html`）和 `api.php?action=...` 实现。
- 公共与共享代码位于 `includes/`（核心：`includes/config.php`, `includes/user.php`, `includes/bottle.php`, `includes/security.php`, `includes/validator.php`）。管理面板位于 `admin/`（公共 header/footer 在 `admin/includes/`）。静态资源在 `assets/`。

2) 关键文件与典型调用/数据流
- 页面 → API：前端 JS 调用 `api.php?action=...`（例如 `create_bottle`, `upload_audio`, `pick_bottle`）。查阅 `api.php` 源码以获得端点实现细节。
- 权限/身份：`includes/config.php` 配置 DB 与系统常量；会话与管理员逻辑参考 `includes/admin.php` 与 `admin/login.php`。
- 模板/布局：后台页面模板通过 `admin/includes/header.php` / `admin/includes/footer.php` 包含；在修改后台页面时请保持该包含结构一致。
- 文件上传：语音文件存放在 `uploads/audio/`（确保可写）。SQL 变更脚本（如 `sql/add_voice_bottle_fields.sql`）记录了 schema 变更。

3) 项目特定约定与模式（对 AI 很重要）
- 风格：代码以过程化 PHP 为主（不是 MVC 框架），偏向直接在页面中引入后端逻辑。避免将修改分散到不相关的页面——优先在 `includes/` 中添加或复用通用逻辑。
- 数据验证与安全：输入验证集中在 `includes/validator.php`，输出/转义与安全处理在 `includes/security.php`。任何改变输入处理必须同时在前端 JS（assets/js）与后端 validator 中一致更新。
- 管理后台：后台操作常直接修改数据并输出结果（同步页面）。对敏感动作请遵循现有后台审核/日志路径：`admin/log_action.php` 与 `logs/` 目录。

4) 常用开发/调试命令（可直接运行）
- 本地快速运行（开发调试，已提前部署完毕）：
  ```bash
  # 直接访问 http://127.0.0.1:800/index.html
  ```
- 导入数据库（将现有 schema 导入 MySQL）：
  ```bash
  mysql -u <user> -p < driftbottle.sql
  ```
- 日志与上传目录：检查 `logs/` 和 `uploads/audio/` 的写权限，排查运行时错误请查看 `logs/` 中的日志文件。

5) 集成点与外部依赖
- 高德地图 / IP 定位：参考 `includes/ip_location.php` 与 README 中关于高德 API 的说明；API key 存于系统设置（数据库 `system_settings`）。
- 语音/文件：语音录制由前端调用 `upload_audio` API，文件存储在 `uploads/audio/`，对应 DB 字段在 `bottles`（参阅 `sql/add_voice_bottle_fields.sql`）。

6) 代码修改建议（AI 生成时优先遵循）
- 优先复用 `includes/` 中已有函数与类，避免直接在页面里重复粘贴逻辑。
- 修改数据库相关字段时，查找并更新所有依赖 SQL（`driftbottle.sql` / `sql/` 下的脚本）与相关 PHP 处理点（`includes/` 与 `api.php`）。
- 对输出做严格转义：在渲染用户内容前使用项目现有函数（查看 `includes/security.php`）。

7) 查找样例（快速示例命令）
- 查找 API 实现点：搜索 `api.php` 中 `action=` 的分支实现。
- 查找前端入口：`index.html`, `throw.html`, `pick.html`, `profile*.html`。
- 查找后台模板：`admin/includes/header.php`, `admin/includes/footer.php`。

8) 文档与参考（仓库内示例）
- 主说明：`README.md`（项目架构、部署、功能一览）
- 功能文档：`docs/features/voice-bottle.md`, `docs/features/vip-system.md`（语音与 VIP 系统实现细节）
- 升级脚本：`sql/` 目录下的 SQL 文件记录了历史 schema 变更与需手动执行的迁移。

9) 合并策略（如果已有 `.github/copilot-instructions.md` 或类似文件）
- 合并时保留仓库特定的“关键文件/命令/约定”段落，删除过时的运行命令并替换为上文的“常用开发/调试命令”。