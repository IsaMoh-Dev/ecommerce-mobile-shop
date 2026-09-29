<?php
require_once 'functions.php';

$auth->logout();

header('Location: index.php');
exit;
