<?php


session_start();
if (empty($_SESSION["authenticated"]) || $_SESSION["authenticated"] != 'true') {
    header('Location: ./index.php');
    exit();
}

$connection = mysqli_connect('localhost', 'root', '', 'emlak');
if(isset($_GET['id'])){

    $id = $_GET['id'];

    $query = "DELETE FROM emlak WHERE `emlak`.`id` = {$id}";

    $delete = mysqli_query($connection, $query);


    if(!$delete){
        die("QUERY FAILED ." . mysqli_error($connection));
    } else {
        echo "SUCCES";
    }

    header("Location: ./admin.php");
}
header("Location: ./admin.php");


?>



