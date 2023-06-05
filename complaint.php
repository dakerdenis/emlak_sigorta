<?php

        $name = $_POST['name'];
        $surname = $_POST['surname'];
        $lastname = $_POST['lastname'];
        $fincod = $_POST['fincode'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $content = $_POST['content'];
    
        // $to = 'akhalilov@beylitech.az';
        $to = 'dakerdenis@gmail.com';
        $from = 'complaints@a-group.az';
    
        // To send HTML mail, the Content-type header must be set
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
    
        // Create email headers
        $headers .= 'From: '.$from."\r\n".
        'Reply-To: '.$from."\r\n" .
        'X-Mailer: PHP/' . phpversion();
    
        // Subject
        $subject = 'Жалоба (через сайт)';
    
        $fio = $name . " " . $surname . " " . $lastname;
        $phone = preg_replace('~[^0-9]+~','',$phone);
        // Compose a simple HTML email message
        $message = '<!doctype html><html><head><meta charset="UTF-8"></head><body>';
        $message .= '<h1 style="color:black !important;">Пользователь отправил жалобу через сайт</h1>';
        $message .= '<h4 style="color:black !important;"> ФИО: ' . $fio . '</h4>';
        $message .= '<h4 style="color:black !important;"> Номер: +' . $phone . '</h4>';
        $message .= '<h4 style="color:black !important;"> Fin Code: +' . $fincod . '</h4>';
        $message .= '<h4 style="color:black !important;"> E-mail: ' . $email . '</h4>';
        $message .= '<p style="color:black !important;"> Сообщение: ' . $content . '</p>';
        $message .= '</body></html>';
    
        // Sending email
        mail($to, $subject, $message, $headers);
        //////********* */
        echo  $to ." ".  $subject ." ".  $message ." ".  $headers;
        $data['status'] = 1;
        $data['message'] = 'success';
    
        echo json_encode($data);
        session_start();
        $_SESSION['success_msg'] = " Ugurla göndərdi ! ";

       // header("Location: https://a-group.az/complaints/");

?>