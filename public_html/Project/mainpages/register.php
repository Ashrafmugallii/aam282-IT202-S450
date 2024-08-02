<?php
require(__DIR__ . "/../../../partials/nav.php");
reset_session();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Include Bootstrap CSS and JS references -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container-fluid mt-4">
        <form onsubmit="return validate(this)" method="POST">
            <?php render_input(["type"=>"email", "id"=>"email", "name"=>"email", "label"=>"Email", "rules"=>["required"=>true]]);?>
            <?php render_input(["type"=>"text", "id"=>"username", "name"=>"username", "label"=>"Username", "rules"=>["required"=>true, "maxlength"=>30]]);?>
            <?php render_input(["type"=>"password", "id"=>"password", "name"=>"password", "label"=>"Password", "rules"=>["required"=>true, "minlength"=>8]]);?>
            <?php render_input(["type"=>"password", "id"=>"confirm", "name"=>"confirm", "label"=>"Confirm Password", "rules"=>["required"=>true,"minlength"=>8]]);?>
            <?php render_button(["text"=>"Register", "type"=>"submit"]);?>
        </form>
    </div>


    <script>
        function validate(form) {
            let email = form.email.value;
            let username = form.username.value;
            let password = form.password.value;
            let confirm = form.confirm.value;

            // Email validation
            let emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            if (!emailPattern.test(email)) {
                alert("Please enter a valid email address.");
                return false;
            }

            // Username validation
            let usernamePattern = /^[a-zA-Z0-9 _-]{3,30}$/;
            if (!usernamePattern.test(username)) {
                alert("Username must only contain 3-30 characters a-z, 0-9, spaces, _, or -.");
                return false;
}


            // Password validation
            if (password.length < 8) {
                alert("Password must be at least 8 characters long.");
                return false;
            }

            // Confirm password validation
            if (password !== confirm) {
                alert("Passwords do not match.");
                return false;
            }

            // If all validations pass
            return true;
        }
    </script>
    <?php
    if (isset($_POST["email"]) && isset($_POST["password"]) && isset($_POST["confirm"]) && isset($_POST["username"])) {
        $email = se($_POST, "email", "", false);
        $password = se($_POST, "password", "", false);
        $confirm = se($_POST, "confirm", "", false);
        $username = se($_POST, "username", "", false);

        $hasError = false;
        if (empty($email)) {
            flash("Email must not be empty", "danger");
            $hasError = true;
        }
        $email = sanitize_email($email);
        if (!is_valid_email($email)) {
            flash("Invalid email address", "danger");
            $hasError = true;
        }
        if (!is_valid_username($username)) {
            flash("Username must only contain 3-16 characters a-z, 0-9, _, or -", "danger");
            $hasError = true;
        }
        if (empty($password)) {
            flash("password must not be empty", "danger");
            $hasError = true;
        }
        if (empty($confirm)) {
            flash("Confirm password must not be empty", "danger");
            $hasError = true;
        }
        if (!is_valid_password($password)) {
            flash("Password too short", "danger");
            $hasError = true;
        }
        if (strlen($password) > 0 && $password !== $confirm) {
            flash("Passwords must match", "danger");
            $hasError = true;
        }
        if (!$hasError) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO Users (email, password, username) VALUES(:email, :password, :username)");
            try {
                $stmt->execute([":email" => $email, ":password" => $hash, ":username" => $username]);
                flash("Successfully registered!", "success");
            } catch (PDOException $e) {
                users_check_duplicate($e->errorInfo);
            }
        }
    }
    ?>
    <?php require(__DIR__ . "/../../../partials/flash.php"); ?>
</body>
</html>
