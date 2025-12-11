<div class="container mt-5">
    <div class="row">
        <!-- 메인 콘텐츠 -->
        <div class="col-lg-9">
            <!-- 태그 헤더 -->
            <div class="d-flex align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-tag-fill text-primary me-2"></i>
                    <?= html_escape($tag->name) ?>
                </h2>
                <span class="badge bg-secondary ms-3"><?= number_format($total) ?>개의 게시글</span>
            </div>

            <!-- 게시글 목록 -->
            <?php if (empty($articles)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 mb-0">이 태그로 작성된 게시글이 없습니다.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="list-group mb-4">
                    <?php foreach ($articles as $article): ?>
                        <a href="/article/<?= $article->id ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5 class="mb-1"><?= html_escape($article->title) ?></h5>
                                    <p class="mb-2 text-muted small">
                                        <?= html_escape(mb_substr(strip_tags($article->content), 0, 150)) ?>...
                                    </p>
                                    <?php if (!empty($article->tags)): ?>
                                        <div class="article-tags mb-2">
                                            <?php foreach ($article->tags as $articleTag): ?>
                                                <span class="badge bg-light text-primary me-1">
                                                    <?= html_escape($articleTag->name) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end text-nowrap ms-3">
                                    <small class="text-muted d-block">
                                        <?= html_escape($article->author_name ?? '알 수 없음') ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <?= date('Y.m.d', strtotime($article->created_at)) ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="bi bi-eye"></i> <?= number_format($article->view_count ?? 0) ?>
                                        <i class="bi bi-heart ms-2"></i> <?= number_format($article->like_count ?? 0) ?>
                                    </small>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- 페이지네이션 -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/tag/<?= html_escape($tag->slug) ?>?page=<?= $currentPage - 1 ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            ?>

                            <?php if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/tag/<?= html_escape($tag->slug) ?>?page=1">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="/tag/<?= html_escape($tag->slug) ?>?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="/tag/<?= html_escape($tag->slug) ?>?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/tag/<?= html_escape($tag->slug) ?>?page=<?= $currentPage + 1 ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- 사이드바 -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-fire"></i> 인기 태그
                </div>
                <div class="card-body">
                    <?php if (!empty($popularTags)): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($popularTags as $popularTag): ?>
                                <a href="/tag/<?= html_escape($popularTag->slug) ?>"
                                   class="badge <?= $popularTag->id === $tag->id ? 'bg-primary' : 'bg-light text-primary' ?> text-decoration-none">
                                    <?= html_escape($popularTag->name) ?>
                                    <span class="ms-1 opacity-75"><?= $popularTag->usage_count ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">등록된 태그가 없습니다.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
