<?php
$conn = mysqli_connect('localhost', 'root', '','login_db');

function row_count($result){

    return mysqli_num_rows($result);
}


function query($query){

    global $conn;

    return mysqli_query($conn, $query);
}



function escape($string){

    global $conn;

   return mysqli_real_escape_string($conn, $string);
}

function confirm($result){

    global $conn;

    if (!$result){

        die('QUERY FAILED'. mysqli_error($conn));
    }
}


function fetch_array($result){


   return mysqli_fetch_array( $result);
}
?>