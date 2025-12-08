<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attachment Model
 *
 * 첨부파일 관련 데이터베이스 작업을 담당하는 모델
 */
class Attachment_m extends CI_Model
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 첨부파일 생성
     *
     * @param array $data 첨부파일 데이터
     * @return int 생성된 첨부파일 ID
     */
    public function create($data)
    {
        $this->db->insert('attachments', $data);
        return $this->db->insert_id();
    }

    /**
     * 첨부파일 조회 (ID로)
     *
     * @param int $id 첨부파일 ID
     * @return object|null 첨부파일 정보
     */
    public function get($id)
    {
        $this->db->where('id', $id);
        $query = $this->db->get('attachments');
        return $query->row();
    }

    /**
     * 게시글의 첨부파일 목록 조회
     *
     * @param int $article_id 게시글 ID
     * @return array 첨부파일 목록
     */
    public function get_by_article($article_id)
    {
        $this->db->where('article_id', $article_id);
        $this->db->order_by('created_at', 'ASC');
        $query = $this->db->get('attachments');
        return $query->result();
    }

    /**
     * 첨부파일 삭제
     *
     * @param int $id 첨부파일 ID
     * @return bool 삭제 성공 여부
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('attachments');
        return $this->db->affected_rows() > 0;
    }

    /**
     * 게시글의 모든 첨부파일 삭제
     *
     * @param int $article_id 게시글 ID
     * @return bool 삭제 성공 여부
     */
    public function delete_by_article($article_id)
    {
        $this->db->where('article_id', $article_id);
        $this->db->delete('attachments');
        return $this->db->affected_rows() >= 0;
    }

    /**
     * 다운로드 횟수 증가
     *
     * @param int $id 첨부파일 ID
     * @return bool 성공 여부
     */
    public function increment_download_count($id)
    {
        $this->db->where('id', $id);
        $this->db->set('download_count', 'download_count + 1', FALSE);
        $this->db->update('attachments');
        return $this->db->affected_rows() > 0;
    }

    /**
     * 게시글의 첨부파일 개수 조회
     *
     * @param int $article_id 게시글 ID
     * @return int 첨부파일 개수
     */
    public function count_by_article($article_id)
    {
        $this->db->where('article_id', $article_id);
        return $this->db->count_all_results('attachments');
    }

    /**
     * 게시글의 첨부파일 총 용량 조회
     *
     * @param int $article_id 게시글 ID
     * @return int 총 용량 (bytes)
     */
    public function get_total_size_by_article($article_id)
    {
        $this->db->select_sum('file_size');
        $this->db->where('article_id', $article_id);
        $query = $this->db->get('attachments');
        $result = $query->row();
        return $result ? (int)$result->file_size : 0;
    }

    /**
     * 이미지 첨부파일 목록 조회
     *
     * @param int $article_id 게시글 ID
     * @return array 이미지 첨부파일 목록
     */
    public function get_images_by_article($article_id)
    {
        $this->db->where('article_id', $article_id);
        $this->db->where('is_image', 1);
        $this->db->order_by('created_at', 'ASC');
        $query = $this->db->get('attachments');
        return $query->result();
    }

    /**
     * 일반 파일 첨부파일 목록 조회
     *
     * @param int $article_id 게시글 ID
     * @return array 일반 파일 첨부파일 목록
     */
    public function get_files_by_article($article_id)
    {
        $this->db->where('article_id', $article_id);
        $this->db->where('is_image', 0);
        $this->db->order_by('created_at', 'ASC');
        $query = $this->db->get('attachments');
        return $query->result();
    }
}