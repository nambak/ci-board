<?php

use Faker\Factory as FakerFactory;

class UserSeeder extends CI_Controller
{
    private $faker;

    public function __construct()
    {
        parent::__construct();

        $this->faker = FakerFactory::create();
        $this->load->model('board_m');
    }

    public function run()
    {
        $data = [];

        for ($i = 0; $i < 10; $i++) {
            $data[] = [
                'username' => $this->faker->userName,
                'email'    => $this->faker->email,
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}

