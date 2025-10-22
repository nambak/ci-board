<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Board_m extends CI_Model
{
    public function get($id = null)
    {
        if ($id === null) {
            $query = $this->db->get('boards');
        } else {
            $query = $this->db->get_where('boards', ['id' => $id]);
        }

        return $query->result();
    }

    public function exists($id)
    {
        $query = $this->db->get_where('boards', ['id' => $id]);
        $result = $query->num_rows();
        return ($result > 0);
    }

    public function create($name, $description = '')
    {
        $this->db->insert('boards', [
            'name' => $name,
            'description' => $description
        ]);

        return $this->db->insert_id();
    }
}


