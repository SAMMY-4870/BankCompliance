<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

ensure_table_column($conn, 'users', 'mobile', 'VARCHAR(15) NULL');
ensure_table_column($conn, 'users', 'branch_location', 'VARCHAR(100) NULL');
ensure_table_column($conn, 'users', 'profile_photo', "VARCHAR(255) NULL DEFAULT 'default.png'");
ensure_table_column($conn, 'users', 'last_login', 'DATETIME NULL');
ensure_table_column($conn, 'users', 'account_status', "ENUM('Active','Inactive') DEFAULT 'Active'");

if(isset($_POST['login'])){

    $email = $_POST['email'];

    $password = $_POST['password'];

    $query = "

    SELECT * FROM users

    WHERE email='$email'

    AND password='$password'

    ";

    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){

        $user = mysqli_fetch_assoc($result);

        $_SESSION['user_id'] = $user['id'];

        $_SESSION['name'] = $user['name'];

        $_SESSION['role'] = $user['role'];

        session_regenerate_id(true);

        mysqli_query(
            $conn,
            "UPDATE users SET last_login = NOW() WHERE id='" . (int)$user['id'] . "'"
        );

        if(in_array($user['role'], ['super_admin', 'admin', 'manager', 'auditor'], true)){

            header("Location: ../admin/dashboard.php");
            exit();

        }else{

            header("Location: ../employee/dashboard.php");
            exit();
        }

    }else{

        $error = "Invalid Email or Password";
    }
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Login</title>

<style>

**{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{

    height:100vh;

    overflow:hidden;

    display:flex;

    background:
    linear-gradient(
    135deg,
    #020617,
    #0f172a,
    #1e293b
    );
}

/* LEFT PANEL */

.left{

    width:55%;

    display:flex;

    flex-direction:column;

    justify-content:center;

    align-items:center;

    position:relative;

    background:
    radial-gradient(circle at top,
    rgba(37,99,235,0.2),
    transparent 60%);
}

.left::before{

    content:"";

    position:absolute;

    width:500px;

    height:500px;

    background:
    rgba(37,99,235,0.15);

    border-radius:50%;

    filter:blur(120px);

    animation:pulse 5s infinite;
}

@keyframes pulse{

    0%{
        transform:scale(1);
    }

    50%{
        transform:scale(1.1);
    }

    100%{
        transform:scale(1);
    }
}

.left img{

    width:70%;

    max-width:600px;

    border-radius:25px;

    box-shadow:
    0 0 40px rgba(37,99,235,0.4);

    animation:float 4s ease-in-out infinite;

    z-index:1;
}

@keyframes float{

    0%{
        transform:translateY(0px);
    }

    50%{
        transform:translateY(-20px);
    }

    100%{
        transform:translateY(0px);
    }
}

.bank-title{

    color:white;

    font-size:40px;

    font-weight:bold;

    margin-top:30px;

    z-index:1;
}

.bank-sub{

    color:#94a3b8;

    margin-top:10px;

    font-size:18px;

    z-index:1;
}

/* RIGHT PANEL */

.right{

    width:45%;

    display:flex;

    justify-content:center;

    align-items:center;

    position:relative;
}

/* LOGIN BOX */
.login-box{

    position:relative;

    width:450px;

    padding:50px;

    border-radius:30px;

    background:
    rgba(255,255,255,0.08);

    border:
    1px solid rgba(255,255,255,0.1);

    backdrop-filter:blur(20px);

    overflow:hidden;

    box-shadow:
    0 0 40px rgba(37,99,235,0.35);
}
/* RUNNING BORDER */
@keyframes rotateBorder{

    0%{

        transform:rotate(0deg);
    }

    100%{

        transform:rotate(360deg);
    }
}
/* ANIMATED BORDER */

.login-box::before{

    content:"";

    position:absolute;

    width:700px;

    height:700px;

    background:

    conic-gradient(

        from 0deg,

        transparent 0deg,

        transparent 40deg,

        #3b82f6 90deg,

        #60a5fa 140deg,

        transparent 180deg,

        transparent 360deg

    );

    animation:spinBorder 3s linear infinite;

    top:-120px;

    left:-120px;
}

/* INNER BOX */

.login-box::after{

    content:"";

    position:absolute;

    inset:4px;

    border-radius:28px;

    background:

    linear-gradient(
    135deg,
    #1e293b,
    #0f172a
    );

    z-index:1;
}

/* KEEP CONTENT ABOVE */

.login-box h1,
.login-box form,
.login-box .subtitle,
.login-box .signup-link,
.login-box .error{

    position:relative;

    z-index:2;
}

/* CONTINUOUS ROTATION */

@keyframes spinBorder{

    100%{

        transform:rotate(360deg);
    }
}
.login-box::after{

    content:"";

    position:absolute;

    inset:3px;

    background:

    linear-gradient(
    135deg,
    #1e293b,
    #0f172a
    );

    border-radius:28px;

    z-index:1;
}

/* CONTENT ABOVE BORDER */

.login-box form,
.login-box h1,
.login-box .subtitle,
.login-box .signup-link,
.login-box .error{

    position:relative;

    z-index:2;
}

/* ANIMATION */

@keyframes rotateBorder{

    0%{

        transform:rotate(0deg);
    }

    100%{

        transform:rotate(360deg);
    }
}
.login-box h1{

    color:white;

    text-align:center;

    font-size:42px;

    margin-bottom:10px;
}

.subtitle{

    color:#cbd5e1;

    text-align:center;

    margin-bottom:40px;

    font-size:16px;
}

/* INPUT */

.input-box{

    margin-bottom:28px;
}

.input-box label{

    display:block;

    color:white;

    margin-bottom:10px;

    font-size:15px;
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

    transition:0.3s;
}

.input-box input:focus{

    box-shadow:
    0 0 20px #2563eb;

    transform:scale(1.02);
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

    font-size:17px;

    cursor:pointer;

    transition:0.3s;
}

button:hover{

    transform:translateY(-3px);

    box-shadow:
    0 10px 25px rgba(37,99,235,0.5);
}

/* SIGNUP */

.signup-link{

    margin-top:30px;

    text-align:center;

    color:#e2e8f0;
}

.signup-link a{

    color:#60a5fa;

    text-decoration:none;

    font-weight:bold;
}

.signup-link a:hover{

    text-decoration:underline;
}

/* ERROR */

.error{

    background:#dc2626;

    color:white;

    padding:14px;

    border-radius:12px;

    margin-bottom:25px;

    text-align:center;
}

</style>

</head>

<body>

<!-- LEFT SIDE -->

<div class="left">

    <img
    src="../assets/images/image.png">
    
</div>

<!-- RIGHT SIDE -->

<div class="right">

    <div class="login-box">

        <h1>Bank Compliance</h1>

        <p class="subtitle">

            Employee Task Reminder System

        </p>

        <?php

        if(isset($error)){

            echo "

            <div class='error'>

            $error

            </div>

            ";
        }

        ?>

        <form method="POST">

            <div class="input-box">

                <label>Email ID</label>

                <input
                type="email"

                name="email"

                placeholder="Enter Email"

                required>

            </div>

            <div class="input-box">

                <label>Password</label>

                <input
                type="password"

                name="password"

                placeholder="Enter Password"

                required>

            </div>

            <button
            type="submit"

            name="login">

                Login

            </button>

        </form>

        <div class="signup-link">

            Don't have an account?

            <a href="signup.php">

                Signup

            </a>

        </div>

    </div>

</div>

</body>
</html>
