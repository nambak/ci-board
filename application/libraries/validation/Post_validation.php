<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/validation/Base_validation.php';

class Post_validation extends Base_validation
{
    public function __construct()
    {
        parent::__construct();
        $this->CI->load->model('board_m');
        $this->set_custom_message();
    }
    public function validate()
    {
        $this->CI->form_validation->set_rules('title', 'title', 'required|trim|max_length[255]|alpha_numeric_spaces');
        $this->CI->form_validation->set_rules('content', 'content', 'required|trim|alpha_numeric_spaces');
        $this->CI->form_validation->set_rules('board_id', 'board_id', 'required|numeric|callback_board_exists');

        return $this->CI->form_validation->run();
    }

    public function board_exists($board_id)
    {
        // Board 모델의 메서드 사용
        if ($this->CI->board_m->exists($board_id)) {
            return true;
        } else {
            return false;
        }
    }

    private function set_custom_message()
    {
        $this->CI->form_validation->set_message('required', '{field}은(는) 필수 입력 항목입니다.');
        $this->CI->form_validation->set_message('max_length', '{field}은(는) {param}자를 초과할 수 없습니다.');
        $this->CI->form_validation->set_message('alpha_numeric_spaces', '{field}은(는) 한글, 영문, 숫자, 공백만 입력 가능합니다.');
        $this->CI->form_validation->set_message('numeric', '{field}은(는) 숫자만 입력 가능합니다.');

        $this->CI->form_validation->set_message('board_exists', '존재하지 않는 게시판입니다.');
    }

    public function get_errors()
    {
        return validation_errors();
    }
}