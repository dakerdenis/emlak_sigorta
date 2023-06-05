<?php
$username = null;
$password = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST["username"]) && !empty($_POST["password"])) {
        $username = $_POST["username"];
        $password = $_POST["password"];

        if ($username == 'admin' && $password == 'Agroup2023') {
            session_start();
            $_SESSION["authenticated"] = 'true';
            header('Location: ./admin.php');
            exit();
        } else {
            header('Location: ./index.php');
            exit();
        }

    } else {
        header('Location: ./index.php');
        exit();
    }
} else {
    ?>


                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta http-equiv="X-UA-Compatible" content="IE=edge">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Sign in</title>
                    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                    <link rel="stylesheet" href="./style.css"> 
                </head>
                <body>
                    <form id="login" method="POST">
                        <h3>Login Here</h3>
      
                        <label for="username">Username</label>
                        <input type="text" placeholder="username" name="username" id="username">
      
                        <label for="password">Password</label>
                        <input type="password" placeholder="password" name="password" id="password">
                        <br>
                        <div class="g-recaptcha" data-sitekey="6Lcirw8hAAAAAK2-oS0g_eueKVqYAtpwqNxlX0x0"></div>
                        <div class="text_error" id="recaptchaError"></div>
                        <button name="login" type="submit" class="g-recaptcha" 
                       >Log In</button>

                      </form>


                      <div class="created__by">
                        Created by <a href="https://www.daker.site">DAKER</a>
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

<?php } ?>