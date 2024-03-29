<?php

/************helper functions**********/
function clean($string){
    return htmlentities($string);
}

function redirect($location){
    return header("Location: {$location}");
}

function set_message($message){

    if(!empty($message)){
        $_SESSION['message'] = $message;
    }
    else {
        $message = "  ";
    }
}

function display_message(){
    if(isset($_SESSION['message'])){

        echo $_SESSION['message'];
        unset($_SESSION['message']);
    }

    }


    /********* for password recover *********/
    function token_generator(){
    $token = $_SESSION['token'] = md5(uniqid(mt_rand(),true));

    return $token;
    }

/********function to check if email exists in the database*******/
    function email_exists($email){
    $sql = "SELECT FROM users WHERE email = '$email' ";
    $result = query($sql);

    if (row_count($result) ==1 ){
        return true;
    }else

    {return false;}

    }

    /*******checking for username in database******/
function username_exists($username){
    $sql = "SELECT id FROM users WHERE username = '$username' ";
    $result = query($sql);

    if (row_count($result) ==1 ){
        return true;
    }else
{
        return false;}

}

/*****function to send email confirmation to the user*******/
function send_email($email, $subject, $msg, $headers){

    return mail($email, $subject,  $msg, $headers);
}






    /************validation functions*********/
    function validate_user_registration(){

        $errors = [];

        $max = 20;
        $min = 3;

        if($_SERVER['REQUEST_METHOD'] == "POST"){

            $first_name       = clean($_POST['first_name']) ;
            $last_name        = clean($_POST['last_name']) ;
            $username         = clean($_POST['username']) ;
            $email            = clean($_POST['email']) ;
            $password         = clean($_POST['password']) ;
            $confirm_password = clean($_POST['confirm_password']);

            if(strlen($first_name) < $min){

               $error[] = "Your first name should not be less than {$min} characters.";
            }

            if(strlen($first_name) > $max ){

                $errors[] = "Your First name should not be more than {$max} characters.";
            }

            if(strlen($last_name) > $max ){

                 $errors[] = "Your last name should not be more than {$max} characters.";
            }

            if (username_exists($username)){
                $errors[] = "Sorry that Username is already taken.";
            }

            if (email_exists($email)){
                $errors[] = "Sorry that email has already been registered.";
            }


            if(strlen($username) < $min ){

                $errors[] = "Your Username should not be less than {$min} characters.";
            }

            if(strlen($username) > $max ){

                $errors[] = "Your User name should not be more than {$max} characters.";
            }
            if($password != $confirm_password){

                $errors[] = "Your password fields do not match .";
            }



            if(!empty($errors)){
                foreach ($errors as $error){

$message = <<<DELIMITER
                    <div class="alert alert-danger alert-dismissible " role="alert">
                    <strong>Warning!</strong>   $error 
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                     </div>
DELIMITER;

echo $message;

                }

            }else{

                if (register_user($first_name, $last_name, $username, $password, $email)) {

                    set_message("<p class='bg-success text-center'>Please check your email or spam folder for an activation link</p>");

                   redirect("index.php");
                }
            }

        }//post request

        }//function

   function register_user($first_name, $last_name, $username, $password, $email)
   {
       $first_name = escape($first_name);
       $last_name = escape($last_name);
       $username = escape($username);
       $email = escape($email);
       $password = escape($password);

       if (email_exists($email)) { return false; }

       elseif (username_exists($username)) { return false;}
       else {

           $password = md5($password);
           $validation_code = md5($username . microtime());

           $sql = "INSERT INTO users(first_name, last_name, username, email, password, validation_code, active)";
           $sql .= " VALUES('$first_name', '$last_name', '$username', '$email', '$password', '$validation_code', 0)";

           $result = query($sql);
           confirm($result);

           $subject = "Activate Account";
           $msg = "Please click the link to activate your Account
                     http://localhost/Register/activate.php?email=$email&code=$validation_code";
           $headers = "From: noreply@yourwebsite.com";

           send_email($email, $subject, $msg, $headers);

           return true;

       }
   }



   /**********activate user functions*********/

   function activate_user(){

       if ($_SERVER['REQUEST_METHOD'] == "GET"){

           if (isset($_GET['email'])){

               echo $email = clean($_GET['email']);
               echo $validation_code = clean($_GET['code']);

               $sql = "SELECT id FROM users WHERE email = '".escape($_GET['email'])."' AND validation_code = '".escape($_GET['code'])."' ";

               $result = query($sql);
               confirm($result);

               if (row_count($result) ==1){

                   $sql2 = "UPDATE users SET active = 1, validation_code = 0 WHERE email = '".escape($email)."' AND validation_code = '".escape($validation_code)."' ";
                   $result2 = query($sql2);
                   confirm($result2);



                   set_message("<p class='bg-success'>Your account has been activated.</p>");
                   redirect("login.php");
               }else {
                   set_message("<p class='bg-danger'>Sorry your account can not be activated.</p>");
                   redirect("login.php");
               }

          }


       }
   }//functions


/************validate user login***********/

