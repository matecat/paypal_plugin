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


use pingidentity\opentoken\helpers\multistringarray;

// state machine values
const LINE_START = 0;
const EMPTY_SPACE = 1;
const VALUE_START = 2;
const LINE_END = 3;
const IN_KEY = 4;
const IN_VALUE = 5;
const IN_QUOTED_VALUE = 6;

class KeyValueSerializer {
  // function serialize takes a php keyed array of key value pairs
  // and returns a string suitable for opentoken payloads
  static function serialize( $map ) {
      $result = "";
      $count = 0;

      if( $map instanceof MultiStringArray ) {
          foreach ( $map->keySet() as $key ) {
              foreach($map->get($key) as $index => $val) {
                  if ( $count != 0 ) $result .= "\n";
                  $count++;
                  // make sure the key does not have any equal signs
                  if (strpos($key, "=") != false) {
                      return NULL;
                  }
                  $result .= $key;
                  $result .= "=";
                  $result .= KeyValueSerializer::escapeValue($val);
              }
          }
      }
      else {
          foreach ($map as $key => $val) {
              if ( $count != 0 ) $result .= "\n";
              $count++;
              // make sure the key does not have any equal signs
              if (strpos($key, "=") != false) {
                  return NULL;
              }

              $result .= $key;
              $result .= "=";
              $result .= KeyValueSerializer::escapeValue($val);
          }
      }

      return $result;
  }

  static function escapeValue($value) {

      $needsQuotes = false;
      $singleQuoteCount = 0;
      $doubleQuoteCount = 0;
      $backSlashCount = 0;

      if ( $value == null ) return "";

      for ($i = 0; $i < strlen($value); $i++) {
          switch ($value[$i]) {
              case "\t":
              case " ":
              case "\n":
                  $needsQuotes = true;
                  break;
              case "\"":
                  $doubleQuoteCount++;
                  break;
              case "'":
                  $singleQuoteCount++;
                  break;
              case "\\": //Escaping Backslash as part of ADPT-228
                  $backSlashCount++;
                  break;
          }
      }

      // if spaces or quotes or backslash were encountered, we need to proceed with escaping
      if ( $needsQuotes == true or $singleQuoteCount > 0 or $doubleQuoteCount > 0 or $backSlashCount > 0) {
          return "'" . str_replace("'", "\'", str_replace( "\"", "\\\"", str_replace( "\\", "\\\\", $value))) . "'";
      }

      return $value;
  }

  static function unescapeValue ($value) {
      return str_replace("\\\\", "\\", str_replace("\\\"", "\"", str_replace("\'", "'", $value)));

  }

  // function deserialize takes a string and returns
  // a keyed array of key value pairs.
  static function deserialize( $string ) {
      $result = new MultiStringArray();
      $state = LINE_START;
      $openQuoteChar = chr(0);
      $currkey = '';
      $token = "";
      $nextval = '';

      for ( $i = 0; $i < strlen($string); $i++ ) {
          $nextval = $string[$i];
          $c = $nextval;

          switch ($c) {
              case "\t":
              case " ":
                  if ($state == IN_KEY) {
                      // key ends
                      $currkey = $token;
                      $token = "";
                      $state = EMPTY_SPACE;
                  }
                  elseif ($state == IN_VALUE) {
                      // non-quoted value ends
                      $result->add($currkey, KeyValueSerializer::unescapeValue($token));
                      $token = "";
                      $state = LINE_END;
                  }
                  elseif ($state == IN_QUOTED_VALUE) {
                      $token .= $c;
                  }
                  break;

              case "\n":
                  // newline
                  if ($state == IN_VALUE || $state == VALUE_START) {
                      // non quoted value ends -- update values map
                      $result->add($currkey, KeyValueSerializer::unescapeValue($token));
                      $token = "";
                      $state = LINE_START;
                  }
                  elseif ($state == LINE_END) {
                      $token = "";
                      $state = LINE_START;
                  }
                  elseif ($state == IN_QUOTED_VALUE) {
                      $token .= $c;
                  }
                  break;

              case "=":
                  // key identifier
                  if ($state == IN_KEY) {
                      // key ends
                      $currkey = $token;
                      $token = "";
                      $state = VALUE_START;
                  }
                  elseif ($state == EMPTY_SPACE) {
                      $token = "";
                      $state = VALUE_START;
                  }
                  elseif ($state == IN_QUOTED_VALUE or $state == IN_VALUE) {
                      $token .= $c;
                  }
                  break;

              case "\"":
              case "'":
                  if ($state == IN_QUOTED_VALUE) {
                      // If the opening quote character is the same as what we encountered, and
                      // the previous character is not an escape, the quote is terminated
                      if ($c == $openQuoteChar and !KeyValueSerializer::isEscaped($token)) {
                          // terminate quoted value -- update values map
                          $result->add($currkey, KeyValueSerializer::unescapeValue($token));
                          $token = "";
                          $state = LINE_END;
                      } else {
                          $token .= $c;
                      }
                  } elseif ($state == VALUE_START) {
                      $state = IN_QUOTED_VALUE;
                      $openQuoteChar = $c;
                  }
                  break;

              default:
                  if ($state == LINE_START) {
                      $state = IN_KEY;
                  }
                  elseif ($state == VALUE_START) {
                      $state = IN_VALUE;
                  }

                  $token .= $c;
                  break;
          }
      }

      // wrap up any pending values
      if ($state == IN_QUOTED_VALUE or $state == IN_VALUE) {
          $result->add($currkey, KeyValueSerializer::unescapeValue($token));
      }

      return $result;
  }

  /** Determine if the current character is escaped by a single unescaped backslash.
   *  Since an arbitrary number of backslashes could precede the current character,
   *  we need to count all preceding backslashes.  If odd, return true.
   */
  static function isEscaped($token){
      $backSlashCount = 0;
      for( $i = (strlen($token)-1); $i >= 0; $i--)
      {
          if ( $token[$i] == "\\"){
              $backSlashCount++;
          }
          else{
              break;
          }
      }
      return ($backSlashCount & 1);
  }

}
?>