<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

function sendOTP($to_email, $otp){

    $mail = new PHPMailer(true);

    try{

        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';

        $mail->SMTPAuth = true;

        $mail->Username =
        'bhingarbank07@gmail.com';

        $mail->Password =
        'paaj hjop mgvc uzzn';

        $mail->SMTPSecure = 'tls';

        $mail->Port = 587;

        $mail->setFrom(

            'bhingarbank07@gmail.com',

            'Bank Compliance Tracker'
        );

        $mail->addAddress($to_email);

        $mail->isHTML(true);

        $mail->Subject =
        'OTP Verification';

        $mail->Body = "

        <h2>Your OTP Code</h2>

        <h1>$otp</h1>

        ";

        $mail->send();

        return true;

    }

    catch(Exception $e){

        return false;
    }
}
?>