<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $content = $_POST['content'];

    $captcha = $_POST['captcha'];
    //method of connection $tibbi = $_POST['tibbi'];
    // $geyritibbi = $_POST['geyritibbi'];

    // $to = 'halilov.lib@gmail.com';
$to = 'dakershteyn@a-hroup.az';
//$to = 'dakershteyn@a-group.az';
   $from = 'contact@a-group.az';



 
    
   // if($captcha = 'kwjp')

                            // To send HTML mail, the Content-type header must be set
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        
            // Create email headers
            $headers .= 'From: '.$from."\r\n".
            'Reply-To: '.$from."\r\n" .
            'X-Mailer: PHP/' . phpversion();
        
            // Subject
            $subject = 'Пользователь захотел связаться с ним (через сайт)';
        
            // Compose a simple HTML email message
            $message = '<!doctype html><html><head><meta charset="UTF-8"></head><body>';
            $message .= '<h1 style="color:black !important;">Пользователь запросил связаться с ним через сайт</h1>';
            $message .= '<h4 style="color:black !important;"> ФИО: ' . $fullname . '</h4>';
            $message .= '<h4 style="color:black !important;"> Номер: ' . $phone . '</h4>';
            $message .= '<p style="color:black !important;"> Сообщение: ' . $content . '</p>';
            $message .= '</body></html>';
        
            // Sending email
            $mail = new PHPMailer();
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP(); // Use SMTP protocol
            $mail->Host = 'smtp.gmail.com'; // Specify  SMTP server
            $mail->SMTPAuth = true; // Auth. SMTP
            $mail->Username = 'Agroup.sigorta.asc@gmail.com'; // Mail who send by PHPMailer
            $mail->Password = 'kctlmcosqklanpxs'; // your pass mail box
            $mail->SMTPSecure = 'ssl'; // Accept SSL
            $mail->Port = 465; // port of your out server
            $mail->setFrom($from); // Mail to send at
            $mail->addAddress($to); // Add sender
            $mail->AddCC('esadiqova@a-group.az');
            $mail->addReplyTo($from); // Adress to reply
            $mail->isHTML(true); // use HTML message
            $mail->Subject = $subject;
            $mail->Body = $message;
    
            // SEND
            if( !$mail->send() ){
                echo 'error';
                exit;
            }
            else{
                $data['status'] = 1;
                $data['message'] = 'success';
            
                echo json_encode($data);
    
    
            die();
            }

?>
