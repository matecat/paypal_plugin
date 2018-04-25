<?php
namespace pingidentity\opentoken;

use pingidentity\opentoken\helpers\token;
use pingidentity\opentoken\helpers\multistringarray;
use pingidentity\opentoken\helpers\keyvalueserializer;

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

/**
 * Key into token values collection for subject value
 */
const TOKEN_SUBJECT ="subject";
/**
 * Key into token values collection for not-before value
 */
const TOKEN_NOT_BEFORE = "not-before";
/**
 * Key into token values collection for not-on-or-after value
 */
const TOKEN_NOT_ON_OR_AFTER = "not-on-or-after";
/**
 * Key into token values collection for renew-until value
 */
const  TOKEN_RENEW_UNTIL= "renew-until";
/**
 * Key into token values collection for authnContext value
 */
const  AUTHN_CTX_ATTRIBUTE_NAME = "authnContext";
/**
 * Date/Time mask for token timestamps
 */
const ISO_8601_GMT = "Y-m-d\TH:i:s\Z";

/**
 * Provides the methods necessary for generating and consuming a valid OpenToken cookie or query parameter.
 * @package opentoken
 */
class Agent {


    /**
     * The name used to identify the token (either as a query parameter or cookie).
     */
    var $tokenName;
    /**
     * The span of time (in seconds) during which generated tokens will be considered valid.
     */
    var $tokenLifetime;
    /**
     * The span of time (in seconds) during which this token may be renewed.
     */
    var $renewUntilLifetime;
    /**
     * The number of seconds in the future that validation of the not-before field will permit.
     */
    var $tokenNotbeforeTolerance;
    /**
     * Gets the password to use for token encryption; actual key is generated via PBKDF2.
     * @see http://tools.ietf.org/html/rfc2898#appendix-A.2
     */
    var $password;
    /**
     * Flag indicating if the token will be exchanged using a cookie.
     */
    var $useCookie;
    /**
     * The path used when writing a token as a cookie.
     */
    var $cookiePath;
    /**
     * The domain used when writing a token as a cookie.
     */
    var $cookieDomain;
    /**
     * Sets the number representing which cipher suite to be used for token encryption.
     * @param cipherSuite the suite number
     * @see CIPHER_SUITE_3DES168CBC
     * @see CIPHER_SUITE_AES128CBC
     * @see CIPHER_SUITE_AES256CBC
     * @see CIPHER_SUITE_NULL
     */
    var $cipherSuite;
    /**
     * Flag indicating if the token will be compressed.
     */
    var $useCompression;
    /**
     * a message string describing the last error encountered by the Agent
     */
    var $lastError;
    /**
     * Flag indicating secure transport only for cookies (HTTPS)
     */
    var $secureCookie;
    /**
     * Flag indicating cookie should expire at the end of browser session.  If false,
     * expiry is set to $tokenLifetime
     */
    var $sessionCookie;
    /**
     * Flag indicating enable/disable verbose logging. If false, the detailed error
     * message is suppressed to avoid Padding Oracle Attack
     */
    var $useVerboseErrorMessages;
    /**
     * Constructs an Agent with values from configuration file
     * @see AGENT_CONFIG_FILE
     */

    function __construct() {
        $this->tokenName = "opentoken";
        $this->tokenLifetime = 300;  // 5 minutes
        $this->renewUntilLifetime = 43200; // 12 hours
        $this->useCookie = false;
        $this->cookiePath = "/";
        $this->cookieDomain = NULL;
        $this->secureCookie = false;
        $this->sessionCookie = false;
        $this->lastError = "";
        $this->cipherSuite = "";
        $this->useCompression = true;
        $this->tokenNotbeforeTolerance = 120; // 2 minutes
        $this->useVerboseErrorMessages = false;

        $this->loadConfiguration();
    }

    /**
     * Reads key/value(s) pairs stored in a token string.  If the return value is null (thus no token was present), $lastError will
     * provide an error message describing the problem.
     *
     * @param string $token
     * @return MultiStringArray key/value(s) pairs if successful
     */
    function readTokenToMultiStringArray( $token ) {
        return $this->readToken($token, true);
    }

