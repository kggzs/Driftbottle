// 日志工具类
const Logger = {
    // 调试模式状态
    isDebugMode: false,
    
    // 初始化日志工具
    init() {
        // 从服务器获取调试模式状态
        fetch('api.php?action=get_debug_mode')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.isDebugMode = data.debug_mode;
                    this.log('Logger initialized, debug mode:', this.isDebugMode);
                }
            })
            .catch(error => {
                console.error('Failed to initialize logger:', error);
                // 默认开启调试模式以便于排查问题
                this.isDebugMode = true;
            });
    },
    
    // 日志打印方法
    log(...args) {
        if (this.isDebugMode) {
            console.log('[DEBUG]', new Date().toISOString(), ...args);
        }
    },
    
    // 错误日志打印方法
    error(...args) {
        // 错误总是记录，不管是否在调试模式
        console.error('[ERROR]', new Date().toISOString(), ...args);
        
        // 如果第一个参数是Error对象，则展开它的详细信息
        if (args[0] instanceof Error) {
            const error = args[0];
            console.error('[ERROR Details]', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
        }
    },
    
    // 警告日志打印方法
    warn(...args) {
        if (this.isDebugMode) {
            console.warn('[WARN]', new Date().toISOString(), ...args);
        }
    },
    
    // 信息日志打印方法
    info(...args) {
        if (this.isDebugMode) {
            console.info('[INFO]', new Date().toISOString(), ...args);
        }
    }
};

// 导出工具类
window.Logger = Logger;

// 添加全局错误处理
window.addEventListener('error', (event) => {
    Logger.error('Global error:', event.error);
});

window.addEventListener('unhandledrejection', (event) => {
    Logger.error('Unhandled promise rejection:', event.reason);
}); 