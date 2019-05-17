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

The logs are located in ./application/logs (for instances such as error while sending mail)

