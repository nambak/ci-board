<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * NotificationService
 * 알림 생성 로직을 중앙화하는 서비스 클래스
 */
class NotificationService
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('notification_m');
        $this->CI->load->model('article_m');
        $this->CI->load->model('comment_m');
        $this->CI->load->model('user_m');
    }

    /**
     * 댓글 알림 생성 (내 게시글에 댓글)
     *
     * @param int $articleId 게시글 ID
     * @param int $commentId 댓글 ID
     * @param int $actorId 댓글 작성자 ID
     * @return void
     */
    public function notifyNewComment($articleId, $commentId, $actorId)
    {
        try {
            $article = $this->CI->article_m->get($articleId);
            $actor = $this->CI->user_m->get($actorId);

            if (!$article || !$actor) {
                return;
            }

            // 본인 게시글에 본인 댓글이면 알림 안 함
            if ($article->user_id == $actorId) {
                return;
            }

            // 알림 설정 확인
            if (!$this->CI->user_m->isNotificationEnabled($article->user_id)) {
                return;
            }

            $title = '새 댓글';
            $message = $actor->name . '님이 회원님의 게시글에 댓글을 남겼습니다.';

            $this->CI->notification_m->create(
                $article->user_id,
                'comment',
                $title,
                $message,
                'article',
                $articleId,
                $actorId
            );
        } catch (Exception $e) {
            log_message('error', 'NotificationService::notifyNewComment error: ' . $e->getMessage());
        }
    }

    /**
     * 답글 알림 생성 (내 댓글에 답글)
     *
     * @param int $parentCommentId 부모 댓글 ID
     * @param int $replyCommentId 답글 ID
     * @param int $actorId 답글 작성자 ID
     * @return void
     */
    public function notifyNewReply($parentCommentId, $replyCommentId, $actorId)
    {
        try {
            $parentComment = $this->CI->comment_m->get($parentCommentId);
            $actor = $this->CI->user_m->get($actorId);

            if (!$parentComment || !$actor) {
                return;
            }

            // 본인 댓글에 본인 답글이면 알림 안 함
            if ($parentComment->writer_id == $actorId) {
                return;
            }

            // 알림 설정 확인
            if (!$this->CI->user_m->isNotificationEnabled($parentComment->writer_id)) {
                return;
            }

            $title = '새 답글';
            $message = $actor->name . '님이 회원님의 댓글에 답글을 남겼습니다.';

            $this->CI->notification_m->create(
                $parentComment->writer_id,
                'reply',
                $title,
                $message,
                'article',
                $parentComment->article_id,
                $actorId
            );
        } catch (Exception $e) {
            log_message('error', 'NotificationService::notifyNewReply error: ' . $e->getMessage());
        }
    }

    /**
     * 좋아요 알림 생성
     *
     * @param int $articleId 게시글 ID
     * @param int $actorId 좋아요 누른 사용자 ID
     * @return void
     */
    public function notifyLike($articleId, $actorId)
    {
        try {
            $article = $this->CI->article_m->get($articleId);
            $actor = $this->CI->user_m->get($actorId);

            if (!$article || !$actor) {
                return;
            }

            // 본인 게시글에 본인 좋아요면 알림 안 함
            if ($article->user_id == $actorId) {
                return;
            }

            // 알림 설정 확인
            if (!$this->CI->user_m->isNotificationEnabled($article->user_id)) {
                return;
            }

            $title = '좋아요';
            $message = $actor->name . '님이 회원님의 게시글을 좋아합니다.';

            $this->CI->notification_m->create(
                $article->user_id,
                'like',
                $title,
                $message,
                'article',
                $articleId,
                $actorId
            );
        } catch (Exception $e) {
            log_message('error', 'NotificationService::notifyLike error: ' . $e->getMessage());
        }
    }

    /**
     * 텍스트에서 멘션(@사용자명) 추출
     *
     * @param string $text 검색할 텍스트
     * @return array 멘션된 사용자명 배열
     */
    public function extractMentions($text)
    {
        $mentions = [];
        // @사용자명 형식 추출 (한글, 영문, 숫자, 언더스코어 허용)
        if (preg_match_all('/@([a-zA-Z0-9_가-힣]+)/', $text, $matches)) {
            $mentions = array_unique($matches[1]);
        }
        return $mentions;
    }

    /**
     * 멘션 알림 생성 (게시글에서 멘션)
     *
     * @param int $articleId 게시글 ID
     * @param string $content 게시글 내용
     * @param int $actorId 작성자 ID
     * @return void
     */
    public function notifyMentionsInArticle($articleId, $content, $actorId)
    {
        try {
            $actor = $this->CI->user_m->get($actorId);
            if (!$actor) {
                return;
            }

            $mentions = $this->extractMentions($content);
            if (empty($mentions)) {
                return;
            }

            foreach ($mentions as $username) {
                $mentionedUser = $this->CI->user_m->get_by_username($username);

                if (!$mentionedUser) {
                    continue;
                }

                // 본인을 멘션하면 알림 안 함
                if ($mentionedUser->id == $actorId) {
                    continue;
                }

                // 알림 설정 확인
                if (!$this->CI->user_m->isNotificationEnabled($mentionedUser->id)) {
                    continue;
                }

                $title = '멘션';
                $message = $actor->name . '님이 게시글에서 회원님을 언급했습니다.';

                $this->CI->notification_m->create(
                    $mentionedUser->id,
                    'mention',
                    $title,
                    $message,
                    'article',
                    $articleId,
                    $actorId
                );
            }
        } catch (Exception $e) {
            log_message('error', 'NotificationService::notifyMentionsInArticle error: ' . $e->getMessage());
        }
    }

    /**
     * 멘션 알림 생성 (댓글에서 멘션)
     *
     * @param int $articleId 게시글 ID
     * @param int $commentId 댓글 ID
     * @param string $content 댓글 내용
     * @param int $actorId 작성자 ID
     * @return void
     */
    public function notifyMentionsInComment($articleId, $commentId, $content, $actorId)
    {
        try {
            $actor = $this->CI->user_m->get($actorId);
            if (!$actor) {
                return;
            }

            $mentions = $this->extractMentions($content);
            if (empty($mentions)) {
                return;
            }

            foreach ($mentions as $username) {
                $mentionedUser = $this->CI->user_m->get_by_username($username);

                if (!$mentionedUser) {
                    continue;
                }

                // 본인을 멘션하면 알림 안 함
                if ($mentionedUser->id == $actorId) {
                    continue;
                }

                // 알림 설정 확인
                if (!$this->CI->user_m->isNotificationEnabled($mentionedUser->id)) {
                    continue;
                }

                $title = '멘션';
                $message = $actor->name . '님이 댓글에서 회원님을 언급했습니다.';

                $this->CI->notification_m->create(
                    $mentionedUser->id,
                    'mention',
                    $title,
                    $message,
                    'article',
                    $articleId,
                    $actorId
                );
            }
        } catch (Exception $e) {
            log_message('error', 'NotificationService::notifyMentionsInComment error: ' . $e->getMessage());
        }
    }
}
