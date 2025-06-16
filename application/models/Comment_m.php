<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Comment_m extends CI_Model
{
    public function create($postId, $comment, $writerId)
    {
        $this->db->insert('comments', [
            'post_id'   => $postId,
            'writer_id' => $writerId,
            'comment'   => $comment
        ]);
    }

    public function fetchByPost($id)
    {
        $query = $this->db->select('comments.*, users.name')
            ->from('comments')
            ->join('users', 'users.id = comments.writer_id')
            ->where('comments.post_id', $id)
            ->get();

        return $query->result();
    }
}
