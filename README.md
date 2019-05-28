# AES Encryption Library
Light Weight AES Encryption Library, using Public Key Infrastructure.

## Feature
* Encrypt Message using symmetric key AES-256-CBC and HMAC-SHA-256 Signature
* Decrypt Message 
* Encrypt symmetric key using public key
* Decrypt symmetric key using private key
* Seal Data using multiple public key
* Open Seal Data using single private key
* File Stream Encrypt
* File Stream Decrypt

## Example Usage

#### Encrypt/Decrypt message without public key recipient
```bash
include_once("aes.php");

$aes = AES_LIB::getInstance();
$enc = $aes->encrypt("Eko Junaidi Salam","secret");
$msg = $aes->decrypt($enc,"secret");
print_r($enc).PHP_EOL;
echo $msg.PHP_EOL;
```

#### Encrypt/Decrypt message with public key recipient
```bash
include_once("aes.php");

$aes = AES_LIB::getInstance();
$enc = $aes->encrypt("Eko Junaidi Salam","secret","public_key/my_cert.crt");
$msg = $aes->decrypt($enc,null,"private_key/my.enc.key","123456");
print_r($enc).PHP_EOL;
echo $msg.PHP_EOL;
```

#### Seal data using multiple public key recipient
```bash
include_once("aes.php");

$aes = AES_LIB::getInstance();
$enc = $aes->sealData(json_encode(array(
    "nik" => "123456",
    "nama" => "Eko Junaidi Salam"
)),"public_key/");
print_r($enc).PHP_EOL;
```

#### Open sealed data using single private key recipient
```bash
include_once("aes.php");

$aes = AES_LIB::getInstance();
$msg = $aes->openSeal($sealed_data,$enc_key,"private_key/my.enc.key","123456");
echo $msg.PHP_EOL;
```

#### Stream Encrypt/Decrypt without public key recipient
```bash
include_once("aes.php");

$aes = AES_LIB::getInstance();
$fname = "coba.pdf";
echo $aes->streamEncrypt($fname,"secret",$fname.".enc").PHP_EOL;
echo $aes->streamDecrypt($fname.".enc","secret","out-".$fname).PHP_EOL;
```

#### Stream Encrypt/Decrypt with public key recipient
```bash
include_once("aes.php");

$aes = AES_LIB::getInstance();
$fname = "coba.pdf";
echo $aes->streamEncrypt($fname,"secret",$fname.".enc","public_key/my_cert.crt").PHP_EOL;
echo $aes->streamDecrypt(array("encfile" => $fname.".enc","key" => $fname.".key"),null,"out-".$fname,"private_key/my.enc.key","123456").PHP_EOL;
```

#### All about digital signature commands, please check it [here](https://github.com/ekojs/digital_signature/blob/master/README.md)