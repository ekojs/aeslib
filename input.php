<?php
function hide_term() {
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        echo "\033[30;40m";
        flush();
    }
}
 
function restore_term() {
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        echo "\033[0m";
        flush();
    }
}
 
echo 'Enter password: ';
hide_term();
$password = rtrim(fgets(STDIN), PHP_EOL);
restore_term();
 
echo "You entered '$password'", PHP_EOL;