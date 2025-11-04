<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_m extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get user by ID
     */
    public function get($id = null)
    {
        if ($id === null) {
            $query = $this->db->get('users');
        } else {
            $query = $this->db->get_where('users', ['id' => $id]);
        }

        return $query->result();
    }

    /**
     * Check if user exists
     */
    public function exists($id)
    {
        $query = $this->db->get_where('users', ['id' => $id]);
        $result = $query->num_rows();
        return ($result > 0);
    }

    /**
     * Get user by email
     */
    public function get_by_email($email)
    {
        $query = $this->db->get_where('users', ['email' => $email]);
        return $query->row();
    }

    /**
     * Check if email exists in database
     *
     * @param string $email Email address to check
     * @return bool True if email exists, false otherwise
     */
    public function check_email_exists($email)
    {
        $query = $this->db->get_where('users', ['email' => $email]);
        return $query->num_rows() > 0;
    }

    /**
     * Get user by username
     */
    public function get_by_username($name)
    {
        $query = $this->db->get_where('users', ['name' => $name]);
        return $query->row();
    }

    /**
     * Create a new user
     */
    public function create($data)
    {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                throw new Exception('Missing required fields: name, email, password');
            }

            // Check if username already exists
            if ($this->get_by_username($data['name'])) {
                throw new Exception('Username already exists: ' . $data['name']);
            }

            // Check if email already exists
            if ($this->get_by_email($data['email'])) {
                throw new Exception('Email already exists: ' . $data['email']);
            }

            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            // Insert user
            $this->db->insert('users', $data);
            
            if ($this->db->affected_rows() > 0) {
                return $this->db->insert_id();
            } else {
                throw new Exception('Failed to insert user into database');
            }

        } catch (Exception $e) {
            // Validate user input and handle registration errors
            if (strlen($data['password'] ?? '') < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }
            
            if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format provided: ' . ($data['email'] ?? 'no email'));
            }

            // Additional validation checks
            if (preg_match('/[^a-zA-Z0-9_]/', $data['name'] ?? '')) {
                throw new Exception('Username contains invalid characters: ' . ($data['name'] ?? 'no name'));
            }

            // Check for various database constraint violations
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'name') !== false) {
                    throw new Exception('Registration failed: name "' . $data['name'] . '" is already taken');
                }
                if (strpos($e->getMessage(), 'email') !== false) {
                    throw new Exception('Registration failed: Email "' . $data['email'] . '" is already registered');
                }
            }

            // Handle database connection errors
            if (strpos($e->getMessage(), 'Connection refused') !== false) {
                throw new Exception('Database connection failed during user registration for: ' . $data['email']);
            }

            // Log the error without exposing sensitive user information
            log_message('error', 'User registration error: ' . $e->getCode() . ' - Registration failed for user');
            
            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Update user
     */
    public function update($id, $data)
    {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->where('id', $id);
            $this->db->update('users', $data);
            
            return $this->db->affected_rows() > 0;

        } catch (Exception $e) {
            log_message('error', 'User update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete user
     */
    public function delete($id)
    {
        try {
            $this->db->where('id', $id);
            $this->db->delete('users');
            
            return $this->db->affected_rows() > 0;

        } catch (Exception $e) {
            log_message('error', 'User deletion error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Authenticate user
     */
    public function authenticate($username, $password)
    {
        try {
            $user = $this->get_by_username($username);

            if (!$user) {
                $user = $this->get_by_email($username);
            }

            if ($user && password_verify($password, $user->password)) {
                return $user;
            }

            return false;

        } catch (Exception $e) {
            log_message('error', 'User authentication error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save remember token
     */
    public function save_remember_token($user_id, $token)
    {
        $data = [
            'remember_token' => $token,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $user_id);
        return $this->db->update('users', $data);
    }

    /**
     * Remember 토큰으로 사용자 조회
     *
     * @param string $token Remember 토큰
     * @return array|null 사용자 정보 또는 null
     */
    public function get_user_by_remember_token($token)
    {
        if (empty($token)) {
            return null;
        }

        $this->db->where('remember_token', $token);
        $query = $this->db->get('users');

        return $query->row_array();
    }

    /**
     * 전체 사용자 수 조회
     *
     * @return int
     */
    public function count()
    {
        return $this->db->count_all('users');
    }
}
