<?php
/**
 * AES Encryption Library untuk enkripsi/dekripsi data/file
 * 
 * Author Eko Junaidi Salam <eko.junaidi.salam@gmail.com>
 */

require_once("aes.php");
defined('AUTHOR') OR define('AUTHOR', "ejs");
defined('HOSTNAME') OR define('HOSTNAME', "shell");

$back = false;
$exit = false;
$param = array();

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

function banner(){
	print "[-] Action you can take : \n";
	print "[x] encrypt \t- Encrypt Messages \n";	
	print "[x] ipinfo \t- IP Information \n";
	print "[x] mail \t- Send Mail \n";
}

print "|****************************************************************|\n";
print "                         EJS Shell Code                           \n";
print "    Credit: Eko Junaidi Salam , eko.junaidi.salam@gmail.com       \n";
print "                    Welcome to EJS Shell Code                     \n";
print "|****************************************************************|\n";
print "\n";
print "[-] Hello, what do you want ? \n";
banner();

while(!$exit){
    $back = false;
    fwrite(STDOUT, "\n[ ".AUTHOR."@".HOSTNAME." ~] > ");
    $read = trim(fgets(STDIN));
    
    if("exit" === $read) $exit = true;

    switch($read){
        case 'help':
            banner();
            break;
    }
}