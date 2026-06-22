<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

/* SEND OTP */

if(isset($_POST['send_otp'])){

    $email = $_POST['email'];

    $otp = rand(100000,999999);

    $_SESSION['otp'] = $otp;

    $_SESSION['otp_email'] = $email;

    $mail = new PHPMailer(true);

    try{

        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';

        $mail->SMTPAuth = true;

        $mail->Username =
        'bhingarbank07@gmail.com';

        $mail->Password =
        'paajhjopmgvcuzzn';

        $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;

        $mail->setFrom(
        'bhingarbank07@gmail.com',
        'Bhingar Urban Co-Operative Bank'
        );

        $mail->addAddress($email);

        $mail->isHTML(true);

        $mail->Subject =
        'OTP Verification';

        $mail->Body = "

        <div style='font-family:sans-serif;'>

        <h2>Bhingar Urban Co-Operative Bank</h2>

        <p>Your OTP Code:</p>

        <h1>$otp</h1>

        </div>

        ";

        $mail->send();

        $_SESSION['otp_sent'] = true;

    }catch(Exception $e){

        $error = "OTP Failed";
    }
}

/* VERIFY OTP */

if(isset($_POST['verify_otp'])){

    if($_POST['otp'] == $_SESSION['otp']){

        $_SESSION['verified'] = true;

    }else{

        $error = "Invalid OTP";
    }
}

/* CREATE ACCOUNT */

if(isset($_POST['create_account'])){

    if(!isset($_SESSION['verified'])){

        die("Verify OTP First");
    }

    $first_name =
    $_POST['first_name'];

    $last_name =
    $_POST['last_name'];
    $mobile = mysqli_real_escape_string(
    $conn,
    $_POST['mobile']
    );

    $branch_location = mysqli_real_escape_string(
    $conn,
    $_POST['branch_location']
    );

    $password =
    $_POST['password'];

    $confirm_password =
    $_POST['confirm_password'];

    $email =
    $_SESSION['otp_email'];

    if($password != $confirm_password){

        $error = "Passwords Not Match";

    }else{

        $employee_id =

        "BUC" .

        date("Y") .

        rand(1000,9999);

        $name =
        $first_name . " " . $last_name;

        $query = "

        INSERT INTO users

        (

        first_name,
        last_name,
        
        email,
        mobile,
        branch_location,
        employee_id,
        password,
        role,
        otp_verified

        )

        VALUES

        (

        '$first_name',
        '$last_name',
        
        '$email',
        '$mobile',
        '$branch_location',
        '$employee_id',
        '$password',
        'employee',
        'Yes'

        )

        ";

        if(mysqli_query($conn,$query)){

        echo "

        <script>

        alert('Account Created Successfully');

        window.location='login.php';

        </script>

        ";

        }else{

        die(mysqli_error($conn));
        }

        session_destroy();

        echo "

        <script>

        alert('Account Created Successfully');

        window.location='login.php';

        </script>

        ";
    }
}


?>

<!DOCTYPE html>
<html>

<head>

<title>Signup</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI';
}

body{

height:100vh;

display:flex;

background:

linear-gradient(
135deg,
#020617,
#0f172a,
#1e293b
);

overflow:hidden;
}

/* LEFT */

.left{

width:55%;

display:flex;

justify-content:center;

align-items:center;

flex-direction:column;

padding:40px;
}

.left img{

width:75%;

max-width:650px;

border-radius:25px;

box-shadow:
0 0 40px rgba(37,99,235,0.4);
}

.bank-name{

margin-top:35px;

font-size:50px;

font-weight:bold;

color:white;

text-align:center;
}

/* RIGHT */

.right{

width:45%;

display:flex;

justify-content:center;

align-items:center;
}

/* BOX */

.signup-box{

position:relative;

width:480px;

padding:45px;

border-radius:30px;

overflow:hidden;

background:
rgba(255,255,255,0.08);

backdrop-filter:blur(18px);

box-shadow:
0 0 50px rgba(37,99,235,0.35);
}

.signup-box::before{

content:"";

position:absolute;

width:700px;

height:700px;

background:

conic-gradient(

transparent,
transparent,
#3b82f6,
#60a5fa,
transparent

);

top:-120px;

left:-120px;

animation:spin 4s linear infinite;
}

.signup-box::after{

content:"";

position:absolute;

inset:4px;

background:

linear-gradient(
135deg,
#1e293b,
#0f172a
);

border-radius:28px;

z-index:1;
}

@keyframes spin{

100%{
transform:rotate(360deg);
}
}

