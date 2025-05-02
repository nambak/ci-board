<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Comment_m extends CI_Model
{
    public function create($postId, $comment)
    {
        $this->db->insert('comments', [
            'post_id' => $postId,
            'comment' => $comment
        ]);
    }
}