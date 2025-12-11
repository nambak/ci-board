/**
 * 태그 입력 모듈
 * 태그 입력, 자동완성, 인기 태그 표시 기능을 제공합니다.
 */
const TagInput = {
    maxTags: 5,
    selectedTags: [],
    suggestionIndex: -1,
    debounceTimer: null,

    /**
     * 초기화
     */
    init() {
        this.selectedTags = [];
        this.suggestionIndex = -1;
        this.bindEvents();
        this.loadPopularTags();
    },

    /**
     * 이벤트 바인딩
     */
    bindEvents() {
        const $input = $('#tagInput');
        const $suggestions = $('#tagSuggestions');

        // 태그 입력
        $input.on('input', (e) => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.searchTags(e.target.value);
            }, 300);
        });

        // 키보드 이벤트
        $input.on('keydown', (e) => {
            const value = e.target.value.trim();
            const $items = $suggestions.find('.tag-suggestion-item');

            switch (e.key) {
                case 'Enter':
                    e.preventDefault();
                    if (this.suggestionIndex >= 0 && $items.length > 0) {
                        const $selected = $items.eq(this.suggestionIndex);
                        this.addTag($selected.data('name'));
                    } else if (value) {
                        this.addTag(value);
                    }
                    break;
                case ',':
                    e.preventDefault();
                    if (value) {
                        this.addTag(value);
                    }
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    if ($items.length > 0) {
                        this.suggestionIndex = Math.min(this.suggestionIndex + 1, $items.length - 1);
                        this.updateSuggestionHighlight($items);
                    }
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    if ($items.length > 0) {
                        this.suggestionIndex = Math.max(this.suggestionIndex - 1, 0);
                        this.updateSuggestionHighlight($items);
                    }
                    break;
                case 'Escape':
                    this.hideSuggestions();
                    break;
                case 'Backspace':
                    if (!value && this.selectedTags.length > 0) {
                        this.removeTagByIndex(this.selectedTags.length - 1);
                    }
                    break;
            }
        });

        // 포커스 아웃 시 자동완성 닫기
        $input.on('blur', () => {
            setTimeout(() => {
                this.hideSuggestions();
            }, 200);
        });

        // 문서 클릭 시 자동완성 닫기
        $(document).on('click', (e) => {
            if (!$(e.target).closest('.tag-input-container').length) {
                this.hideSuggestions();
            }
        });
    },

    /**
     * 태그 검색 (자동완성)
     */
    searchTags(keyword) {
        keyword = keyword.trim();

        if (!keyword) {
            this.hideSuggestions();
            return;
        }

        $.ajax({
            url: '/rest/tag/search',
            type: 'GET',
            data: { q: keyword, limit: 10 },
            success: (response) => {
                if (response.success && response.data.length > 0) {
                    this.showSuggestions(response.data);
                } else {
                    this.hideSuggestions();
                }
            },
            error: () => {
                this.hideSuggestions();
            }
        });
    },

    /**
     * 자동완성 목록 표시
     */
    showSuggestions(tags) {
        const $suggestions = $('#tagSuggestions');
        $suggestions.empty();
        this.suggestionIndex = -1;

        tags.forEach((tag) => {
            // 이미 선택된 태그는 제외
            if (this.selectedTags.some(t => t.toLowerCase() === tag.name.toLowerCase())) {
                return;
            }

            const $item = $(`
                <div class="tag-suggestion-item" data-name="${this.escapeHtml(tag.name)}">
                    <span>${this.escapeHtml(tag.name)}</span>
                    <span class="tag-usage">${tag.usage_count}개 게시글</span>
                </div>
            `);

            $item.on('click', () => {
                this.addTag(tag.name);
            });

            $suggestions.append($item);
        });

        if ($suggestions.children().length > 0) {
            $suggestions.addClass('show');
        } else {
            this.hideSuggestions();
        }
    },

    /**
     * 자동완성 숨기기
     */
    hideSuggestions() {
        $('#tagSuggestions').removeClass('show').empty();
        this.suggestionIndex = -1;
    },

    /**
     * 자동완성 항목 하이라이트 업데이트
     */
    updateSuggestionHighlight($items) {
        $items.removeClass('active');
        if (this.suggestionIndex >= 0) {
            $items.eq(this.suggestionIndex).addClass('active');
        }
    },

    /**
     * 태그 추가
     */
    addTag(name) {
        name = name.trim();

        if (!name) return;

        // 최대 태그 수 확인
        if (this.selectedTags.length >= this.maxTags) {
            Swal.fire({
                icon: 'warning',
                text: `최대 ${this.maxTags}개의 태그만 추가할 수 있습니다.`
            });
            return;
        }

        // 중복 확인
        if (this.selectedTags.some(t => t.toLowerCase() === name.toLowerCase())) {
            Swal.fire({
                icon: 'warning',
                text: '이미 추가된 태그입니다.'
            });
            return;
        }

        // 태그 길이 확인
        if (name.length > 50) {
            Swal.fire({
                icon: 'warning',
                text: '태그는 50자 이내로 입력해주세요.'
            });
            return;
        }

        this.selectedTags.push(name);
        this.renderSelectedTags();
        $('#tagInput').val('');
        this.hideSuggestions();
    },

    /**
     * 인덱스로 태그 제거
     */
    removeTagByIndex(index) {
        if (index >= 0 && index < this.selectedTags.length) {
            this.selectedTags.splice(index, 1);
            this.renderSelectedTags();
        }
    },

    /**
     * 선택된 태그 렌더링
     */
    renderSelectedTags() {
        const $container = $('#selectedTags');
        $container.empty();

        this.selectedTags.forEach((tag, index) => {
            const $badge = $(`
                <span class="tag-badge">
                    ${this.escapeHtml(tag)}
                    <span class="tag-remove" data-index="${index}">
                        <i class="bi bi-x"></i>
                    </span>
                </span>
            `);

            $badge.find('.tag-remove').on('click', () => {
                this.removeTagByIndex(index);
            });

            $container.append($badge);
        });
    },

    /**
     * 인기 태그 로드
     */
    loadPopularTags() {
        $.ajax({
            url: '/rest/tag/popular',
            type: 'GET',
            data: { limit: 10 },
            success: (response) => {
                if (response.success && response.data.length > 0) {
                    this.renderPopularTags(response.data);
                }
            }
        });
    },

    /**
     * 인기 태그 렌더링
     */
    renderPopularTags(tags) {
        const $container = $('#popularTags');
        $container.empty();

        tags.forEach((tag) => {
            const $tag = $(`<span class="popular-tag">${this.escapeHtml(tag.name)}</span>`);

            $tag.on('click', () => {
                this.addTag(tag.name);
            });

            $container.append($tag);
        });
    },

    /**
     * 기존 태그 설정 (수정 페이지용)
     */
    setTags(tags) {
        this.selectedTags = [];
        if (Array.isArray(tags)) {
            tags.forEach(tag => {
                if (typeof tag === 'string') {
                    this.selectedTags.push(tag);
                } else if (tag.name) {
                    this.selectedTags.push(tag.name);
                }
            });
        }
        this.renderSelectedTags();
    },

    /**
     * 선택된 태그 목록 반환
     */
    getTags() {
        return this.selectedTags.slice();
    },

    /**
     * HTML 이스케이프
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
