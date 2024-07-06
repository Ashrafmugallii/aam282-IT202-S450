<?php
require(__DIR__ . "/../../lib/functions.php");
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
        echo "must provide email <br> ";
        $hasError = true;
    }
    
    //sanatize the email
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        echo "ENTER A VALID EMAIL! <br>";
        $hasError = true;
    }

    if(empty($password)){
        echo "must provide password <br> ";
        $hasError = true;
    }

    if(empty($confirm)){
        echo "must confirm password <br> ";
        $hasError = true;
    }

    if(strlen($password) < 8){
        echo "password must be 8 at least 8 characters <br> ";
        $hasError = true;
    }

    if(strlen($password) > 0 && $password !== $confirm){
        echo "password must match <br> ";
        $hasError = true;
    }

    if(!$hasError){
       // echo "Welcome in, ", $email;
       //TODO 4: hash pass
       $hash = password_hash($password, PASSWORD_BCRYPT);
       $db = getDB();
       $stmt = $db->prepare("INSERT INTO Users(email, password) VALUES (:email, :password)");
       try {
        $r = $stmt->execute([":email"=>$email, ":password"=>$hash]);
        echo "success!";
       }catch (Exception $e){
        echo "there was an error registering";
        echo "<pre>" . var_export($e, true) . "</pre>";
    }

 }
}

?>