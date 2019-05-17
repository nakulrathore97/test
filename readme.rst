###################
PHP TASK
###################
The application is hosted at http://18.224.141.242/index.php/form

The application takes sign up information from the user and stores it in database.
Additionally, the application takes in .csv files and stores its contents and mails the recipients listed in the file.

The structure of the .csv file is:

invite_username, invite_mailid

example:

nakul,nakulrathore97@gmail.com


*******************
Setup Information
*******************
The application was tested on Ubuntu 18.04 with the environment set up as mentioned in the following link.
https://www.hugeserver.com/kb/how-install-codeigniter-apache-php7-mariadb10-ubuntu16/

The following packages were used:

Codeigniter 3.1.7

php 7.1

mariadb 10.2

apache2

ElasticEmail API

***********
Structure
***********
The control logic is in the ./application/controllers/ folder.

The files for the control logic are:
Form.php
Upload.php

The views are rendered from file located in ./application/views

********
Issues
********
https://elasticemail.com/api-documentation/integration-libraries/introduction-to-our-php-api-integration-library/
This is the PHP library that they want to use.

The file with the mail logic is /application/controllers/Upload.php line 107.

The library is a single file present in /application/controllers/ElasticEmailClient.php

The library calls guzzlehttp (a php client http://docs.guzzlephp.org/en/stable/) which I am not able to integrate.
guzzlehttp itself requires composer (a dependency manager https://getcomposer.org/) to install.
I am getting some config errors here.

The project is hosted using apache2.

