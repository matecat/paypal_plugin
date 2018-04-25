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

const MULTI_STRING_ARRAY_DEFAULT =  null;

class MultiStringArray
{


    private $_values = array();

  function __construct($values = array())
  {
    if (!empty($values)) {
      $this->_values = $values;
    }
  }

  function clear()
  {
    $this->_values = array();
  }

  function containsKey($key)
  {
    return array_key_exists($key, $this->_values);
  }

  function get($key, $index=-1, $default=MULTI_STRING_ARRAY_DEFAULT)
  {
    if ($this->containsKey($key)) {
	if($index > -1) {
		return $this->_values[$key][$index];
	}
	else {
		return $this->_values[$key];
	}
    } else {
      if (MULTI_STRING_ARRAY_DEFAULT != $default) {
        return $default;
      }
    }
  }

  function isEmpty()
  {
    return empty($this->_values);
  }

  function keySet()
  {
    return array_keys($this->_values);
  }

  function add($key, $value)
  {
    if(!$this->containsKey((string)$key))
    {
      $this->_values[(string)$key] = array((string)$value);
    }
    else
    {
      $this->_values[(string)$key][count($this->get((string)$key))] = (string)$value;
    }
  }

  function remove($key)
  {
    $value = $this->get($key);
    if (!is_null($value)) { unset($this->_values[$key]); }
    return $value;
  }

  function size()
  {
    return count($this->_values);
  }
}
?>
