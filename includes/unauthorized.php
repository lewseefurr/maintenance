<?php

session_start();
include 'db.php';

include 'auth.php';
redirectIfNotLoggedIn();
redirectIfNotTechnician();

?>