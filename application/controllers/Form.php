<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Form extends CI_Controller
{

    public function index()
    {
        $this->load->helper(array('form', 'url'));

        $this->load->library('form_validation');

        //loading model
        $this->load->model('Insert_model');

        $this->form_validation->set_rules('username', 'Username', 'required');
        $this->form_validation->set_rules(
            'password',
            'Password',
            'required',
            array('required' => '%s must have 6 characters.')
        );
        $this->form_validation->set_rules('passconf', 'Password Confirmation', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required');

        if ($this->form_validation->run() == FALSE) {

            $this->load->view('myform');
        } else {
            $data = array(
                'user_id' => null,
                'full_name' => $this->input->post('username'),
                'email' => $this->input->post('email'),
                'password' => $this->input->post('password'),
                'created_by' => (-1),
                'is_admin' => true,
                //'created_on' => time()
            );
            $this->db->set('created_on', 'NOW()', FALSE);
            $flag = $this->Insert_model->form_insert($data);
            //$this->load->view('formsuccess');
            redirect('/upload','refresh');
        }
    }
}
