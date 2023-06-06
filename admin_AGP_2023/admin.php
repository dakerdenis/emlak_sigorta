<?php
session_start();
if (empty($_SESSION["authenticated"]) || $_SESSION["authenticated"] != 'true') {
    header('Location: ./index.php');
    exit();
}






?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin page</title>

    <link rel="stylesheet" href="./style/style.css">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
</head>

<body>
    <div class="main__wrapper">
        <div class="main__header">
            <div class="header__Logo">
                <img src="./imgs/logo.svg" alt="">
            </div>

            <div class="header__username__exit">
                <div class="header__username__image">
                    <img src="./imgs/account.png" alt="">
                </div>
                <div class="header__username__name">
                    <p>Admin</p>
                </div>
                <div class="header__username__exit">
                    <form action="./logout.php" method="POST" id="logout">
                        <button>Logout</button>
                    </form>
                </div>
            </div>

        </div>



        <div class="main__list">
            <div class="main__list__desc">
                <div class="list__desc__info">
                    <div class="list__desc__id">
                        id
                    </div>
                    <div class="list__desc__name">
                        <p>имя</p>
                    </div>
                    <div class="list__desc__surname">
                        <p>фамилия</p>
                    </div>
                    <div class="list__desc__phone">
                        <p>телефон</p>
                    </div>
                    <div class="list__desc__wp">
                        <p>Whatsapp</p>
                    </div>

                    <div class="list__desc__delete">
                        <p>Delete</p>
                    </div>
                </div>

                <div class="main__list__wrapper">

                <?php

                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "emlak";

                $limit = 100; // Количество строк на странице
                $page = isset($_GET['page']) ? $_GET['page'] : 1; // Текущая страница, получаемая из URL
                $start = ($page - 1) * $limit; // Начальная позиция для выборки данных
                $conn = new mysqli($servername, $username, $password, $dbname);
                // Проверка подключения
                if ($conn->connect_error) {
                    die("Ошибка подключения: " . $conn->connect_error);
                }


                $countQuery = "SELECT COUNT(*) AS total FROM emlak";
                $countResult = $conn->query($countQuery);
                $row = $countResult->fetch_assoc();
                $totalRows = $row['total'];


                // Выполнение запроса для получения данных с учетом пагинации
                $sql = "SELECT * FROM emlak LIMIT $start, $limit";
                $result = $conn->query($sql);

                // Вывод данных на экран
                if ($result->num_rows > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Выводите данные здесь в нужном вам формате
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
                                     <div class="wrapper__element_number">
                                         <p>
                                             <?= $row['number'] ?>
                                         </p>
                                     </div>
                                     <a href="https://wa.me/<?php echo $row['number'] ?>">WhatsApp</a> 
                                 

                                     <a href="https://wa.me/<?php echo $row['number'] ?>">Delete</a> 

                                    </div>
                    

                          <?php                     
                    }
                    

                        
                } else {
                    echo "Нет данных для отображения.";
                }

                // Генерация ссылок для пагинации
                $totalPages = ceil($totalRows / $limit);
                if ($totalPages > 1) {
                    echo "<br>Страницы: ";
                    for ($i = 1; $i <= $totalPages; $i++) {
                        echo "<a href='admin.php?page=$i'>$i</a> ";
                    }
                }


                ?>
                
                </div>
            </div>

        </div>
    </div>

    <script>
        $(".open_popup").click(function () {
            $(this).parent(".popup_main").children(".popup_body").addClass("popup_body_show");
        });

    </script>
</body>

</html>
