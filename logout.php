<?php

require 'config/Session.php';

Session::start();
Session::destroy();

header('Location: login.php');

exit;
