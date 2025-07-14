<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function _output($output) {
        // header view
        $header = $this->load->view('header_v', [
            'debugbarRenderer' => null
        ], TRUE);

        // footer view
        $footer = $this->load->view('footer_v', [
            'debugbarRenderer' => null
        ], TRUE);

        echo $header . $output . $footer;
    }
}
