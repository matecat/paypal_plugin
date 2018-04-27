<?php
namespace pingidentity\opentoken\helpers;

/**
 * NULL (no encryption) cipher suite
 */
const CIPHER_SUITE_NULL = 0;

/**
 * AES (256 bit, CBC mode, PKCS/5 padding) cipher suite
 */
const CIPHER_SUITE_AES256CBC = 1;

/**
 * AES (128 bit, CBC mode, PKCS/5 padding) cipher suite
 */
const CIPHER_SUITE_AES128CBC = 2;

/**
 * Triple DES (168 bit, CBC mode, PKCS/5 padding) cipher suite
 */
const CIPHER_SUITE_3DES168CBC = 3;

const V1_MAC_SIZE = 20;

const V1_CS_POS = 4;

const V1_MAC_POS = 5;

const V1_IVLEN_POS = 25;

const V1_IV_POS = 26;



class Token
{
    const CIPHER_SUITE_NULL = 0;

    /**
     * AES (256 bit, CBC mode, PKCS/5 padding) cipher suite
     */
    const CIPHER_SUITE_AES256CBC = 1;

    /**
     * AES (128 bit, CBC mode, PKCS/5 padding) cipher suite
     */
    const CIPHER_SUITE_AES128CBC = 2;

    /**
     * Triple DES (168 bit, CBC mode, PKCS/5 padding) cipher suite
     */
    const CIPHER_SUITE_3DES168CBC = 3;

    const V1_MAC_SIZE = 20;

    const V1_CS_POS = 4;

    const V1_MAC_POS = 5;

    const V1_IVLEN_POS = 25;

    const V1_IV_POS = 26;


    /**
     * function to decode the token
     */
    public static function decode($token_in, $cipherSuite, $password, $useCompression = true)
    {
        $key = NULL;
        $iv = NULL;
        $keyMetaData = NULL;
        $rawData = NULL;
        $cipherSuiteEncoded = NULL;
        $dataMac = NULL;
        $IVLen = NULL;
        $V1_HEADER = array('O', 'T', 'K', 1);

        // first we have to base64 decode the token
        // but first we need to replace any trailing '*' with '='
        for ($b = strlen($token_in) - 1; $b > -1; $b--) {
            if ($token_in[$b] == '*') {
                $token_in[$b] = '=';
            } else {
                break;
            }
        }

        // walk through the in_token and replace all '_' with '/'  and
        // all '-' with '+' to let
        // the standard php base64 code to parse this correctly
        for ($c = 0; $c < strlen($token_in); $c++) {
            if ($token_in[$c] == '_') $token_in[$c] = '/';
            if ($token_in[$c] == '-') $token_in[$c] = '+';
        }

        // decode the token into rawdata
        $rawData = base64_decode($token_in);

        // validate the header
        if ($rawData[0] != $V1_HEADER[0] or
            $rawData[1] != $V1_HEADER[1] or
            $rawData[2] != $V1_HEADER[2] or
            ord($rawData[3]) != $V1_HEADER[3]
        ) {
            return NULL;
        }

        // validate the cipher suite
        $cipherSuiteEncoded = ord(substr($rawData, self::V1_CS_POS, 1));

        // make sure token is encrypted with the same cipher specified
        // in the properties file
        if ($cipherSuiteEncoded != $cipherSuite) {
            return NULL;
        }

        // extract the MAC
        $dataMac = substr($rawData, self::V1_MAC_POS, self::V1_MAC_SIZE);

        // validate the IV length
        $IVLen = ord(substr($rawData, self::V1_IVLEN_POS, 1));

        if (!self::validateIV($IVLen, $cipherSuite)) {
            return NULL;
        }

        // fetch the IV
        $iv = substr($rawData, self::V1_IV_POS, $IVLen);

        // extract the key meta data (if present)
        $keyMetaDataLenOffset = self::V1_IV_POS + $IVLen;
        $keyMetaDataLen = ord(substr($rawData, $keyMetaDataLenOffset, 1));
        $keyMetaDataOffset = $keyMetaDataLenOffset + 1;
        //Validate the Key Info Length
        if ($keyMetaDataLen > 0) {
		return NULL;
        }

        // get the decryption key
        $key = PasswordKeyGenerator::generate($password, $cipherSuite);

        // figure out where the payload is
        $payloadLenOffset = $keyMetaDataLenOffset + $keyMetaDataLen + 1;
        $payloadOffset = $payloadLenOffset + 2;

        // grab the payload
        $payload = substr($rawData, $payloadOffset, strlen($rawData) - $payloadOffset);

        // setup the cipher
        $td = self::setupCipher($cipherSuite, $key, $iv);

        if ($td != NULL) {
            // decrypt the payload
            $decryptedData = mdecrypt_generic($td, $payload);

            // Remove the PKCS-5 padding if any
            $padding_char = ord($decryptedData[strlen($decryptedData) - 1]);
            if ($padding_char < 32) {
                $decryptedData = substr($decryptedData, 0, strlen($decryptedData) - $padding_char);
            }
        } else {
            $decryptedData = $payload;
        }

        // deflate the payload
        try {
            $deflatedData = @gzuncompress($decryptedData);
        } catch (Exception $e) {
            /*
                * if data error occurs on gzuncompress, try inflate
                * with first 2 bytes removed.
                * http://us.php.net/manual/en/function.gzinflate.php#70875
                */
            $deflatedData = gzinflate(substr($decryptedData, 2));
        }

        // create a MAC from the incoming data
        if ($td != NULL) {
            $mac = hash_init('sha1', HASH_HMAC, $key);
        } else {
            $mac = hash_init('sha1');
        }
        //1.  OTK version
        hash_update($mac, chr(0x01)); // version

        //2.  Cipher suite value
        hash_update($mac, chr($cipherSuite)); // ciphersuite

        //3.  IV value (if present)
        if ($IVLen > 0) {
            hash_update($mac, $iv); // initialization vector
        }

        //4.  Key Info value (if present)
        if ($keyMetaDataLen > 0) {
            hash_update($mac, $keyMetaData); // key meta data
        }

        //5.  Payload length (2 bytes, network order)
        hash_update($mac, $deflatedData); // clear text payload

        // ADPT-229 fix
        // hash_update($mac, strlen($deflatedData)); //undo ADPT-229 fix for now

        $hash = hash_final($mac, true);

        // compare this hash with the has in the token
        if (strcmp($hash, $dataMac) != 0) {
            return NULL;
        }

        return KeyValueSerializer::deserialize($deflatedData);
    }

