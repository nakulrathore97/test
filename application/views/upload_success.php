<!DOCTYPE html>
<html lang="en">

<head>
    <title>Upload Form</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href=/css/bootstrap.min.css> <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous"> -->

</head>

<body>
    <div class="container" style="padding-top=25px;">
        <h3>Your file was successfully uploaded!</h3>

        <h5>
            <?php
            // echo $upload_data['file_name'];
            // $str = "./uploads/" . $upload_data['file_name'];
            // echo "<br>";
            // echo $str;
            // echo "<br>";
            echo $records . " records were stored.";
            echo "<br>";
            //echo $gg;
            ?>
            <!-- <?php foreach ($upload_data as $item => $value) : ?>
        <li><?php echo $item; ?>: <?php echo $value; ?></li>
<?php endforeach; ?> -->
        </h5>

        <p><?php echo anchor('upload', 'Upload Another File!'); ?></p>
    </div>
</body>

</html>