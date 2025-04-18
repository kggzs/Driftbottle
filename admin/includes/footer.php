    </main>

    <!-- Bootstrap核心JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <!-- 侧边栏切换脚本 -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector('body').classList.toggle('sb-sidenav-toggled');
                document.querySelector('.sidebar').classList.toggle('show');
                document.querySelector('.main-content').classList.toggle('shift');
            });
        }
        
        // 自动为表格添加响应式类
        var tables = document.querySelectorAll('table.table');
        tables.forEach(function(table) {
            var parent = table.parentNode;
            if (!parent.classList.contains('table-responsive')) {
                // 如果父元素不是.table-responsive, 创建一个包裹元素
                var wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                parent.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
        
        // 初始化工具提示
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // 初始化弹出框
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
    </script>
</body>
</html> 