    private static function validateIV($ivlen, $cipherSuite)
    {

        switch ($cipherSuite) {
            case self::CIPHER_SUITE_NULL:
                return $ivlen == 0;
                break;

            case self::CIPHER_SUITE_AES256CBC:
                return $ivlen == 32;

            case self::CIPHER_SUITE_AES128CBC:
                return $ivlen == 16;

            case self::CIPHER_SUITE_3DES168CBC:
                return $ivlen == 8;
        }

        return false;
    }

    public static function setupCipher($cipherSuite, $key, $iv)
    {
        $result = NULL;

        switch ($cipherSuite) {
            case self::CIPHER_SUITE_NULL:
                $result = NULL;
                break;

            case self::CIPHER_SUITE_AES256CBC:
                $result = mcrypt_module_open("rijndael-256", '', 'cbc', '');
                break;

            case self::CIPHER_SUITE_AES128CBC:
                $result = mcrypt_module_open("rijndael-128", '', 'cbc', '');
                break;

            case self::CIPHER_SUITE_3DES168CBC:
                $result = mcrypt_module_open("tripledes", '', 'cbc', '');
                break;
        }

        if ($result != NULL) {
            // generate an iv if needed
            if ($iv == NULL) {
                $iv = self::createIV($cipherSuite);
            }

            // initialize the cipher
            mcrypt_generic_init($result, $key, $iv);
        }

        return $result;
    }


