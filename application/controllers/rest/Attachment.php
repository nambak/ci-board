<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

/**
 * Attachment REST Controller
 *
 * 첨부파일 업로드, 다운로드, 삭제 기능을 제공하는 REST API 컨트롤러
 */
class Attachment extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('attachment_m');
        $this->load->model('article_m');
        $this->load->model('user_m');
        $this->load->helper(['auth', 'file']);
        $this->load->config('upload');
    }

    /**
     * 파일 업로드
     * POST /rest/attachment/upload
     */
    public function upload_post()
    {
        try {
            $user_id = get_user_id();

            if (!$user_id) {
                $this->response([
                    'success' => false,
                    'message' => '로그인이 필요합니다.'
                ], self::HTTP_UNAUTHORIZED);
                return;
            }

            // 이메일 인증 확인
            if (!$this->user_m->is_email_verified($user_id)) {
                $this->response([
                    'success' => false,
                    'message' => '이메일 인증이 필요합니다.',
                    'email_verification_required' => true
                ], self::HTTP_FORBIDDEN);
                return;
            }

            $article_id = $this->post('article_id', true);

            if (!$article_id || !is_numeric($article_id)) {
                $this->response([
                    'success' => false,
                    'message' => '게시글 ID가 필요합니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 게시글 존재 확인
            $article = $this->article_m->get($article_id);
            if (!$article) {
                $this->response([
                    'success' => false,
                    'message' => '게시글을 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            // 게시글 작성자 확인
            if ($article->user_id != $user_id) {
                $this->response([
                    'success' => false,
                    'message' => '권한이 없습니다.'
                ], self::HTTP_FORBIDDEN);
                return;
            }

            // 첨부파일 개수 확인
            $current_count = $this->attachment_m->count_by_article($article_id);
            $max_files = $this->config->item('max_files_per_article');

            if ($current_count >= $max_files) {
                $this->response([
                    'success' => false,
                    'message' => "게시글당 최대 {$max_files}개의 파일만 첨부할 수 있습니다."
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 파일 업로드 확인
            if (empty($_FILES['file']['name'])) {
                $this->response([
                    'success' => false,
                    'message' => '파일이 선택되지 않았습니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 파일 타입 확인
            $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowed_types = explode('|', $this->config->item('allowed_types')['all']);
            $disallowed_types = $this->config->item('disallowed_types');

            // 위험 확장자 차단
            if (in_array($file_ext, $disallowed_types)) {
                $this->response([
                    'success' => false,
                    'message' => '보안상 허용되지 않는 파일 형식입니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            if (!in_array($file_ext, $allowed_types)) {
                $this->response([
                    'success' => false,
                    'message' => '허용되지 않는 파일 형식입니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // MIME 타입 확인
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['file']['tmp_name']);
            finfo_close($finfo);

            // 이미지 여부 확인
            $is_image = in_array($mime_type, $this->config->item('image_mime_types'));

            // 파일 크기 확인
            $max_size = $is_image ?
                $this->config->item('max_size_image') * 1024 :
                $this->config->item('max_size_file') * 1024;

            if ($_FILES['file']['size'] > $max_size) {
                $max_size_mb = round($max_size / 1024 / 1024, 1);
                $this->response([
                    'success' => false,
                    'message' => "파일 크기는 최대 {$max_size_mb}MB까지 허용됩니다."
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            // 업로드 디렉토리 생성
            $upload_path = $this->config->item('upload_path_attachments');
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }

            // 파일 업로드 설정
            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = implode('|', $allowed_types);
            $config['max_size'] = round($max_size / 1024); // KB 단위
            $config['encrypt_name'] = TRUE;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('file')) {
                $this->response([
                    'success' => false,
                    'message' => '파일 업로드 실패: ' . $this->upload->display_errors('', '')
                ], self::HTTP_INTERNAL_ERROR);
                return;
            }

            $upload_data = $this->upload->data();

            $thumbnail_path = null;

            // 이미지인 경우 썸네일 생성 및 리사이징
            if ($is_image) {
                $thumbnail_path = $this->create_thumbnail($upload_data['full_path']);
                $this->resize_image($upload_data['full_path']);
            }

            // DB에 저장
            $attachment_data = [
                'article_id' => $article_id,
                'original_name' => $_FILES['file']['name'],
                'stored_name' => $upload_data['file_name'],
                'file_path' => $upload_data['full_path'],
                'file_size' => $upload_data['file_size'] * 1024, // bytes로 저장
                'mime_type' => $mime_type,
                'is_image' => $is_image ? 1 : 0,
                'thumbnail_path' => $thumbnail_path
            ];

            $attachment_id = $this->attachment_m->create($attachment_data);

            $this->response([
                'success' => true,
                'message' => '파일이 업로드되었습니다.',
                'data' => [
                    'id' => $attachment_id,
                    'original_name' => $attachment_data['original_name'],
                    'file_size' => $attachment_data['file_size'],
                    'is_image' => $is_image,
                    'thumbnail_url' => $thumbnail_path ? base_url('uploads/thumbnails/' . basename($thumbnail_path)) : null
                ]
            ], self::HTTP_CREATED);

        } catch (Exception $e) {
            log_message('error', 'File upload error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => '파일 업로드 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 썸네일 생성
     *
     * @param string $source_path 원본 이미지 경로
     * @return string|null 썸네일 경로
     */
    private function create_thumbnail($source_path)
    {
        try {
            $thumbnail_path = $this->config->item('upload_path_thumbnails');

            if (!is_dir($thumbnail_path)) {
                mkdir($thumbnail_path, 0755, true);
            }

            $config['image_library'] = 'gd2';
            $config['source_image'] = $source_path;
            $config['new_image'] = $thumbnail_path;
            $config['maintain_ratio'] = $this->config->item('thumbnail_maintain_ratio');
            $config['width'] = $this->config->item('thumbnail_width');
            $config['height'] = $this->config->item('thumbnail_height');

            $this->load->library('image_lib');
            $this->image_lib->initialize($config);

            if (!$this->image_lib->resize()) {
                log_message('error', 'Thumbnail creation failed: ' . $this->image_lib->display_errors('', ''));
                $this->image_lib->clear();
                return null;
            }

            $this->image_lib->clear();

            return $thumbnail_path . basename($source_path);

        } catch (Exception $e) {
            log_message('error', 'Thumbnail creation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 이미지 리사이징
     *
     * @param string $source_path 원본 이미지 경로
     * @return bool 성공 여부
     */
    private function resize_image($source_path)
    {
        try {
            $config['image_library'] = 'gd2';
            $config['source_image'] = $source_path;
            $config['maintain_ratio'] = TRUE;
            $config['width'] = $this->config->item('max_image_width');
            $config['height'] = $this->config->item('max_image_height');
            $config['master_dim'] = 'auto';

            $this->load->library('image_lib');
            $this->image_lib->initialize($config);

            if (!$this->image_lib->resize()) {
                log_message('error', 'Image resize failed: ' . $this->image_lib->display_errors('', ''));
                return false;
            }

            $this->image_lib->clear();
            return true;

        } catch (Exception $e) {
            log_message('error', 'Image resize error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 파일 다운로드
     * GET /rest/attachment/download/{id}
     */
    public function download_get($id = null)
    {
        try {
            if (!$id || !is_numeric($id)) {
                $this->response([
                    'success' => false,
                    'message' => '첨부파일 ID가 필요합니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $attachment = $this->attachment_m->get($id);

            if (!$attachment) {
                $this->response([
                    'success' => false,
                    'message' => '첨부파일을 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            // 파일 존재 확인
            if (!file_exists($attachment->file_path)) {
                $this->response([
                    'success' => false,
                    'message' => '파일이 존재하지 않습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            // 다운로드 횟수 증가
            $this->attachment_m->increment_download_count($id);

            // 헤더 설정
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($attachment->original_name) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($attachment->file_path));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            // 출력 버퍼 정리 후 스트리밍
            ob_clean();
            flush();
            readfile($attachment->file_path);
            exit;

        } catch (Exception $e) {
            log_message('error', 'File download error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => '파일 다운로드 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 첨부파일 삭제
     * DELETE /rest/attachment/{id}
     */
    public function index_delete($id = null)
    {
        try {
            $user_id = get_user_id();

            if (!$user_id) {
                $this->response([
                    'success' => false,
                    'message' => '로그인이 필요합니다.'
                ], self::HTTP_UNAUTHORIZED);
                return;
            }

            if (!$id || !is_numeric($id)) {
                $this->response([
                    'success' => false,
                    'message' => '첨부파일 ID가 필요합니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $attachment = $this->attachment_m->get($id);

            if (!$attachment) {
                $this->response([
                    'success' => false,
                    'message' => '첨부파일을 찾을 수 없습니다.'
                ], self::HTTP_NOT_FOUND);
                return;
            }

            // 게시글 작성자 확인
            $article = $this->article_m->get($attachment->article_id);
            if ($article->user_id != $user_id) {
                $this->response([
                    'success' => false,
                    'message' => '권한이 없습니다.'
                ], self::HTTP_FORBIDDEN);
                return;
            }

            // 파일 삭제
            if (file_exists($attachment->file_path)) {
                unlink($attachment->file_path);
            }

            // 썸네일 삭제
            if ($attachment->thumbnail_path && file_exists($attachment->thumbnail_path)) {
                unlink($attachment->thumbnail_path);
            }

            // DB에서 삭제
            $this->attachment_m->delete($id);

            $this->response([
                'success' => true,
                'message' => '첨부파일이 삭제되었습니다.'
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'File deletion error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => '파일 삭제 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * 게시글의 첨부파일 목록 조회
     * GET /rest/attachment/list/{article_id}
     */
    public function list_get($article_id = null)
    {
        try {
            if (!$article_id || !is_numeric($article_id)) {
                $this->response([
                    'success' => false,
                    'message' => '게시글 ID가 필요합니다.'
                ], self::HTTP_BAD_REQUEST);
                return;
            }

            $attachments = $this->attachment_m->get_by_article($article_id);

            $response_data = [];
            foreach ($attachments as $attachment) {
                $image_url = $attachment->is_image ?
                    base_url('uploads/attachments/' . basename($attachment->file_path)) : null;
                $thumbnail_url = $attachment->thumbnail_path ?
                    base_url('uploads/thumbnails/' . basename($attachment->thumbnail_path)) : $image_url;

                $response_data[] = [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'file_size' => $attachment->file_size,
                    'mime_type' => $attachment->mime_type,
                    'is_image' => (bool)$attachment->is_image,
                    'thumbnail_url' => $thumbnail_url,
                    'image_url' => $image_url,
                    'download_count' => $attachment->download_count,
                    'created_at' => $attachment->created_at
                ];
            }

            $this->response([
                'success' => true,
                'data' => $response_data
            ], self::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Attachment list error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => '첨부파일 목록 조회 중 오류가 발생했습니다.'
            ], self::HTTP_INTERNAL_ERROR);
        }
    }
}