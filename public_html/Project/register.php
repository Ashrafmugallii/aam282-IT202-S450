<?php
require(__DIR__ . "/../../partials/nav.php");
?>

<form onsubmit="return validate(this)" method="POST">
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" required />
    </div>
    <div>
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required minlength="8" />
    </div>
    <div>
        <label for="confirm">Confirm</label>
        <input type="password" name="confirm" required minlength="8" />
    </div>
    <input type="submit" value="Register" />
</form>
<script>
    function validate(form) {
        //TODO 1: implement JavaScript validation
        //ensure it returns false for an error and true for success
        return true;
    }
</script>


<?php
 //TODO 2: add PHP Code
 if(isset($_POST["email"]) && isset($_POST["password"]) && isset($_POST["confirm"])){
    $email = se($_POST, "email", "", false);//$_POST["email"];
    $password = se($_POST, "password", "", false); //$_POST["password"];
    $confirm = se($_POST, "confirm", "", false);//$_POST["confirm"];


    //TODO 3:
    $hasError = false;
    if(empty($email)){
        flash("must provide email <br> ");
        $hasError = true;
    }
    
    //sanatize the email
    //$email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $email = sanitize_email($email);
  /*  if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        flash("ENTER A VALID EMAIL! <br>");
        $hasError = true;
    }*/ 
    if(!is_valid_email(($email))){
        flash("ENTER A VALID EMAIL! <br>");
        $hasError = true; 
    }

    if(empty($password)){
        flash("must provide password <br> ");
        $hasError = true;
    }

    if(empty($confirm)){
        flash("must confirm password <br> ");
        $hasError = true;
    }

    if(strlen($password) < 8){
        flash("password must be 8 at least 8 characters <br> ");
        $hasError = true;
    }

    if(strlen($password) > 0 && $password !== $confirm){
        flash("password must match <br> ");
        $hasError = true;
    }

    if(!$hasError){
       // flash("Welcome in, ", $emai");
       //TODO 4: hash pass
       $hash = password_hash($password, PASSWORD_BCRYPT);
       $db = getDB();
       $stmt = $db->prepare("INSERT INTO Users(email, password) VALUES (:email, :password)");
       try {
        $r = $stmt->execute([":email"=>$email, ":password"=>$hash]);
        flash("success!");
       }catch (Exception $e){
        flash("there was an error registering");
        flash("<pre>" . var_export($e, true) . "</pre>");
    }

 }
}

?>

<?php require_once(__DIR__ . "/../../partials/flash.php");