<?php
use ElasticEmailApi\Email;
use ElasticEmailClient\ApiConfiguration;

class Upload extends CI_Controller
{

        public function __construct()
        {
                parent::__construct();
                $this->load->helper(array('form', 'url'));
        }

        public function index()
        {
                $this->load->view('upload_form', array('error' => ' '));
        }

        public function do_upload()
        {
                $this->load->model('Insert_model');

                $config['upload_path']          = './uploads/';
                $config['allowed_types']        = 'csv';
                $config['max_size']             = 100;

                $this->load->library('upload', $config);

                //require_once 'ElasticEmailClient.php';

                //ElasticEmailClient\ApiConfiguration::SetApiKey("dfd01946-4450-45a3-b414-d13087b56302");

                if (!$this->upload->do_upload('userfile')) {
                        $error = array('error' => $this->upload->display_errors());

                        $this->load->view('upload_form', $error);
                } else {
                        $data = array('upload_data' => $this->upload->data());

                        //file handling
                        $str = "./uploads/" . $this->upload->file_name;
                        $file = fopen($str, "r");

                        $num = 0;
                        //$conf= new ApiConfiguration(array(
                        //        'apiKey'=>"dfd01946-4450-45a3-b414-d13087b56302",
                        //        'apiUrl'=>'https://api.elasticemail.com/v2/'));

                        // $EEemail = new Email($conf);
                        //$EEemail.SetApiKey("dfd01946-4450-45a3-b414-d13087b56302");


                        while (!feof($file)) {
                                $arr = fgetcsv($file);

                                //insertion
                                if ($arr[0] != null) {

                                        $fdata = array(
                                                'user_id' => null,
                                                'full_name' => $arr[0], //$this->input->post('username'),
                                                'email' => $arr[1], //$this->input->post('email'),
                                                'password' => "ggwpggwp", //$this->input->post('password'),
                                                'created_by' => (-1),
                                                'is_admin' => true,
                                                //'created_on' => time()
                                        );

                                        // try {
                                        //         $response = $EEemail->Send(
                                        //                 "PHP TASK",
                                        //                 "nakulrathore97@gmail.com", 
                                        //                 "Nakul Rathore", 
                                        //                 null, 
                                        //                 null, 
                                        //                 null, 
                                        //                 null, 
                                        //                 null, 
                                        //                 null, 
                                        //                 array('$arr[1]'), 
                                        //                 array('$arr[1]'), 
                                        //                 array(), 
                                        //                 array(), 
                                        //                 array(), 
                                        //                 array(), 
                                        //                 null, 
                                        //                 null, 
                                        //                 null,
                                        //                 "Dear ".$arr[0].",\n
                                        //                 You are invited to this sample website.\n
                                        //                 Here are your credentials:\n
                                        //                 username: ".$arr[1]."\n
                                        //                 password: password\n
                                        //                 \n
                                        //                 Sincerely,\n
                                        //                 PHP Task", 
                                        //                 null, 
                                        //                 null, 
                                        //                 null, 
                                        //                 null, 
                                        //                 null, 
                                        //                 null);
                                        // } catch (Exception $e) {
                                        //         $e->getMessage();
                                        // }
                                        // $ggwpez=file_get_contents(`
                                        set_error_handler(function(){
                                                
                                                //to handle file_get_contents errorrs
                                        });
                                        $urlstr = "https://api.elasticemail.com/v2/email/send?apikey=dfd01946-4450-45a3-b414-d13087b56302&&subject=PHP%20TASK%20INVITE&to=" . $arr[1] . "&from=nakulrathore97@gmail.com&fromName=Nakul%20Rathore&bodyText=Dear%20" . $arr[0] . "%2C%0AYou%20are%20invited%20to%20this%20sample%20website.%0AHere%20are%20your%20credentials%3A%0Ausername%3A%20" . $arr[1] . "%0Apassword%3A%20password%0A%0ASincerely%2C%0APHP%20Task%0A&trackClicks=false&trackOpens=false&msgFromName=Nakul%20Rathore";
                                        $ggwpez = file_get_contents($urlstr);
                                        restore_error_handler();
                                        $this->db->set('created_on', 'NOW()', FALSE);
                                        $flag = $this->Insert_model->form_insert($fdata);
                                        $num = $num + 1;
                                }
                        }

                        $data += array('records' => $num);
                        $this->load->view('upload_success', $data);
                }
        }
}
