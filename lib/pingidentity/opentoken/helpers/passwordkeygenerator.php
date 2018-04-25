<?php
namespace pingidentity\opentoken\helpers;
/***************************************************************************

 * Copyright (C) 2012 Ping Identity Corporation

 * All rights reserved.

 *

 * The contents of this file are the property of Ping Identity Corporation.

 * You may not copy or use this file, in either source code or executable

 * form, except in compliance with terms set by Ping Identity Corporation.

 * For further information please contact:

 *

 *      Ping Identity Corporation

 *      1099 18th St Suite 2950

 *      Denver, CO 80202

 *      303.468.2900

 *      http://www.pingidentity.com

 *

 **************************************************************************/


class PasswordKeyGenerator {

    function getSha1Hmac($password) {
        return hash_init( 'sha1', HASH_HMAC, $password );
    }

    public static function generate($password, $cipherSuite) {
        $salt = chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0);

        return PasswordKeyGenerator::generate_impl($password, $cipherSuite, $salt, 1000);
    }

     static function generate_impl($password, $cipherSuite, $salt, $iterations) {

        if ($cipherSuite == CIPHER_SUITE_NULL) {
            return NULL;
        }

        // Determine how many bytes of key material we need
        $keysize = 0;

        switch ($cipherSuite)
        {
        case CIPHER_SUITE_AES256CBC: // 32 bytes
            $keysize = 32;
            break;
        case CIPHER_SUITE_AES128CBC: // 16 bytes
            $keysize = 16;
            break;
        case CIPHER_SUITE_3DES168CBC: // 24 bytes
            $keysize = 24;
            break;
        }

        // determine the number of blocks
        $numblocks = (int)($keysize / 20);

        if ( $keysize % 20 > 0 )
            $numblocks = $numblocks + 1;

        // Generate the appropriate number of blocks and write their output to
        // the key bytes; note that it's important to start from 1 (vs. 0) as the
        // initial block number affects the hash. It's not clear that this fact
        // is stated explicitly anywhere, but without this approach, the generated
        // keys will not match up with test cases defined in RFC 3962.
        $keyBufferIndex = 0;
        $key = '';

        for ( $i = 1; $i <= $numblocks; $i++ ) {
            $mac = hash_init( 'sha1', HASH_HMAC, $password );
            $block = PasswordKeyGenerator::generateBlock($password, $mac, $salt, $iterations, $i);
            $len = min(20, $keysize - $keyBufferIndex);
            $key .= substr($block, 0, $len);
            $keyBufferIndex += $len;
        }

        return $key;
    }

    static function generateBlock( $password, $mac, $salt, $count, $index ) {
        hash_update($mac, $salt);
        hash_update($mac, pack('N', $index));

        $result = hash_final($mac, true);
        $cur = $result;

        for ( $i = 1; $i < $count; $i++ ) {
            $mac = hash_init( 'sha1', HASH_HMAC, $password );
            hash_update($mac, $cur);
            $cur = hash_final($mac, true);

            for ($j = 0; $j < 20; $j++) {
                $result[$j] = $result[$j] ^ $cur[$j];
            }
        }
        return $result;
    }
  }
?>