.content{

position:relative;

z-index:2;
}

h1{

color:white;

text-align:center;

font-size:48px;

margin-bottom:10px;
}

.subtitle{

text-align:center;

color:#cbd5e1;

margin-bottom:35px;
}

/* INPUT */

.input-box{

margin-bottom:20px;
}

.input-box input{

width:100%;

padding:16px;

border:none;

border-radius:14px;

background:
rgba(255,255,255,0.1);

color:white;

font-size:15px;

outline:none;
}

.input-box input:focus{

box-shadow:
0 0 20px #2563eb;
}

/* BUTTON */

button{

width:100%;

padding:16px;

border:none;

border-radius:14px;

background:

linear-gradient(
135deg,
#2563eb,
#1d4ed8
);

color:white;

font-size:16px;

cursor:pointer;

transition:0.3s;
}

button:hover{

transform:translateY(-2px);

box-shadow:
0 10px 25px rgba(37,99,235,0.4);
}

/* SUCCESS */

.success{

background:#16a34a;

padding:14px;

border-radius:12px;

color:white;

text-align:center;

margin-bottom:20px;
}

/* ERROR */

.error{

background:#dc2626;

padding:14px;

border-radius:12px;

color:white;

text-align:center;

margin-bottom:20px;
}

/* LINK */

.login-link{

margin-top:25px;

text-align:center;

color:white;
}

.login-link a{

color:#60a5fa;

text-decoration:none;

font-weight:bold;
}

/* RESEND */

.resend{

margin-top:12px;

text-align:center;
}

.resend button{

background:none;

border:none;

color:#60a5fa;

cursor:pointer;

font-size:15px;
}
input{
    width:100%;
    height:55px;
    background:#2b354d;
    border:none;
    border-radius:15px;
    color:white;
    padding:0 18px;
    font-size:15px;
    margin-bottom:20px;
}

input::placeholder{
    color:#94a3b8;
}

</style>

</head>

<body>

<div class="left">

<img
src="../assets/images/image.png">

<div class="bank-name">

BHINGAR URBAN CO-OPERATIVE BANK

</div>

</div>

<div class="right">

<div class="signup-box">

<div class="content">

<h1>Create Account</h1>

<p class="subtitle">

Secure Employee Registration

</p>

<?php
if(isset($error)){
?>

<div class="error">

<?php echo $error; ?>

</div>

<?php } ?>

<!-- EMAIL -->

<?php
if(!isset($_SESSION['otp_sent'])){
?>

<form method="POST">

<div class="input-box">

<input
type="email"

name="email"

placeholder="Enter Email"

required>

</div>

<button
type="submit"

name="send_otp">

Send OTP

</button>

</form>

<?php } ?>

<!-- OTP -->

<?php
if(isset($_SESSION['otp_sent'])
&& !isset($_SESSION['verified'])){
?>

<div class="success">

OTP Sent Successfully

</div>

<form method="POST">

<div class="input-box">

<input
type="text"

name="otp"

placeholder="Enter OTP"

required>

</div>

<button
type="submit"

name="verify_otp">

Verify OTP

</button>

</form>

<div class="resend">

<form method="POST">

<input
type="hidden"

name="email"

value="<?php echo $_SESSION['otp_email']; ?>">

<button
type="submit"

name="send_otp">

Resend OTP

</button>

</form>

</div>

<?php } ?>

<!-- ACCOUNT FORM -->

<?php
if(isset($_SESSION['verified'])){
?>

<div class="success">

OTP Verified Successfully

</div>

<form method="POST">

<div class="input-box">

<input
type="text"

name="first_name"

placeholder="First Name"

required>

</div>

<div class="input-box">

<input
type="text"

name="last_name"

placeholder="Last Name"

required>

</div>
<input
type="text"
name="mobile"
placeholder="Mobile Number"
maxlength="10"
pattern="[0-9]{10}"
required>

<input
type="text"
name="branch_location"
placeholder="Branch Location"
required>

<div class="input-box">

<input
type="password"

name="password"

placeholder="Password"
minlength="6"
pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$"
title="Password must contain at least 6 characters, one uppercase letter, one lowercase letter, one number, and one special symbol"

required>

</div>

<div class="input-box">

<input
type="password"

name="confirm_password"

placeholder="Confirm Password"

required>

</div>

<button
type="submit"

name="create_account">

Create Account

</button>

</form>

<?php } ?>

<div class="login-link">

Already have account?

<a href="login.php">

Login

</a>

</div>

</div>

</div>

</div>

</body>
</html>
