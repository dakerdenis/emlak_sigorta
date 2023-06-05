<?php


session_start();
if (empty($_SESSION["authenticated"]) || $_SESSION["authenticated"] != 'true') {
    header('Location: ./index.php');
    exit();
}

$connection = mysqli_connect('localhost', 'root', '', 'users');
if(isset($_GET['id'])){

    $id = $_GET['id'];

    $query = "DELETE FROM users WHERE `users`.`id` = {$id}";

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






<div class="main__list__wrapper__element">
                                    <div class="wrapper__element_id">
                                        <?= $row['id'] ?>
                                     </div>
                                     <div class="wrapper__element_name">
                                         <p>
                                             <?= $row['name'] ?>
                                         </p>
                                     </div>
                                     <div class="wrapper__element_surname">
                                         <p>
                                             <?= $row['surname'] ?>
                                         </p>
                                     </div>
                                     <div class="popup_main">
                                         <button class="open_popup">Показать номер</button>
                                         <div class="popup_body">
                                             <div class="popup_back"></div>
                                             <div class="popup_contain">
                                                 <a href="./delete.php?id=<?= $row['id'] ?>" class="popup_close">x</a>
                                                 <br>
                                                 <?= $row['phone'] ?>
                                             </div>
                                         </div>
                                     </div>

                                 </div>