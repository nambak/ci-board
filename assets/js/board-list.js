const BoardList = {
    isLoggedIn: null,
    csrfHash: null,
    csrfTokenName: null,

    initBoardList() {
        // 기본 컬럼 구성 (관리 기능 제거 - 관리자 페이지에서만 가능)
        const columns = [{
            field: 'name',
            title: '제목',
            halign: 'center',
            formatter: (value, row, index) => {
                return `<a href="/board/detail?id=${row.id}">${this.escapeHtml(row.name)}</a>`;
            }
        }, {
            field: 'description',
            title: '설명',
            halign: 'center',
            formatter: (value, row, index) => {
                return this.escapeHtml(row.description || '-');
            }
        }, {
            field: 'created_at',
            title: '등록일',
            align: 'center',
            halign: 'center',
            formatter: (value, row, index) => {
                if (!row.created_at) return '-';
                // 날짜만 추출 (YYYY-MM-DD)
                return row.created_at.split(' ')[0];
            }
        }];

        // 테이블 초기화
        $('#board_list_table').bootstrapTable({
            url: '/rest/board',
            columns: columns,
            pagination: true,
            headerStyle: (column) => {
                return {
                    classes: 'table-dark'
                }
            }
        });
    },

    // 모달이 닫힐 때 폼 초기화
    initCreateBoardModal() {
        $('#createBoardModal').on('hidden.bs.modal', () => {
            const form = document.getElementById('createBoardForm');
            form.reset();
            form.classList.remove('was-validated');
            // 모달 제목을 기본값으로 복원
            $('#createBoardModalLabel').text('새 게시판 생성');
        });
    },

    escapeHtml(str) {
        return String(str === undefined || str === null ? '' : str)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#39;');
    },

    init(isLoggedIn, csrfHash, csrfTokenName) {
        this.isLoggedIn = isLoggedIn;
        this.csrfHash = csrfHash;
        this.csrfTokenName = csrfTokenName;

        this.initBoardList();
        this.initCreateBoardModal();
    }
}

$(document).ready(() => {
    const form = $('#board_list');
    const isLoggedIn = form.data('is-logged-in');
    const csrfHash = form.data('csrf-hash');
    const csrfTokenName = form.data('csrf-token-name');

    BoardList.init(isLoggedIn, csrfHash, csrfTokenName);
});


