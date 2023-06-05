<?php

$connection = mysqli_connect('localhost', 'root', '', 'emlak');
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
  } echo "";
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $name   = $_POST['name'];
    $surname   = $_POST['surname'];
    $number  = $_POST['number'];
    $message = $_POST['message'];


    $query3 = "INSERT INTO emlak ( name, surname, number, text) ";
    $query3 .= " VALUES ( '{$name}', '{$surname}', '{$number}', '{$message}'); ";
    

    $cities = mysqli_query($connection, $query3);


    if(!$cities){
        die("QUERY FAILED ." . mysqli_error($connection));
    } else {
        echo "SUCCESS";
    }

    header("Location: ./index.php");
    

  }
?>


                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta http-equiv="X-UA-Compatible" content="IE=edge">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Property Insurance</title>
                    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                    <link rel="stylesheet" href="../style.css"> 
                </head>
                <body>
                <div class="main__container">

<div class="language__logo">
        <div class="logo__container">
            <a href="#">
                <img src="./Logo.svg" alt="">
            </a>
        </div>

        <div class="language__container">
            <a  href="../index.php" class="language_element ">
                AZ
            </a>
            <a  href="#" class="language_element active_">
                EN
            </a>
            <a  href="../ru/index.php" class="language_element">
                RU
            </a>
        </div>
</div>

<div class="main__image__pc">
    <img src="../Illstration.svg" alt="">
</div>

<div class="main__form__container">
    <div class="main__container__desc">
        <p>
        Application for Property Insurance
        </p>
    </div>
    

    <div class="main__container__form">
        <form id="login" method="POST">
            <div class="form__desc">
                <p>Customer information</p>
            </div>
            <div class="form__input__container">
                <input type="text" id="name" name="name" required  placeholder="Name">
            </div>
            <div class="form__input__container">
                <input type="text" id="surname" name="surname"   placeholder="Surname">
            </div>
            <div class="form__input__container">
                <input type="text" id="number" name="number"  placeholder="Phone Number">
            </div>
            <div class="form__input__container_textarea">
                <textarea name="message" id="message" name="message" placeholder="Note"></textarea>
            </div>
            <br>
            <div class="g-recaptcha" data-sitekey="6Lcirw8hAAAAAK2-oS0g_eueKVqYAtpwqNxlX0x0"></div>
            <br>
            <div class="text_error" id="recaptchaError"></div>

            <div class="form__button">
                <button name="login" type="submit" class="g-recaptcha" 
               >Apply</button>
            </div>
        </form>
    </div>
</div>


</div>

                      <script>


        $(document).ready(function(){
                $("#login").on("submit", function(event){
                    event.preventDefault()
            var captcha = grecaptcha.getResponse();

            if (!captcha.length){
                $('#recaptchaError').text("Captcha error ");
            } else {
                $('#recaptchaError').text('');
                document.getElementById("login").submit();

              // let dataForm = $(this).serialize()

              // $.ajax({
              //     url: '/index.php',
              //     method: 'post',
              //     dataType: 'html',
              //     data: dataForm,
              //     success: function(data){
              //         console.log(data);

              //         grecaptcha.reset();
              //     }
              // })
            }
                })
        })



                      </script>


                </body>
                </html>

