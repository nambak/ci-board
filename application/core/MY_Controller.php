<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use DebugBar\StandardDebugBar;

class MY_Controller extends CI_Controller {
    protected $debugbar;
    protected $debugbarRenderer;

    public function __construct() {
        parent::__construct();
        $this->debugbar = new StandardDebugBar();
        $this->debugbarRenderer = $this->debugbar->getJavascriptRenderer();
    }

    public function _output($output) {
        // header view에 debugbarRenderer 전달
        $header = $this->load->view('header_v', [
            'debugbarRenderer' => $this->debugbarRenderer
        ], TRUE);

        // footer view에 debugbarRenderer 전달
        $footer = $this->load->view('footer_v', [
            'debugbarRenderer' => $this->debugbarRenderer
        ], TRUE);

        echo $header . $output . $footer;
    }
}
