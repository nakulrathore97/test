
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

