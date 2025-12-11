<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- 헤더 -->
            <div class="text-center mb-5">
                <h2>
                    <i class="bi bi-tags-fill text-primary me-2"></i>
                    인기 태그
                </h2>
                <p class="text-muted">게시글에 사용된 태그들을 확인해보세요.</p>
            </div>

            <!-- 태그 클라우드 -->
            <?php if (!empty($popularTags)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <?php
                            // 태그 크기 계산을 위한 최대/최소 사용량 구하기
                            $maxUsage = max(array_column($popularTags, 'usage_count'));
                            $minUsage = min(array_column($popularTags, 'usage_count'));
                            $range = max(1, $maxUsage - $minUsage);
                            ?>
                            <?php foreach ($popularTags as $tag): ?>
                                <?php
                                // 사용량에 따른 크기 계산 (0.875rem ~ 1.5rem)
                                $sizeRatio = ($tag->usage_count - $minUsage) / $range;
                                $fontSize = 0.875 + ($sizeRatio * 0.625);
                                ?>
                                <a href="/tag/<?= html_escape($tag->slug) ?>"
                                   class="badge bg-light text-primary text-decoration-none py-2 px-3"
                                   style="font-size: <?= $fontSize ?>rem;">
                                    <?= html_escape($tag->name) ?>
                                    <span class="ms-1 opacity-75 small">(<?= number_format($tag->usage_count) ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-tags text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 mb-0">등록된 태그가 없습니다.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
