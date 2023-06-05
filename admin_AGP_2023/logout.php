<?php
session_start();
$_SESSION["authenticated"] = 'false';

header('Location: ./index.php');
exit();

?>
