
<?php

function sanitize_email($email = "")
{
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}
function is_valid_email($email = "")
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}
function is_valid_username($username)
{
    return preg_match('/^[a-zA-Z0-9 _-]{3,30}$/', $username);
}
function is_valid_password($password)
{
    return strlen($password) >= 8;
}