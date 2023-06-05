<?php

$connection = mysqli_connect('localhost', 'root', '', 'emlak');
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
  } echo "";
  if(isset($_POST['submit'])){
    $name   = $_POST['city'];
    $surname   = $_POST['clinic_name'];
    $number  = $_POST['phone'];
    $message = $_POST['adress'];

  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css">
    <title>Əmlak Siğorta</title>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
                    <a  href="#" class="language_element active_">
                        AZ
                    </a>
                    <a  href="#" class="language_element">
                        EN
                    </a>
                    <a  href="#" class="language_element">
                        RU
                    </a>
                </div>
        </div>

        <div class="main__image__pc">
            <img src="./Illstration.svg" alt="">
        </div>

        <div class="main__form__container">
            <div class="main__container__desc">
                <p>
                    Əmlak Sığortası üçün müraciət
                </p>
            </div>
            
    
            <div class="main__container__form">
                <form action="./index.html" id="form" method="POST">
                    <div class="form__desc">
                        <p>Müştəri məlumatları</p>
                    </div>
    
                    <div class="form__input__container">
                        <input type="text" id="name" placeholder="Ad">
                    </div>
                    <div class="form__input__container">
                        <input type="text" id="surname"  placeholder="Soyad">
                    </div>
                    <div class="form__input__container">
                        <input type="text" id="number"  placeholder="Əlaqə nömrəsi">
                    </div>
                    <div class="form__input__container_textarea">
                        <textarea name="message" id="message" placeholder="Qeyd"></textarea>
                    </div>
                    <br>
                    <div class="g-recaptcha" data-sitekey="6Lcirw8hAAAAAK2-oS0g_eueKVqYAtpwqNxlX0x0"></div>
                    <br>
                    <div class="text_error" id="recaptchaError"></div>
    
                    <div class="form__button">
                        <button name="submit" type="submit" class="g-recaptcha" 
                       >Müraciet Et</button>
                    </div>
                </form>
            </div>
        </div>


    </div>

    
    <script>
        $(document).ready(function(){
                $("#form").on("submit", function(event){
                    event.preventDefault()
            var captcha = grecaptcha.getResponse();

            if (!captcha.length){
                $('#recaptchaError').text("Captcha error ");
            } else {
                $('#recaptchaError').text('');
                document.getElementById("form").submit();

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

    <!----front end  CREATED BY DENIS AKERSHTEYN for 4 hours
  ///////////////////////////////////
  https://www.linkedin.com/in/denis-akershteyn-985358197/
  https://github.com/dakerdenis/
  ///////////////////////////////////
  for connecting https://daker.site/ 
  -------->
</body>
</html>