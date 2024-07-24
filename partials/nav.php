<?php
require_once(__DIR__ . "/../lib/functions.php");
//Note: this is to resolve cookie issues with port numbers
$domain = $_SERVER["HTTP_HOST"];
if (strpos($domain, ":")) {
    $domain = explode(":", $domain)[0];
}
$localWorks = false; //some people have issues with localhost for the cookie params
//if you're one of those people make this false

//this is an extra condition added to "resolve" the localhost issue for the session cookie
if (($localWorks && $domain == "localhost") || $domain != "localhost") {
    session_set_cookie_params([
        "lifetime" => 60 * 60,
        "path" => "$BASE_PATH",
        //"domain" => $_SERVER["HTTP_HOST"] || "localhost",
        "domain" => $domain,
        "secure" => true,
        "httponly" => true,
        "samesite" => "lax"
    ]);
}
session_start();
?>


<!-- include bootstrap css and js references -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
<!-- include css and js files -->
<link rel="stylesheet" href="<?php echo get_url('/../public_html/Project/mainpages/mainPages.css'); ?>">
<script src="<?php echo get_url('/../public_html/Project/helpers.js'); ?>"></script>


<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo get_url('/../public_html/Project/mainpages/home.php'); ?>">Voyagr</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <?php if (is_logged_in()) : ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/../public_html/Project/mainpages/profile.php'); ?>">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/../public_html/Project/featuresPages/createJournal.php'); ?>">Create Journal</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/../public_html/Project/featuresPages/exploreJournals.php'); ?>">Explore Journals</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/../public_html/Project/featuresPages/searchFlights.php'); ?>">Search flights</a></li>


                <?php endif; ?>
                <?php if (!is_logged_in()) : ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/../public_html/Project/mainpages/login.php'); ?>">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/../public_html/Project/mainpages/register.php'); ?>">Register</a></li>
                <?php endif; ?>


            <ul class="navbar-nav">
                <?php if (has_role("Admin")) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="<?php echo get_url('/../public_html/Project/admin/create_role.php'); ?>">Create Role</a></li>
                            <li><a class="dropdown-item" href="<?php echo get_url('/../public_html/Project/admin/list_roles.php'); ?>">List Roles</a></li>
                            <li><a class="dropdown-item" href="<?php echo get_url('/../public_html/Project/admin/assign_roles.php'); ?>">Assign Roles</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (is_logged_in()) : ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/../public_html/Project/mainpages/logout.php'); ?>">Logout</a></li>
                    <?php endif; ?>
                </ul>
        </div>
    </div>
</nav>