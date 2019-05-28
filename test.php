<?php
include_once("aes.php");

$aes = AES_LIB::getInstance();

$enc = $aes->encrypt("Eko Junaidi Salam","secret","public_key/my_cert.crt");
$msg = $aes->decrypt($enc,null,"private_key/my.enc.key","123456");
print_r($enc).PHP_EOL;
echo $msg.PHP_EOL;

// $enc = $aes->sealData(json_encode(array(
//     "nip" => "123456",
//     "nama" => "Eko Junaidi Salam"
// )),"public_key/");
// $msg = $aes->openSeal($enc['sealed'],$enc['keys'],"private_key/my.enc.key","123456");
// print_r($enc).PHP_EOL;
// echo $msg.PHP_EOL;

// $fname = "coba.pdf";
// echo $aes->streamEncrypt($fname,"secret",$fname.".enc","public_key/my_cert.crt").PHP_EOL;
// echo $aes->streamDecrypt(array("encfile" => $fname.".enc","key" => $fname.".key"),null,"out-".$fname,"private_key/my.enc.key","123456").PHP_EOL;
// echo $aes->streamDecrypt($fname.".enc","secret","out-".$fname).PHP_EOL;