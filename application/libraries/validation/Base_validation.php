<?php
defined('BASEPATH') OR exit('No direct script access allowed');

abstract class Base_validation
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('form_validation');
    }

    abstract public function validate();
}