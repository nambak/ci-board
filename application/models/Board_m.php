<?php
defined('BASEPATH') or exit('No direct script access allowed');

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
        $name = trim((string)$name);
        $description = trim((string)$description);

        $this->db->insert('boards', [
            'name'        => $name,
            'description' => $description
        ]);

        return $this->db->insert_id();
    }

    public function update($id, $name, $description = '')
    {
        $name = trim((string)$name);
        $description = trim((string)$description);

        $this->db->where('id', $id);

        return $this->db->update('boards', [
            'name'        => $name,
            'description' => $description
        ]);
    }

    public function delete($id)
    {
        // CASCADE 설정으로 관련 posts와 comments가 자동 삭제됨
        $this->db->where('id', $id);
        return $this->db->delete('boards');
    }
}


