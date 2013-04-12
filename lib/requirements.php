<?php

if (version_compare(PHP_VERSION, '5.4.0') < 0) die('<span style="color:#B22222;">Version of your PHP is ' . PHP_VERSION . ', but Aleph requires not less than PHP 5.4.0</span>');
echo 'All requirements are met.';