<?php
namespace pingidentity\opentoken\helpers;

use pingidentity\opentoken\agent;

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


// include this file to automatically parse the open token and set global variables with some
// of the open token values.
// The global variables are:

$opentoken_subject = null;         // the authenticated subject (user)
$opentoken_haveValidToken = false; // set to true if the token was valid
$opentoken_lastError = null; // contains a string describing any open token errors
$opentoken_values = null; // contains an array of all the token values
$opentoken_valuesMultiStringArray = null;

// code begins here

$opentoken_agent = new Agent();
$opentoken_values = $opentoken_agent->readTokenFromHTTPRequest();
$opentoken_valuesMultiStringArray = $opentoken_agent->readTokenFromHTTPRequestToMultiStringArray();

if ($opentoken_values != null || $opentoken_valuesMultiStringArray != null) {
    $opentoken_haveValidToken = true;
    $opentoken_subject = $opentoken_values[\pingidentity\opentoken\TOKEN_SUBJECT];
}

$opentoken_lastError = $opentoken_agent->lastError;

?>