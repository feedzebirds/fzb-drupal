<?php
/*
 * Copyright 2011 Bear Bones limited
 *
 * Released under the GNU General Public License
 *
 * Author: Ira Miller
 * 
 * CyPay API functions for easy integration. :)
 */
require_once("cypay_config.php");
require_once("cypay_util.php");
require_once("cypay_error.php");

/**
 * Get new Bitcoin addresses. CyPay will notify your callback URL after
 * a transaction has received the number of confirmations you
 * specify here.
 *
 * Parameters:
 * confirmations    The number of confirmations to require
 *                  before approving new funds
 * qty              The number of Bitcoin addresses you need. Max 10
 * key              Your CyPay application key
 * secret           Your CyPay application secret
 *
 * Return:
 * object containing (on success):
 * 0 through qty-1  New Bitcoin addresses for your CyPay account
 *
 * on failure:
 * ERRORCODE
 * ERRORMESSAGE
 */
function getNewAddress($confirmations = -1, $qty = 1, $key = "", $secret = "") {
  //Replace parameters with defined values, if not set.
  if($confirmations < 0) $confirmations = CONFIRMATIONS;

  //Check for bad values before sending to gateway
  if(!is_numeric($confirmations) ||
     $confirmations < 0 ||
     $confirmations > 6 ||
     !is_numeric($qty) ||
     $qty < 1 ||
     $qty > 10)
    return new cyError(10, "Malformed Request");

  $gate = "http://cypay.co:53135/api/getnewaddress";
  $params = new stdClass();
  $params->confs = (string) $confirmations;
  $params->qty = (string) $qty;

  return sendToGateway($params, $gate, $key, $secret);
}

/**
 * Get Bitcoin address information.
 *
 * Parameters:
 * address          The Bitcoin address you need info for
 * key              Your CyPay application key
 * secret           Your CyPay application secret
 *
 * Return:
 * object containing (on success):
 * received         The amount received by the address, as a float (i.e. 8.88)
 *
 * on failure:
 * ERRORCODE
 * ERRORMESSAGE
 */
function getAddressInfo($address, $key = "", $secret = "") {
  //Check for bad values before sending to gateway
  if(empty($address) || !addressLooksValid($address))
    return new cyError(10, "Malformed Request");

  $gate = "http://cypay.co:53135/api/getaddressinfo";
  $params = new stdClass();
  $params->address = $address;

  return sendToGateway($params, $gate, $key, $secret);
}

/**
 * Send Bitcoin to the specified address.
 *
 * Parameters:
 * amount           The amount of Bitcoin to send, as a float value (i.e. 1.2)
 * address          The Bitcoin address to send the funds to
 * key              Your CyPay application key
 * secret           Your CyPay application secret
 *
 * Return:
 * object containing (on success):
 * cytxid           CyPay transaction id
 *
 * on failure:
 * ERRORCODE
 * ERRORMESSAGE
 */
function sendBitcoin($amount, $address, $key = "", $secret = "") {
  //Check for bad values before sending to gateway
  $address = trim($address, " \n");
  if(empty($amount) || !is_numeric($amount) || empty($address) ||
     !addressLooksValid($address))
    return new cyError(10, "(lib) Malformed Request".json_encode($amount.$address));

  $gate = "http://cypay.co:53135/api/sendbitcoin";
  $params = new stdClass();
  $params->amount = (string) $amount;
  $params->address = $address;

  return sendToGateway($params, $gate, $key, $secret);
}

/**
 * Get Account balance.
 *
 * Parameters:
 * curcode          The currency code for the balance you wish to receive (only BTC right now)
 * key              Your CyPay application key
 * secret           Your CyPay application secret
 *
 * Return:
 * object containing (on success):
 * btcbal           Your Bitcoin balance with CyPay
 *
 * on failure:
 * ERRORCODE
 * ERRORMESSAGE
 */
function getAccountBalance($curcode, $key = "", $secret = "") {
  //Check for bad values before sending to gateway
  if(empty($curcode) || strlen($curcode) != 3)
    return new cyError(10, "Malformed Request");

  $gate = "http://cypay.co:53135/api/getbalance";
  $params = new stdClass();
  $params->curcode = $curcode;

  return sendToGateway($params, $gate, $key, $secret);
}
?>
