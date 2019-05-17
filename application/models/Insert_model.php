
<?php
class Insert_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }
    function form_insert($data)
    {
        // Inserting in Table(my_users) of Database(HS)
        $this->db->insert('my_users', $data);
    }
}
?>

<!-- SQL CREATE statement --$this->load->model('MY_MODEL');>
 use HS; // for using database HS-->
<!-- CREATE TABLE my_users(
    user_id int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, -->
    <!-- full_name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    password varchar(255) NOT NULL,
    created_by int,
    is_admin BOOLEAN,
    created_on TIMESTAMP
) -->