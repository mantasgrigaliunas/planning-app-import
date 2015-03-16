<?php
//  A place for re-usable and static functions.. 
class UtilityFunctions {
       public static function decryptValues($encrypted) {

        $key = base64_decode('BGrHVkZLRuvYfD8mTMo85g==');
        // show key size use either 16, 24 or 32 byte keys for AES-128, 192
        $key_size = strlen($key);
        // create a random IV to use with CBC encoding
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $ciphertext_dec = base64_decode($encrypted);
        // retrieves the IV, iv_size should be created using mcrypt_get_iv_size()
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);
        // retrieves the cipher text (everything except the $iv_size in the front)
        $ciphertext_dec = substr($ciphertext_dec, $iv_size);
        // may remove 00h valued characters from end of plain text
        $plaintext_dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
        return $plaintext_dec;
    }
}