    /**
     * Reads key/value pairs stored in a token string.  If the return value is null (thus no token was present), $lastError will
     * provide an error message describing the problem.
     *
     * @param string $token
     * @return Arrays key/value(s) pairs if successful
     */
    function readToken( $token, $multiStringArray=false ) {

        $ids = Token::decode($token, $this->cipherSuite, $this->password, $this->useCompression);

        if ( $ids == NULL ) {
            // something went wrong decoding the token
            // bail
            if($this->useVerboseErrorMessages){
                $this->lastError = "Invalid Token: could not decode the token.";
            }
            else{
                $this->lastError = "Error";
            }
            return NULL;
        }

        // validate subject
        if ($ids->containsKey(TOKEN_SUBJECT) == false ) {
            $this->lastError = "Token does not have a subject key/value pair.";
            return NULL;
        }

        // Validate the dates
        $now = time();
        $futureNow = $now + $this->tokenNotbeforeTolerance;
        $notBefore = $this->parseDate($ids->get(TOKEN_NOT_BEFORE, 0));
        $notOnOrAfter = $this->parseDate($ids->get(TOKEN_NOT_ON_OR_AFTER, 0));
        $renewUntil = $this->parseDate($ids->get(TOKEN_RENEW_UNTIL, 0));

        if ( $notBefore > $notOnOrAfter ) {
            $this->lastError = "Invalid Token: not-on-or-after precedes not-before.";
            return NULL;
        }
        elseif ($notBefore > $now && $notBefore > $futureNow) {
            $this->lastError = "Invalid Token: token is not yet valid (not-before > now)";
            return NULL;
        }
        elseif ($now > $notOnOrAfter) {
            $this->lastError = "Invalid token; token has expired (now > not-on-or-after)";
            return NULL;
        }
        elseif ($now > $renewUntil) {
            $this->lastError = "Invalid token; token may no longer be renewed (now > renew-until)";
            return NULL;
        }

        if($multiStringArray == true) {
            return $ids;
        }
        else {
            return $this->flattenMultiStringArray($ids);
        }
    }

    /**
     * Reads key/value pairs stored in a token on the HTTP request.  If the return value is null (thus no token was present), $lastError will
     * provide an error message describing the problem.
     *
     * @return MultiStringArray key/value pairs if successful
     */
    function readTokenFromHTTPRequestToMultiStringArray() {
        return $this->readTokenFromHTTPRequest(true);
    }

    /**
     * Reads key/value pairs stored in a token on the HTTP request.  If the return value is null (thus no token was present), $lastError will
     * provide an error message describing the problem.
     *
     * @return Array key/value pairs if successful
     */
    function readTokenFromHTTPRequest($multiStringArray=false) {
        $result = NULL;
        $token = NULL;

        // if we use cookies then extract the token fromthe cookie
        if ( $this->useCookie == true ) {
            if ( array_key_exists($this->tokenName, $_COOKIE) ) {
                $token = $_COOKIE[$this->tokenName];
            } else {
                $this->lastError = "No cookie token found named '" . $this->tokenName . "'";
            }
        }
        // if we do not use cookies then extract it from the query parameters
        else {
            if ( array_key_exists($this->tokenName, $_GET) ) {
                $token = $_GET[$this->tokenName];
            } elseif ( array_key_exists($this->tokenName, $_POST) ) {
                $token = $_POST[$this->tokenName];
            } else {
                $this->lastError = "No query or POST parameter token found named '" . $this->tokenName . "'";
            }
        }

        if ( $token != NULL ) {
            $result = $this->readToken($token, $multiStringArray);
        }

        return $result;
    }

    /**
     * Writes map of key/value pairs to a new token.
     *
     * @param Array key/value pairs
     * @return string token
     */
    function writeToken($ids) {
        // update the time stamps if needed
        $now = time();

        if( $ids instanceof MultiStringArray == false) {
            $ids = $this->convertToMultiStringArray($ids);
        }

        $ids->remove(TOKEN_NOT_BEFORE);
        $ids->add(TOKEN_NOT_BEFORE, gmdate(ISO_8601_GMT , $now));

        $ids->remove(TOKEN_NOT_ON_OR_AFTER);
        $ids->add(TOKEN_NOT_ON_OR_AFTER, gmdate(ISO_8601_GMT, $now + $this->tokenLifetime));

        if ( $ids->containsKey(TOKEN_RENEW_UNTIL) == false) {
            $ids->add(TOKEN_RENEW_UNTIL,gmdate(ISO_8601_GMT, $now + $this->renewUntilLifetime));
        }

        // return the new token
        return  Token::encode($ids, $this->cipherSuite, $this->password, $this->useCompression);
    }

    /**
     * Writes map of key/value pairs to a new token and puts it on the HTTP Response as either a cookie or query parameter
     *
     * @param Array key/value pairs
     * @return string token (optionally, if using query parameter)
     */
    function writeTokenToHTTPResponse($ids) {
        $token = $this->writeToken($ids);

        // write to cookie if we are using cookies
        if ( $this->useCookie == true ) {
            $expiry = $this->sessionCookie ? 0 : time() + $this->tokenLifetime;
            setrawcookie($this->tokenName, $token, $expiry, $this->cookiePath, $this->cookieDomain, $this->secureCookie);
            return null;
        } else {
            return $this->tokenName . "=" . $token;
        }
    }

