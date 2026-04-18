<?php
    if (!function_exists('mysqli_init') && !extension_loaded('mysqli')) {
        echo 'you dont have';
     } else {
        echo 'you have!';
     }
     
     phpinfo();
?>
