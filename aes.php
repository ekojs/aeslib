<?php
/**
 * AES Encryption Library untuk enkripsi/dekripsi data/file
 * 
 * Author Eko Junaidi Salam <eko.junaidi.salam@gmail.com>
 */
Class AES_LIB {
    private $block_size = 8192;
    private $hash_size = 32;
    private $cipher = "aes-256-cbc";
    private $hash = "sha256";
    private $iv;

    private static $instance = null;

    private function __construct(){}

    private function __clone(){}

    private function __wakeup(){}

    public static function getInstance(){
        if(null === self::$instance){
			// Buat instance baru...
            self::$instance = new AES_LIB();
        }
        return self::$instance;
    }

    /**
     * Inisialisasi vector untuk enkripsi data
     */
    private function initialize(){
		// Generate Random Initialization Vector untuk enkripsi data 
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $this->iv = openssl_random_pseudo_bytes($ivlen);
        return $this;
    }

    /**
     * Encrypt data
     * @param $msg                  string  Pesan yang akan dienkripsi
     * @param $key                  string  Kunci rahasia
     * @param $fpubkey_recipient    string  Lokasi path file public key
     * 
     * @return string | array
     */
    public function encrypt($msg=null,$key=null,$fpubkey_recipient=null){
		if(empty($msg) || empty($key)) trigger_error("Pesan dan key harus diisi..",E_USER_ERROR);
		
        $this->initialize();
		$ciphertext = openssl_encrypt($msg,$this->cipher,$key,OPENSSL_RAW_DATA,$this->iv);
		// Buat signature menggunakan hmac
        $hash = hash_hmac($this->hash,$ciphertext,$key,true);

        if(!empty($fpubkey_recipient) && file_exists($fpubkey_recipient)){
            return array(
				"data" => base64_encode($this->iv.$ciphertext.$hash),
				// Enkripsi kunci rahasia menggunakan publik key penerima
                "key" => $this->encWithPublic($key,$fpubkey_recipient)
            );
        }
        return base64_encode($this->iv.$ciphertext.$hash);
    }

    /**
     * Decrypt data
     * @param $enc             string | array('data','key')		Encrypted data
     * @param $key                  string		Kunci rahasia
     * @param $fpkey_recipient      string		Lokasi path file private key
     * @param $passphrase           string		Passphrase dari private key
     * 
     * @return string
     */
    public function decrypt($enc=null,$key=null,$fpkey_recipient=null,$passphrase=null){
        if(empty($enc)) trigger_error("Pesan harus diisi..",E_USER_ERROR);
        if(empty($key) && empty($fpkey_recipient)) trigger_error("Key atau file private key harus diisi..",E_USER_ERROR);

        if(!empty($fpkey_recipient)){
            if(!is_array($enc)) trigger_error("Pesan harus berupa array bila menggunakan private key...",E_USER_ERROR);
			
            $encrypted_key = $enc['key'];
			$enc = $enc['data'];
			// Dekripsi kunci rahasia menggunakan private key penerima
            $key = $this->decryptWithPrivate($encrypted_key,$fpkey_recipient,$passphrase);
        }

		$ivlen = openssl_cipher_iv_length($this->cipher);
		// Decode pesan rahasia menggunakan b64
		$str = base64_decode($enc);
		// Ambil Initialization Vector pada 16 byte pertama
        $iv = substr($str,0,$ivlen);

		// Ambil hmac signature pada 32 byte di akhir data
		$hmac = substr($str,strlen($str) - $this->hash_size,$this->hash_size);
		// Ambil pesan rahasia pada posisi 16 byte data sampai panjang data - ivlen - hash signature 
        $ciphertext = substr($str,$ivlen,strlen($str)-$ivlen-$this->hash_size);
        $hash = hash_hmac($this->hash,$ciphertext,$key,true);

		// Cek apakah signature valid atau tidak
        if(hash_equals($hmac,$hash)){
            return openssl_decrypt($ciphertext,$this->cipher,$key,OPENSSL_RAW_DATA,$iv);
        }
        return false;
    }

    /**
     * Encrypt data menggunakan public key
     * @param $data string  Data yang akan dienkripsi
     * @param $fpub string  Lokasi path file publik key
     * 
     * @return string
     */
    private function encWithPublic($data,$fpub){
        $pubkey = file_get_contents($fpub);
        $pbk = openssl_pkey_get_public($pubkey);
        if(!$pbk) exit("Can't get public key.".PHP_EOL);
        
        openssl_public_encrypt($data,$crypted,$pbk);
		openssl_free_key($pbk);

		return (!empty($crypted)?base64_encode($crypted):false);
    }

    /**
     * Decrypt data menggunakan private key
     * @param $enc string  Data yang akan terenkripsi
     * @param $fpkey string  Lokasi path file private key
     * 
     * @return string
     */
    private function decryptWithPrivate($enc,$fpkey,$passphrase=null){
        $data = base64_decode($enc);
        $pkey = file_get_contents($fpkey);
        $pk = (!empty($passphrase)?openssl_pkey_get_private($pkey,$passphrase):openssl_pkey_get_private($pkey));
        if(!$pk) exit("Can't get private key.".PHP_EOL);

        openssl_private_decrypt($data,$decrypted,$pk);
		openssl_free_key($pk);

		return (!empty($decrypted)?$decrypted:false);
    }

    /**
     * Seal data menggunakan multi public key
     * @param $msg  string  Data yang akan dienkripsi
     * @param $dir  string  Lokasi folder publik key
     * 
     * @return array
     */
    public function sealData($msg=null,$dir=null){
		if(empty($msg) || empty($dir)) trigger_error("Pesan dan directory public key harus diisi..",E_USER_ERROR);
		
		$scanned = scandir($dir);
		// Ambil seluruh list file kecuali kode .. dan .
        $files = array_diff($scanned,array('..','.'));
        
        $pubkey = [];
        foreach($files as $f){
			// Ekstrak data public key
            $pbk = openssl_pkey_get_public(file_get_contents($dir.$f));
            if(!$pbk){
                echo "Can't get public key from ".$f.PHP_EOL;
            }else{
                array_push($pubkey,$pbk);
            }
        }
        $this->initialize();

        openssl_seal($msg,$sealed,$keys,$pubkey,$this->cipher,$this->iv);

        foreach($pubkey as $k){
            openssl_free_key($k);
        }

        if(!empty($sealed) && !empty($keys)){
            return array(
				"sealed" => base64_encode($this->iv.$sealed),
				// Encode key menggunakan b64
                "keys" => array_map(function($v){return base64_encode($v);},$keys)
            );
        }else{
            return false;
        }
    }

    /**
     * Open Seal data untuk membuka Seal Data
     * @param $sealed       string  Berisi data seal dan keys tiap publik key
     * @param $keys         array   Berisi aes list key untuk decrypt data
     * @param $fpkey        string  Lokasi path file private key
     * @param $passphrase   string	Passphrase dari private key
     * 
     * @return string
     */
    public function openSeal($sealed=null,$keys=null,$fpkey=null,$passphrase=null){
		if(empty($sealed) || empty($keys) || empty($fpkey)) trigger_error("Pesan, keys, dan lokasi private key harus diisi..",E_USER_ERROR);

		if(!file_exists($fpkey)) trigger_error("Private Key File tidak ada..",E_USER_ERROR);

        $pkey = file_get_contents($fpkey);
        $pk = (!empty($passphrase)?openssl_pkey_get_private($pkey,$passphrase):openssl_pkey_get_private($pkey));

        if(!$pk) exit("Can't get private key.".PHP_EOL);

        $str = base64_decode($sealed);
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $iv = substr($str,0,$ivlen);
        $ciphertext = substr($str,$ivlen,strlen($str)-$ivlen);

        foreach($keys as $key){
            $res = openssl_open($ciphertext,$open,base64_decode($key),$pk,$this->cipher,$iv);
            if($res) break;
        }
		openssl_free_key($pk);
		
		return ($res?$open:false);
    }

    /**
     * Encrypt data using from stream
     * @param $file                 string      Lokasi path file yang akan dienkrip
     * @param $key                  string      Kunci rahasia
     * @param $outfile              string      Nama file output
     * @param $fpubkey_recipient    string      Lokasi path file public key
     * 
     * @return string	Output berisi hash dari file output
     */
    public function streamEncrypt($file=null,$key=null,$outfile=null,$fpubkey_recipient=null){
        if(empty($file) || empty($key) || empty($outfile)) trigger_error("Input dan output file harus diisi..",E_USER_ERROR);

		if(!file_exists($file)) trigger_error("Input File tidak ada..",E_USER_ERROR);

        $this->initialize();
        $f = fopen($file,"r");
        $out = fopen($outfile,"w");
        fwrite($out,$this->iv);
        
        while(!feof($f)){
            $buf = fread($f,$this->block_size);
            fwrite($out,openssl_encrypt($buf,$this->cipher,$key,OPENSSL_RAW_DATA,$this->iv));
		}
		// Buat signature menggunakan hmac
        $hmac = hash_hmac_file($this->hash,$outfile,$key,true);
        fwrite($out,$hmac);
        fclose($f);
        fclose($out);
        
        if(file_exists($outfile) && filesize($outfile)){
            if(!empty($fpubkey_recipient) && file_exists($fpubkey_recipient)){
                file_put_contents($file.".key",$this->encWithPublic($key,$fpubkey_recipient));
            }
            return hash_file($this->hash,$outfile);
        }
        return false;
    }

    /**
     * Decrypt data from stream
     * @param $file            string | array('encfile','key')      Lokasi path file yang akan dienkrip
     * @param $key                  string      Kunci rahasia
     * @param $outfile              string      Nama file output
     * @param $fpkey_recipient      string      Lokasi path file private key
     * @param $passphrase           string      Passphrase dari private key
     * 
     * @return string	Output berisi hash dari file output
     */
    public function streamDecrypt($file=null,$key=null,$outfile=null,$fpkey_recipient=null,$passphrase=null){
        if(empty($file) || empty($outfile)) trigger_error("Input dan output file harus diisi..",E_USER_ERROR);
		if(empty($key) && empty($fpkey_recipient)) trigger_error("Key atau file private key harus diisi..",E_USER_ERROR);

		if(is_array($file)){
			if(!file_exists($file['encfile']) || !file_exists($file['key'])) trigger_error("Input File atau File kunci tidak ada..",E_USER_ERROR);
		}else if(!file_exists($file)){
			trigger_error("Input File tidak ada..",E_USER_ERROR);
		}
		
		if(!empty($fpkey_recipient)){
			if(!is_array($file)) trigger_error("File harus berupa array bila menggunakan private key...",E_USER_ERROR);
			
            $encrypted_key = file_get_contents($file['key']);
            $file = $file['encfile'];
            $key = $this->decryptWithPrivate($encrypted_key,$fpkey_recipient,$passphrase);
        }

        // Backup original file
        $tmp = "tmp_file";
        copy($file,$tmp);
        $f = fopen($tmp,"r+");
        $out = fopen($outfile,"w");

        $stat = fstat($f);
        if(fseek($f,$stat['size']-$this->hash_size) == 0){
			// Ekstrak signature pada file input
            $hash = fread($f,$this->hash_size);
			fseek($f,0);
			// Hapus data signature pada file
            ftruncate($f,$stat['size']-$this->hash_size);
            fseek($f,0);
        }

        $hmac = hash_hmac_file($this->hash,$tmp,$key,true);

		// Cek apakah signature pada file dan fisik cocok
        if(hash_equals($hash,$hmac)){
            $ivlen = openssl_cipher_iv_length($this->cipher);
            $iv = fread($f,$ivlen);

            while(!feof($f)){
                $buf = fread($f,$this->block_size+$ivlen);
                fwrite($out,openssl_decrypt($buf,$this->cipher,$key,OPENSSL_RAW_DATA,$iv));
            }
            fclose($f);
            fclose($out);
			
			// Hapus temp file bila ada
            if(file_exists($tmp)) unlink($tmp);

            if(file_exists($outfile) && filesize($outfile)) return hash_file($this->hash,$outfile);
        }
        return false;
    }
}