    /**
     * Reads agent configuration from file
     * @see AGENT_CONFIG_FILE
     */
    function loadConfiguration() {
        // open the config file and read its contents
        // $config_data = file_get_contents(\pingidentity\opentoken\helpers\config:: AGENT_CONFIG_FILE);
        $config_data = file_get_contents( \pingidentity\opentoken\helpers\config:: AGENT_CONFIG_FILE);
        $config = $this->flattenMultiStringArray(KeyValueSerializer::deserialize($config_data));

        // set the various instance variables
        if ( array_key_exists("token-name", $config) == true ) {
            $this->tokenName = $config["token-name"];
        }

        if ( array_key_exists("token-lifetime", $config) == true ) {
            $this->tokenLifetime = $config["token-lifetime"];
        }

        if ( array_key_exists("token-renewuntil", $config) == true ) {
            $this->renewUntilLifetime = $config["token-renewuntil"];
        }

        if ( array_key_exists("use-cookie", $config) == true ) {

            //trim any whitespace that may have been introduced by an
            //editor
            $this->useCookie = trim($config["use-cookie"]) == "true";

            if ( array_key_exists("cookie-domain", $config) == true ) {
                $this->cookieDomain = $config["cookie-domain"];
            }

            if ( array_key_exists("cookie-path", $config) == true ) {
                $this->cookiePath = $config["cookie-path"];
            }

            if ( array_key_exists("secure-cookie", $config) == true ) {
                $this->secureCookie = $config["secure-cookie"];
            }

            if ( array_key_exists("session-cookie", $config) == true ) {
                $this->sessionCookie = $config["session-cookie"];
            }
        }

        if ( array_key_exists("password", $config) == true ) {

            $obfuscated_password = $config["password"];
            $this->password = base64_decode($obfuscated_password);

            if ( array_key_exists("cipher-suite", $config) == true ) {
                $this->cipherSuite = $config["cipher-suite"];
            }
        }

        if ( array_key_exists("token-notbefore-tolerance", $config) == true ) {
            $this->tokenNotbeforeTolerance = $config["token-notbefore-tolerance"];
        }

        if( array_key_exists("use-compression", $config) == true ) {
            $this->useCompression = trim($config["use-compression"]) == "true";
        }
        if( array_key_exists("use-verbose-error-messages", $config) == true ) {
            $this->useVerboseErrorMessages = trim($config["use-verbose-error-messages"]) == "true";
        }
    }

    /**
     * Writes agent configuration to file
     * @see AGENT_CONFIG_FILE
     */
    function storeConfig() {
        $config = array();

        $config["token-name"] = $this->tokenName;
        $config["token-lifetime"] = $this->tokenLifetime;
        $config["token-renewuntil"] = $this->renewUntilLifetime;

        if ( $this->useCookie != NULL and $this->useCookie == true ) {

            $config["use-cookie"] = $this->useCookie;
            $config["cookie-path"] = $this->cookiePath;
            $config["secure-cookie"] = $this->secureCookie;
            $config["session-cookie"] = $this->sessionCookie;

            if ($this->cookieDomain != NULL and strlen($this->cookieDomain) != 0) {
                $config["cookie-domain"] = $this->cookieDomain;
            }
        }

        if ($this->password != NULL and strlen($this->password) != 0) {
            $config["cipher-suite"] = $this->cipherSuite;
            $config["password"] = base64_encode($this->password);
        }

        $config["use-compression"] = $this->useCompression;
        $config["use-verbose-error-messages"] = $this->useVerboseErrorMessages;
        $data = KeyValueSerializer::serialize($config);

        file_put_contents("pfagent-sp.properties", $data);
    }

    /**
     * returns a unix date integer given a date string
     */
    private function parseDate( $date ) {
        $result = NULL;
        if ( $date != NULL ) {
            $array = date_parse($date);
            $result = gmmktime( $array["hour"],
                $array["minute"],
                $array["second"],
                $array["month"],
                $array["day"],
                $array["year"]);
        }
        return $result;
    }

    private function flattenMultiStringArray($multiStringArray) {

        $flatResult = array();

        foreach($multiStringArray->keySet() as $key) {
            $flatResult[$key] = $multiStringArray->get($key, 0);
        }

        return $flatResult;
    }

    private function convertToMultiStringArray($ids){
        $multiStringArray = new MultiStringArray();

        foreach(array_keys((array) $ids) as $key){
            $multiStringArray->add($key, $ids[$key]);
        }

        return $multiStringArray;
    }

}
?>
