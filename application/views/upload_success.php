<html>
<head>
<title>Upload Form</title>
</head>
<body>

<h3>Your file was successfully uploaded!</h3>

<ul>
<?php
    echo $upload_data['file_name'];
    $str = "./uploads/" . $upload_data['file_name'];
    echo "<br>";
    echo $str;
    echo "<br>";
    echo $records;
    echo "<br>";
    //echo $gg;
?>
<!-- <?php foreach ($upload_data as $item => $value):?>
<li><?php echo $item;?>: <?php echo $value;?></li>
<?php endforeach; ?> -->
</ul>

<p><?php echo anchor('upload', 'Upload Another File!'); ?></p>

</body>
</html>