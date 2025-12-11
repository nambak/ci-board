<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Activity Logger Library
 *
 * 활동 로그 기록을 위한 편의 라이브러리
 */
class Activity_logger
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Activity_log_m');
    }

    /**
     * 로그인 성공 로그
     *
     * @param int $userId 사용자 ID
     * @param string $username 사용자명
     * @return int|bool
     */
    public function logLogin($userId, $username)
    {
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_LOGIN,
            Activity_log_m::TARGET_USER,
            $userId,
            "{$username}님이 로그인했습니다.",
            null,
            null,
            $userId
        );
    }

    /**
     * 로그인 실패 로그
     *
     * @param string $attemptedUsername 시도한 사용자명
     * @return int|bool
     */
    public function logLoginFailed($attemptedUsername)
    {
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_LOGIN_FAILED,
            null,
            null,
            "로그인 시도 실패 (사용자명: {$attemptedUsername})",
            null,
            null,
            null
        );
    }

    /**
     * 로그아웃 로그
     *
     * @param int $userId 사용자 ID
     * @param string $username 사용자명
     * @return int|bool
     */
    public function logLogout($userId, $username)
    {
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_LOGOUT,
            Activity_log_m::TARGET_USER,
            $userId,
            "{$username}님이 로그아웃했습니다.",
            null,
            null,
            $userId
        );
    }

    /**
     * 게시글 작성 로그
     *
     * @param int $articleId 게시글 ID
     * @param string $title 게시글 제목
     * @param int $boardId 게시판 ID
     * @return int|bool
     */
    public function logArticleCreate($articleId, $title, $boardId)
    {
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_ARTICLE_CREATE,
            Activity_log_m::TARGET_ARTICLE,
            $articleId,
            "게시글 작성: {$title}",
            null,
            ['title' => $title, 'board_id' => $boardId]
        );
    }

    /**
     * 게시글 수정 로그
     *
     * @param int $articleId 게시글 ID
     * @param array $oldData 변경 전 데이터
     * @param array $newData 변경 후 데이터
     * @return int|bool
     */
    public function logArticleUpdate($articleId, $oldData, $newData)
    {
        $title = $newData['title'] ?? $oldData['title'] ?? '제목 없음';
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_ARTICLE_UPDATE,
            Activity_log_m::TARGET_ARTICLE,
            $articleId,
            "게시글 수정: {$title}",
            $this->sanitizeData($oldData),
            $this->sanitizeData($newData)
        );
    }

    /**
     * 게시글 삭제 로그
     *
     * @param int $articleId 게시글 ID
     * @param array $articleData 삭제된 게시글 데이터
     * @return int|bool
     */
    public function logArticleDelete($articleId, $articleData)
    {
        $title = $articleData['title'] ?? '제목 없음';
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_ARTICLE_DELETE,
            Activity_log_m::TARGET_ARTICLE,
            $articleId,
            "게시글 삭제: {$title}",
            $this->sanitizeData($articleData),
            null
        );
    }

    /**
     * 댓글 작성 로그
     *
     * @param int $commentId 댓글 ID
     * @param int $articleId 게시글 ID
     * @param string $content 댓글 내용 (일부만 저장)
     * @return int|bool
     */
    public function logCommentCreate($commentId, $articleId, $content)
    {
        $preview = mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : '');
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_COMMENT_CREATE,
            Activity_log_m::TARGET_COMMENT,
            $commentId,
            "댓글 작성 (게시글 #{$articleId}): {$preview}",
            null,
            ['article_id' => $articleId]
        );
    }

    /**
     * 댓글 수정 로그
     *
     * @param int $commentId 댓글 ID
     * @param string $oldContent 변경 전 내용
     * @param string $newContent 변경 후 내용
     * @return int|bool
     */
    public function logCommentUpdate($commentId, $oldContent, $newContent)
    {
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_COMMENT_UPDATE,
            Activity_log_m::TARGET_COMMENT,
            $commentId,
            "댓글 수정",
            ['content' => mb_substr($oldContent, 0, 100)],
            ['content' => mb_substr($newContent, 0, 100)]
        );
    }

    /**
     * 댓글 삭제 로그
     *
     * @param int $commentId 댓글 ID
     * @param int $articleId 게시글 ID
     * @return int|bool
     */
    public function logCommentDelete($commentId, $articleId)
    {
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_COMMENT_DELETE,
            Activity_log_m::TARGET_COMMENT,
            $commentId,
            "댓글 삭제 (게시글 #{$articleId})"
        );
    }

    /**
     * 비밀번호 변경 로그
     *
     * @param int $userId 사용자 ID
     * @return int|bool
     */
    public function logPasswordChange($userId)
    {
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_PASSWORD_CHANGE,
            Activity_log_m::TARGET_USER,
            $userId,
            "비밀번호 변경"
        );
    }

    /**
     * 프로필 수정 로그
     *
     * @param int $userId 사용자 ID
     * @param array $oldData 변경 전 데이터
     * @param array $newData 변경 후 데이터
     * @return int|bool
     */
    public function logProfileUpdate($userId, $oldData, $newData)
    {
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_PROFILE_UPDATE,
            Activity_log_m::TARGET_USER,
            $userId,
            "프로필 수정",
            $this->sanitizeData($oldData),
            $this->sanitizeData($newData)
        );
    }

    /**
     * 사용자 삭제 로그
     *
     * @param int $targetUserId 삭제된 사용자 ID
     * @param array $userData 삭제된 사용자 데이터
     * @return int|bool
     */
    public function logUserDelete($targetUserId, $userData)
    {
        $username = $userData['name'] ?? '알 수 없음';
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_USER_DELETE,
            Activity_log_m::TARGET_USER,
            $targetUserId,
            "사용자 삭제: {$username}",
            $this->sanitizeData($userData),
            null
        );
    }

    /**
     * 사용자 권한 변경 로그
     *
     * @param int $targetUserId 대상 사용자 ID
     * @param string $oldRole 이전 권한
     * @param string $newRole 새 권한
     * @return int|bool
     */
    public function logUserRoleChange($targetUserId, $oldRole, $newRole)
    {
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_USER_ROLE_CHANGE,
            Activity_log_m::TARGET_USER,
            $targetUserId,
            "사용자 권한 변경: {$oldRole} -> {$newRole}",
            ['role' => $oldRole],
            ['role' => $newRole]
        );
    }

    /**
     * 게시판 생성 로그
     *
     * @param int $boardId 게시판 ID
     * @param string $boardName 게시판 이름
     * @return int|bool
     */
    public function logBoardCreate($boardId, $boardName)
    {
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_BOARD_CREATE,
            Activity_log_m::TARGET_BOARD,
            $boardId,
            "게시판 생성: {$boardName}",
            null,
            ['name' => $boardName]
        );
    }

    /**
     * 게시판 수정 로그
     *
     * @param int $boardId 게시판 ID
     * @param array $oldData 변경 전 데이터
     * @param array $newData 변경 후 데이터
     * @return int|bool
     */
    public function logBoardUpdate($boardId, $oldData, $newData)
    {
        $boardName = $newData['name'] ?? $oldData['name'] ?? '알 수 없음';
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_BOARD_UPDATE,
            Activity_log_m::TARGET_BOARD,
            $boardId,
            "게시판 수정: {$boardName}",
            $this->sanitizeData($oldData),
            $this->sanitizeData($newData)
        );
    }

    /**
     * 게시판 삭제 로그
     *
     * @param int $boardId 게시판 ID
     * @param array $boardData 삭제된 게시판 데이터
     * @return int|bool
     */
    public function logBoardDelete($boardId, $boardData)
    {
        $boardName = $boardData['name'] ?? '알 수 없음';
        return $this->CI->Activity_log_m->log(
            Activity_log_m::ACTION_BOARD_DELETE,
            Activity_log_m::TARGET_BOARD,
            $boardId,
            "게시판 삭제: {$boardName}",
            $this->sanitizeData($boardData),
            null
        );
    }

    /**
     * 민감한 데이터 제거
     *
     * @param array|object $data
     * @return array
     */
    private function sanitizeData($data)
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            return $data;
        }

        // 민감한 필드 제거
        $sensitiveFields = ['password', 'remember_token', 'verification_token'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }

        return $data;
    }
}
