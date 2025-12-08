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

    /**
     * 전체 사용자 목록 조회
     *
     * @param string $order_by 정렬 기준 (기본값: 'id')
     * @param string $order_direction 정렬 방향 (기본값: 'DESC')
     * @return array
     */
    public function get_all_with_counts($order_by = 'id', $order_direction = 'DESC', $limit = null, $offset = null)
    {
        // 허용된 정렬 컬럼만 사용
        $allowed_columns = ['id', 'name', 'email', 'created_at'];
        if (!in_array($order_by, $allowed_columns)) {
            $order_by = 'id';
        }

        // 정렬 방향 검증
        $order_direction = strtoupper($order_direction);
        if (!in_array($order_direction, ['ASC', 'DESC'])) {
            $order_direction = 'DESC';
        }

        $this->db->select('users.*,
        (SELECT COUNT(*) FROM articles WHERE articles.user_id = users.id) as article_count,
        (SELECT COUNT(*) FROM comments WHERE comments.writer_id = users.id) as comment_count');
        $this->db->order_by($order_by, $order_direction);

        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }

        $query = $this->db->get('users');
        return $query->result();
    }

    /**
     * 사용자 권한(role) 업데이트
     *
     * @param int $userId 사용자 ID
     * @param string $role 새로운 권한 (user 또는 admin)
     * @return bool 성공 여부
     */
    public function updateRole($userId, $role)
    {
        try {
            // 권한 값 검증 (화이트리스트)
            $allowedRoles = ['user', 'admin'];
            if (!in_array($role, $allowedRoles)) {
                throw new Exception('Invalid role value');
            }

            $data = [
                'role' => $role,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->where('id', $userId);
            $this->db->update('users', $data);

            return $this->db->affected_rows() > 0;

        } catch (Exception $e) {
            log_message('error', 'User role update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 관리자 수 조회
     *
     * @return int 관리자 사용자 수
     */
    public function countAdmins()
    {
        $this->db->where('role', 'admin');
        return $this->db->count_all_results('users');
    }

    /**
     * 비밀번호 업데이트
     *
     * @param int $user_id 사용자 ID
     * @param string $new_password 새 비밀번호 (평문)
     * @return bool 성공 여부
     */
    public function update_password($user_id, $new_password)
    {
        try {
            $data = [
                'password' => password_hash($new_password, PASSWORD_DEFAULT),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->where('id', $user_id);
            $this->db->update('users', $data);

            return $this->db->affected_rows() > 0;

        } catch (Exception $e) {
            log_message('error', 'Password update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 이메일 인증 토큰 생성 및 저장
     *
     * @param int $user_id 사용자 ID
     * @return string 생성된 인증 토큰
     */
    public function generate_verification_token($user_id)
    {
        try {
            $token = bin2hex(random_bytes(32));

            $data = [
                'verification_token' => $token,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->where('id', $user_id);
            $this->db->update('users', $data);

            return $token;

        } catch (Exception $e) {
            log_message('error', 'Verification token generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 인증 토큰으로 사용자 조회
     *
     * @param string $token 인증 토큰
     * @return object|null 사용자 정보 또는 null
     */
    public function get_by_verification_token($token)
    {
        if (empty($token)) {
            return null;
        }

        $this->db->where('verification_token', $token);
        $query = $this->db->get('users');

        return $query->row();
    }

    /**
     * 이메일 인증 완료 처리
     *
     * @param int $user_id 사용자 ID
     * @return bool 성공 여부
     */
    public function verify_email($user_id)
    {
        try {
            $data = [
                'email_verified_at' => date('Y-m-d H:i:s'),
                'verification_token' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->where('id', $user_id);
            $this->db->update('users', $data);

            return $this->db->affected_rows() > 0;

        } catch (Exception $e) {
            log_message('error', 'Email verification error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 이메일 인증 여부 확인
     *
     * @param int $user_id 사용자 ID
     * @return bool 인증 여부
     */
    public function is_email_verified($user_id)
    {
        $this->db->select('email_verified_at');
        $this->db->where('id', $user_id);
        $query = $this->db->get('users');

        $user = $query->row();
        return $user && !empty($user->email_verified_at);
    }
}
