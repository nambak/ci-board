<aside class="admin-sidebar" id="adminSidebar">
    <div class="p-3">
        <h5 class="text-white mb-3">관리자 메뉴</h5>
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a href="/admin" class="nav-link text-white active">
                    <i class="bi bi-speedometer2"></i> 대시보드
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="/admin/users" class="nav-link text-white">
                    <i class="bi bi-people"></i> 사용자 관리
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="/admin/boards" class="nav-link text-white">
                    <i class="bi bi-list-ul"></i> 게시판 관리
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="/admin/articles" class="nav-link text-white">
                    <i class="bi bi-file-text"></i> 게시글 관리
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="/admin/comments" class="nav-link text-white">
                    <i class="bi bi-chat-dots"></i> 댓글 관리
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="/admin/reports" class="nav-link text-white">
                    <i class="bi bi-flag"></i> 신고 관리
                </a>
            </li>
        </ul>
    </div>
</aside>

<script>
// 모바일에서 사이드바 토글
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('adminSidebar').classList.toggle('show');
});

// 사이드바 외부 클릭시 닫기
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('adminSidebar');
    const toggle = document.getElementById('sidebarToggle');

    if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
        if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    }
});
</script>