    public static function encode($values, $cipherSuite, $password, $useCompression = true)
    {
        $result = "";

        $key = PasswordKeyGenerator::generate($password, $cipherSuite);
        $iv = self::createIV($cipherSuite);
        $cipher = self::setupCipher($cipherSuite, $key, $iv);

        // Setup the header
        $result .= "OTK" . chr(1);

        // Add the cipher suite
        $result .= chr($cipherSuite);

        // Add space for the MAC
        $result .= str_repeat(chr(0), 20);

        // write the IV len and IV
        $payloadLenOffset = self::V1_IV_POS;
        $iv_len = strlen($iv);

        if ($iv_len != 0) {
            $result .= chr($iv_len);
            $result .= $iv;
            $payloadLenOffset += $iv_len;
        } else {
            $result .= chr(0);
        }

        // Store key metadata (currently we do not support any)
        $result .= chr(0);
        $payloadLenOffset += 1;

        // Encode the values
        $encoded_values = KeyValueSerializer::serialize($values);

        // Compress the payload.  Optionally skip compression if
        // configured to do so by the adapter
        if ($useCompression == true) {
            $compressed_payload = gzcompress($encoded_values);
            $compressed_len = strlen($compressed_payload);
        } else {
            $compressed_payload = gzcompress($encoded_values, 0);
            $compressed_len = strlen($compressed_payload);
        }

        // Pad the payload, per PKCS-5
        if ($iv_len > 0) {
            if ($compressed_len % $iv_len != 0) {
                $padlen = $iv_len - ($compressed_len % $iv_len);
            } // if compressed data fills a perfect set of blocks, add one full
            // block of padding, i.e.
            // EB = ||M|| 08 08 08 08 08 08 08 08 - if ||M|| mod 8 = 0
            else {
                $padlen = mcrypt_enc_get_block_size($cipher);
            }
            $compressed_payload .= str_repeat(chr($padlen), $padlen);
        }

        // Encrypt the payload
        if ($cipher != self::CIPHER_SUITE_NULL) {
            $encrypted_payload = mcrypt_generic($cipher, $compressed_payload);
        } else {
            $encrypted_payload = $compressed_payload;
        }

        // Calculate payload size
        $payloadlenbytes = self::shortToNetwork(strlen($encrypted_payload));
        $result[$payloadLenOffset] = chr($payloadlenbytes[0]);
        $result[$payloadLenOffset + 1] = chr($payloadlenbytes[1]);

        // Append the payload to the result
        $result .= $encrypted_payload;

        // Create the MAC
        if ($cipher != self::CIPHER_SUITE_NULL) {
            $mac = hash_init('sha1', HASH_HMAC, $key);
        } else {
            $mac = hash_init('sha1');
        }

        hash_update($mac, chr(0x01)); // version
        hash_update($mac, chr($cipherSuite)); // ciphersuite

        if ($iv_len > 0) {
            hash_update($mac, $iv); // initialization vector
        }

        hash_update($mac, $encoded_values); // clear text payload

        // ADPT 229 Fix
        //hash_update($mac, strlen($encoded_values));  undo ADPT-229 Fix for now

        // comment - with peter   payload length
        $hash = hash_final($mac, true);

        // Stuff the mac into the token
        $result = substr_replace($result, $hash, self::V1_MAC_POS, 20);

        // base64 the result
        $result = base64_encode($result);

        // replace some of the base64 characters for URL safety
        for ($b = strlen($result) - 1; $b > -1; $b--) {
            if ($result[$b] == '=')
                $result[$b] = '*';
            else
                break;
        }

        // walk through the in_token and replace all '/' with '_'  and
        // all '+' with '-' to let
        // the standard php base64 code to parse this correctly
        for ($c = 0; $c < strlen($result); $c++) {
            if ($result[$c] == '/') $result[$c] = '_';
            if ($result[$c] == '+') $result[$c] = '-';
        }

        return $result;
    }


    private static function createIV($cipherSuite)
    {
        $result = "";
        $iv_size = 0;

        switch ($cipherSuite) {
            case self::CIPHER_SUITE_NULL:
                $result = NULL;
                break;

            // We cannot support AES256SBS becasue the mcrypt implementation of
            // rijndael256 uses a block size of 32 instead of the aes256 block size of 16
            //case self::CIPHER_SUITE_AES256CBC:
            //    $iv_size = mcrypt_get_iv_size( "rijndael-256", "cbc" );
            //    break;

            case self::CIPHER_SUITE_AES128CBC:
                $iv_size = mcrypt_get_iv_size("rijndael-128", "cbc");
                break;

            case self::CIPHER_SUITE_3DES168CBC:
                $iv_size = mcrypt_get_iv_size("tripledes", "cbc");
                break;
        }

        if ($iv_size > 0) {
            $result = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        }

        return $result;
    }


    private static function shortToNetwork($value)
    {
        if ($value > 65536) {
            return NULL;
        }

        return array(($value >> 8) & 0xFF, $value & 0xFF);
    }


    private static function networkToShort($array, $offset)
    {
        $result = 0;

        $result += ($array[$offset] & 0xFF) << 8;
        $result += ($array[$offset + 1] & 0xFF);

        return $result;
    }

}





?>
