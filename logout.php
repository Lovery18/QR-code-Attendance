<?php
session_start();
session_destroy();
header("Location: ../homepage/homepage.html");
exit();
?> 