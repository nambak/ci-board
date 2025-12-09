<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Report extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('report_m');
        $this->load->model('article_m');
        $this->load->model('comment_m');
        $this->load->library('session');
        $this->load->helper('auth');
    }

    /**
     * 신고 제출
     * POST /rest/report
     *
     * @return void
     */
    public function index_post()
    {
        try {
            $userId = get_user_id();

            if (!$userId) {
                $this->response(['message' => 'unauthorized'], 401);
                return;
            }

            $targetType = $this->post('target_type', true);
            $targetId = (int)$this->post('target_id', true);
            $reason = $this->post('reason', true);
            $detail = $this->post('detail', true);

            // 입력값 검증
            if (!in_array($targetType, [Report_m::TYPE_ARTICLE, Report_m::TYPE_COMMENT])) {
                $this->response(['message' => 'invalid target_type'], 400);
                return;
            }

            if ($targetId <= 0) {
                $this->response(['message' => 'invalid target_id'], 400);
                return;
            }

            if (!array_key_exists($reason, Report_m::$allowedReasons)) {
                $this->response(['message' => 'invalid reason'], 400);
                return;
            }

            // '기타' 사유인 경우 상세 내용 필수
            if ($reason === Report_m::REASON_OTHER && empty($detail)) {
                $this->response(['message' => '기타 사유를 선택한 경우 상세 내용을 입력해주세요.'], 400);
                return;
            }

            // 대상 존재 확인
            if ($targetType === Report_m::TYPE_ARTICLE) {
                if (!$this->article_m->exists($targetId)) {
                    $this->response(['message' => '존재하지 않는 게시글입니다.'], 404);
                    return;
                }
            } else {
                if (!$this->comment_m->exists($targetId)) {
                    $this->response(['message' => '존재하지 않는 댓글입니다.'], 404);
                    return;
                }
            }

            // 중복 신고 확인
            if ($this->report_m->hasReported($userId, $targetType, $targetId)) {
                $this->response(['message' => '이미 신고한 게시물입니다.'], 409);
                return;
            }

            // 자기 자신의 콘텐츠 신고 방지
            if ($targetType === Report_m::TYPE_ARTICLE) {
                $article = $this->article_m->get($targetId);
                if ($article && (int)$article->user_id === (int)$userId) {
                    $this->response(['message' => '자신의 게시글은 신고할 수 없습니다.'], 403);
                    return;
                }
            } else {
                $comment = $this->comment_m->get($targetId);
                if ($comment && (int)$comment->writer_id === (int)$userId) {
                    $this->response(['message' => '자신의 댓글은 신고할 수 없습니다.'], 403);
                    return;
                }
            }

            // 신고 저장
            $reportId = $this->report_m->add($userId, $targetType, $targetId, $reason, $detail);

            if (!$reportId) {
                $this->response(['message' => '신고 등록에 실패했습니다.'], 500);
                return;
            }

            $this->response([
                'message' => '신고가 접수되었습니다.',
                'id' => $reportId
            ], 201);
        } catch (Exception $e) {
            log_message('error', 'Report::index_post error: ' . $e->getMessage());
            $this->response(['message' => 'server error'], 500);
        }
    }

    /**
     * 신고 사유 목록 조회
     * GET /rest/report/reasons
     *
     * @return void
     */
    public function reasons_get()
    {
        $reasons = [];
        foreach (Report_m::$allowedReasons as $key => $label) {
            $reasons[] = [
                'value' => $key,
                'label' => $label
            ];
        }

        $this->response(['reasons' => $reasons], 200);
    }
}
