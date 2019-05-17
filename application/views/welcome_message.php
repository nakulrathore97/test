<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html>

<head>
    <title>Sample Login form</title>
</head>

<body>
    Sign Up
    <form action="" method="post">
        <input type='text' name='fullname' placeholder='Full Name'>
        <br>
        <input type='email' name='email' placeholder='Email'>
        <br>
        <input type='password' name='password' placeholder='Password(6 or more characters)'>
        <br>
        <button type='submit' name='submit'>CREATE ACCOUNT</button>
    </form>
    <?php 
        if(strlen($this->input->post('name'))<3)
        {
            echo "Name should contain more than 2 characters";
        }

    ?>
</body>

</html>