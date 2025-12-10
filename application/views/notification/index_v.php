<main class="container py-4" id="notification_page">
    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-bell"></i> 알림</h5>
                    <button class="btn btn-sm btn-outline-primary" id="markAllRead">
                        <i class="bi bi-check-all"></i> 모두 읽음 처리
                    </button>
                </div>
                <div class="card-body">
                    <div id="notificationListFull">
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-arrow-repeat"></i> 로딩 중...
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <nav id="paginationNav"></nav>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    $('#markAllRead').on('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        NotificationManager.markAllAsRead();
    });
</script>
