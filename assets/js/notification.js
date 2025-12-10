/**
 * 알림 기능 JavaScript
 * 헤더 드롭다운 및 알림 페이지에서 사용
 */
const NotificationManager = {
    pollingInterval: null,
    POLLING_DELAY: 30000, // 30초

    /**
     * 알림 아이콘 타입별 반환
     */
    getNotificationIcon(type) {
        const icons = {
            'comment': 'bi-chat-dots',
            'reply': 'bi-reply',
            'like': 'bi-heart-fill text-danger',
            'mention': 'bi-at text-primary'
        };
        return icons[type] || 'bi-bell';
    },

    /**
     * 상대 시간 포맷
     */
    formatRelativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHour / 24);

        if (diffSec < 60) return '방금 전';
        if (diffMin < 60) return `${diffMin}분 전`;
        if (diffHour < 24) return `${diffHour}시간 전`;
        if (diffDay < 7) return `${diffDay}일 전`;
        return dateString.split(' ')[0];
    },

    /**
     * 안읽은 알림 수 조회 및 배지 업데이트
     */
    updateUnreadCount() {
        $.ajax({
            url: '/rest/notification/unread-count',
            type: 'GET',
            success: (response) => {
                const count = response.count || 0;
                const $badge = $('#notificationBadge');

                if (count > 0) {
                    $badge.text(count > 99 ? '99+' : count).show();
                } else {
                    $badge.hide();
                }
            },
            error: (error) => {
                console.error('Unread count error:', error);
            }
        });
    },

    /**
     * 드롭다운 알림 목록 로드
     */
    loadRecentNotifications() {
        $.ajax({
            url: '/rest/notification',
            type: 'GET',
            data: { recent: 1 },
            success: (response) => {
                this.renderDropdownNotifications(response.rows);
                this.updateBadge(response.unread_count);
            },
            error: (error) => {
                console.error('Load notifications error:', error);
            }
        });
    },

    /**
     * 드롭다운 알림 렌더링
     */
    renderDropdownNotifications(notifications) {
        const $list = $('#notificationList');
        $list.empty();

        if (!notifications || notifications.length === 0) {
            $list.html(`
                <div class="text-center text-muted py-3">
                    <i class="bi bi-bell-slash"></i> 알림이 없습니다
                </div>
            `);
            return;
        }

        notifications.forEach(noti => {
            const isUnread = noti.is_read == 0;
            const icon = this.getNotificationIcon(noti.type);
            const relativeTime = this.formatRelativeTime(noti.created_at);
            const bgClass = isUnread ? 'bg-light' : '';

            // 클릭 시 이동할 URL
            let targetUrl = '#';
            if (noti.reference_type === 'article' && noti.reference_id) {
                targetUrl = `/article/${noti.reference_id}`;
            }

            const html = `
                <a href="${targetUrl}"
                   class="dropdown-item notification-item ${bgClass}"
                   data-id="${noti.id}"
                   data-read="${noti.is_read}">
                    <div class="d-flex align-items-start">
                        <i class="bi ${icon} me-2 mt-1"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">${this.escapeHtml(noti.title)}</div>
                            <small class="text-muted">${this.escapeHtml(noti.message)}</small>
                            <div class="small text-muted">${relativeTime}</div>
                        </div>
                        ${isUnread ? '<span class="badge bg-primary ms-2">New</span>' : ''}
                    </div>
                </a>
            `;
            $list.append(html);
        });

        // 알림 클릭 이벤트
        $('.notification-item').off('click').on('click', (e) => {
            const $item = $(e.currentTarget);
            const id = $item.data('id');
            const isRead = $item.data('read');
            const href = $item.attr('href');

            if (isRead === 0) {
                e.preventDefault();
                this.markAsRead(id, () => {
                    window.location.href = href;
                });
            }
        });
    },

    /**
     * 배지 업데이트
     */
    updateBadge(count) {
        const $badge = $('#notificationBadge');
        if (count > 0) {
            $badge.text(count > 99 ? '99+' : count).show();
        } else {
            $badge.hide();
        }
    },

    /**
     * 개별 알림 읽음 처리
     */
    markAsRead(id) {
        $.ajax({
            url: `/rest/notification/${id}/read`,
            type: 'PUT',
            success: () => {
                this.updateUnreadCount();
            }
        });
    },

    /**
     * 모든 알림 읽음 처리
     */
    markAllAsRead() {
        $.ajax({
            url: '/rest/notification/read-all',
            type: 'PUT',
            success: () => {
                this.loadRecentNotifications();
                // 전체 페이지인 경우 목록도 새로고침
                if ($('#notification_page').length) {
                    this.loadFullNotificationList();
                }
            }
        });
    },

    /**
     * 전체 알림 목록 로드 (알림 페이지용)
     */
    loadFullNotificationList(page = 1) {
        $('#notificationListFull').html(`
            <div class="text-center py-5 text-muted">
                <i class="bi bi-arrow-repeat"></i> 로딩 중...
            </div>
        `);

        $.ajax({
            url: '/rest/notification',
            type: 'GET',
            data: { page: page, per_page: 20 },
            success: (response) => {
                this.renderFullNotificationList(response.rows);
                this.renderPagination(response.pagination);
            },
            error: (error) => {
                $('#notificationListFull').html(`
                    <div class="alert alert-danger">알림을 불러올 수 없습니다.</div>
                `);
            }
        });
    },

    /**
     * 전체 알림 목록 렌더링
     */
    renderFullNotificationList(notifications) {
        const $list = $('#notificationListFull');
        $list.empty();

        if (!notifications || notifications.length === 0) {
            $list.html(`
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1"></i>
                    <p class="mt-3">알림이 없습니다</p>
                </div>
            `);
            return;
        }

        notifications.forEach(noti => {
            const isUnread = noti.is_read == 0;
            const icon = this.getNotificationIcon(noti.type);
            const relativeTime = this.formatRelativeTime(noti.created_at);
            const bgClass = isUnread ? 'bg-light border-start border-primary border-3' : '';

            let targetUrl = '#';
            if (noti.reference_type === 'article' && noti.reference_id) {
                targetUrl = `/article/${noti.reference_id}`;
            }

            const html = `
                <div class="card mb-2 ${bgClass} notification-card" data-id="${noti.id}">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <a href="${targetUrl}" class="text-decoration-none text-dark flex-grow-1 notification-link" data-id="${noti.id}" data-read="${noti.is_read}">
                                <div class="d-flex align-items-start">
                                    <i class="bi ${icon} fs-5 me-3"></i>
                                    <div>
                                        <div class="fw-semibold">${this.escapeHtml(noti.title)}</div>
                                        <div class="text-muted">${this.escapeHtml(noti.message)}</div>
                                        <small class="text-muted">${relativeTime}</small>
                                    </div>
                                </div>
                            </a>
                            <button class="btn btn-sm btn-outline-danger delete-notification-btn" data-id="${noti.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $list.append(html);
        });

        // 링크 클릭 이벤트 - 읽음 처리
        $('.notification-link').off('click').on('click', (e) => {
            const $item = $(e.currentTarget);
            const id = $item.data('id');
            const isRead = $item.data('read');

            if (isRead == 0) {
                this.markAsRead(id);
            }
        });

        // 삭제 버튼 이벤트
        $('.delete-notification-btn').off('click').on('click', (e) => {
            e.preventDefault();
            const id = $(e.currentTarget).data('id');
            this.deleteNotification(id);
        });
    },

    /**
     * 알림 삭제
     */
    deleteNotification(id) {
        $.ajax({
            url: `/rest/notification/${id}`,
            type: 'DELETE',
            success: () => {
                $(`.notification-card[data-id="${id}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
                this.updateUnreadCount();
            }
        });
    },

    /**
     * 페이지네이션 렌더링
     */
    renderPagination(pagination) {
        const $nav = $('#paginationNav');

        if (!pagination || pagination.total_pages <= 1) {
            $nav.empty();
            return;
        }

        let html = '<ul class="pagination justify-content-center mb-0">';

        // 이전 버튼
        if (pagination.current_page > 1) {
            html += `<li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}">&laquo;</a>
            </li>`;
        }

        // 페이지 번호
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === pagination.current_page) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                html += `<li class="page-item">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            }
        }

        // 다음 버튼
        if (pagination.current_page < pagination.total_pages) {
            html += `<li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}">&raquo;</a>
            </li>`;
        }

        html += '</ul>';
        $nav.html(html);

        // 페이지 클릭 이벤트
        $nav.find('a.page-link').off('click').on('click', (e) => {
            e.preventDefault();
            const page = $(e.currentTarget).data('page');
            this.loadFullNotificationList(page);
        });
    },

    /**
     * HTML 이스케이프
     */
    escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    /**
     * 폴링 시작
     */
    startPolling() {
        // 즉시 한 번 실행
        this.updateUnreadCount();

        // 30초마다 폴링
        this.pollingInterval = setInterval(() => {
            this.updateUnreadCount();
        }, this.POLLING_DELAY);
    },

    /**
     * 폴링 중지
     */
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    },

    /**
     * 초기화
     */
    init() {
        // 드롭다운 열릴 때 최신 알림 로드
        const notificationDropdown = document.getElementById('notificationDropdown');
        if (notificationDropdown) {
            notificationDropdown.addEventListener('show.bs.dropdown', () => {
                this.loadRecentNotifications();
            });
        }

        // 모두 읽음 버튼
        $('#markAllReadBtn').on('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.markAllAsRead();
        });

        // 로그인 상태면 폴링 시작 (알림 드롭다운 버튼이 있으면)
        if ($('#notificationDropdown').length) {
            this.startPolling();
        }

        // 알림 페이지인 경우 전체 목록 로드
        if ($('#notification_page').length) {
            this.loadFullNotificationList();
        }
    }
};

// DOM 로드 후 초기화
$(document).ready(() => {
    NotificationManager.init();
});