function validate_user_login()
{

    $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == "POST")
    {


        $email            = clean($_POST['email']) ;
        $password         = clean($_POST['password']) ;
        $remember         = isset($_POST['remember']);


        if (empty($email))
        {
            $errors[] = "Email field cannot be empty";
        }

        if (empty($password))
        {
            $errors[] = "Password field cannot be empty";
        }


        if(!empty($errors))
        {

            foreach ($errors as $error)
            {

//                echo validation_errors($error);
                $message = <<<DELIMITER
                    <div class="alert alert-danger alert-dismissible " role="alert">
                    <strong>Warning!</strong>   $error 
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                     </div>
DELIMITER;

                echo $message;

            }

        }else
        {
           if (login_user($email, $password, $remember))
           {

               redirect("admin.php");

           }else{
               echo $errors[] = "Your credentials are not correct.";
           }
        }

    }

}//ending function for user login validation


/******************************* user login authentication********************************/
function login_user($email, $password, $remember)
{

     $sql = "SELECT password, id FROM users WHERE email = '".escape($email)."' AND active = 1 ";
     $result = query($sql);


     /****** checking if user password exists in the database and the user is active******/
     if (row_count($result) == 1)
     {

         $row = fetch_array($result);

         $db_password = $row['password'];

         if (md5($password) == $db_password)
         {
/*****checking if the checkbox is on**********/
             if ($remember == "on")
             {
                 setcookie('email', $email, time() + 86400);
             }


             $_SESSION['email'] = $email;

             return true;

         }else {  return false; }

//         return true;
     }else
     {
         return false;
     }
}//end user login function


/****************logged in function ********************/

function logged_in()
{
    if (isset($_SESSION['email']) || isset($_COOKIE['email'])){


        return true;
    }else{

        return false;
    }

}//ened function

/*********** recover password **********************************************/
function recover_password()
{

    if ($_SERVER['REQUEST_METHOD'] == "POST")
    {

        if (isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token'])
        {
            $email =  clean($_POST['email']);

            if (email_exists($email))
            {


                $validation_code = md5($email . microtime());

                setcookie('temp_access_code', $validation_code, time() + 900);

                $sql = "UPDATE users SET validation_code = '".escape($validation_code)."' WHERE email ='".escape($email)."'  ";
                $result = query($sql);


                $subject = "Please reset your password";
                $message = "Here is your password reset code {$validation_code}
                
                Click here to reset your password http://localhost/code.php?email=$email&code=$validation_code ";

                $headers = "From: noreply@hassansaava.com";

               if( send_email($email,  $subject, $message, $headers)){

                   echo '<div class="alert alert-danger alert-dismissible " role="alert">
                    <strong>Warning!</strong>   Email could not be sent. 
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                     </div>';


               }else{

                   set_message("<p class='bg-success text-center'> Please check your email or spam folder for a password reset code</p>");
                   redirect("index.php");
               }

            }else {

                echo '<div class="alert alert-danger alert-dismissible " role="alert">
                    <strong>Warning!</strong>   This email does not exist. 
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                     </div>';
            }

        }else{
            redirect("index.php");
        }

        if (isset($_POST['cancel_submit'])){

            redirect("index.php");
        }

    }


}// end tokens


/***************************** recover password function *****************************/

function validate_code (){

    if (isset($_COOKIE['temp_access_code'])) {

         if (!isset($_GET['email']) && isset($_GET['code'])){

             redirect("index.php");

            }elseif (empty($_GET['email']) || empty($_GET['code'])){

                redirect("index.php");
    }   else{

             if (isset($_POST['code'])) {


                 $email = clean($_GET['email']);
                 $validation_code = clean($_POST['code']);

                 $sql = "SELECT id FROM users WHERE validation_code = '".escape($validation_code)."' AND '".escape($email)."'";
                 $result = query($sql);

                 if (row_count($result) == 1){

                     setcookie('temp_access_code', $validation_code, time() + 300);

                     redirect("reset.php?email=$email&code=$validation_code");
                 }else{

                     echo '<div class="alert alert-danger alert-dismissible " role="alert">
                    <strong>Warning!</strong>   Sorry Wrong Validation code. 
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                     </div>';

                 }

             }
         }

        }else{

        set_message("<p class='bg-danger text-center'> Sorry your validation code was expired.</p>");
        redirect("recover.php");
    }
}// end function



/***************************** reset password function *****************************/


function password_reset(){

  if (isset ($_COOKIE['temp_access_code'])){

    if  (isset($_GET['email']) && isset($_GET['code'])){


        if (isset($_SESSION['token']) &&  isset($_POST['token'])){

            if  ($_POST['token'] === $_SESSION['token']) {


                if ($_POST['password'] === $_POST['confirm_password']) {

                    $sql = "UPDATE users SET password = '" . escape($_POST['password']) . "' WHERE email = '" . escape($email) . "' ";

                }

            }


        }


    }

}else {

    set_message("<p class='bg-danger text-center'> Sorry your time has expired.</p>");
    redirect("recover.php");
}




}

?>





