<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User_m extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 이메일로 사용자 조회
     * @param string $email
     * @return object|null
     */
    public function get_by_email($email)
    {
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where('email', $email);
        $query = $this->db->get();

        return $query->row();
    }

    /**
     * ID로 사용자 조회
     * @param int $id
     * @return object|null
     */
    public function get($id)
    {
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where('id', $id);
        $query = $this->db->get();

        return $query->row();
    }
}