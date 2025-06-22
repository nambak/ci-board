<?php
defined('BASEPATH') || exit('No direct script access allowed');

class User_m extends CI_Model
{
    public function get_user_by_email($email)
    {
        $query = $this->db->get_where('users', ['email' => $email]);

        return $query->row();
    }
}
