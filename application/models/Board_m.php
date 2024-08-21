<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Board_m extends CI_Model
{
    public function get()
    {
        $query = $this->db->get('boards');

        return $query->result();
    }

    public function exists($id)
    {
        return false;
    }
}


