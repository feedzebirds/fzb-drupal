<?php
/*
 * Copyright 2011 Bear Bones limited
 *
 * Released under the GNU General Public License
 *
 * Author: Ira Miller
 * 
 * CyPay SCI functions for easy integration. :)
 */
require_once("cypay_config.php");
require_once("cypay_util.php");
require_once("cypay_error.php");

/**
 * Set initial order details for CyPay Express Checkout.
 *
 * Parameters:
 * total            The order total
 * curcode          The ISO 4217 currency code for total
 * returnURL    	The return URL for your cart (optional)
 * cancelURL    	The cancel URL for your cart (optional)
 * key              Your CyPay application key
 * secret           Your CyPay application secret
 *
 * Return:
 * object containing (on success):
 * oid              The order ID
 *
 * on failure:
 * ERRORCODE
 * ERRORMESSAGE
 */
function setDetails($total, $curcode, $returnURL = "", $cancelURL = "",
                       $key = "", $secret = "") {
  //Check for bad values before sending to gateway
  if(empty($total) ||
     !is_numeric($total) ||
     empty($curcode) ||
     strlen($curcode) != 3)
    return new cyError(10, "Malformed Request");

  $gate = "http://cypay.co:53135/sci/setdetails";
  $params = new stdClass();
  $params->total = (string) $total;
  $params->curcode = $curcode;
  $params->returnURL = $returnURL;
  $params->cancelURL = $cancelURL;

  return sendToGateway($params, $gate, $key, $secret);
}

/**
 * Get the details of the order specified by oid.
 *
 * Parameters:
 * oid              The ID of the order to get details for
 * key              Your CyPay application key
 * secret           Your CyPay application secret
 *
 * Return:
 * object containing (on success):
 * status		    Status (int)
 * total		    Total amount in {curcode} currency (float)
 * btc		        Total in BTC (float)
 * curcode	        Currency code for total
 * return-url	    The return URL for your cart
 * cancel-url	    The cancel URL for your cart
 * notes		    Notes on the order.
 *
 * on failure:
 * ERRORCODE
 * ERRORMESSAGE
 */
function getDetails($oid, $key = "", $secret = "") {
  //Check for bad values before sending to gateway
  if(empty($oid))
    return new cyError(10, "Malformed Request");

  $gate = "http://cypay.co:53135/sci/getdetails";
  $params = new stdClass();
  $params->oid = $oid;

  return sendToGateway($params, $gate, $key, $secret);
}

/**
 * Update an existing order.
 *
 * Parameters:
 * oid		        Order ID to update
 * total		    Total amount in {curcode} currency (float)
 * curcode	        Currency code for total
 * return-url	    The return URL for your cart
 * cancel-url	    The cancel URL for your cart
 *
 * Return:
 * object containing (on success):
 * status		    Order state
 *
 * on failure:
 * ERRORCODE
 * ERRORMESSAGE
 */
function updateOrder($oid, $total, $curcode,
                     $returnURL = "", $cancelURL = "",
                     $key = "", $secret = "") {
  //Check for bad values before sending to gateway
  if(empty($oid) ||
     empty($total) ||
     !is_numeric($total) ||
     empty($curcode) ||
     strlen($curcode) != 3)
    return new cyError(10, "Malformed Request");

  $gate = "http://cypay.co:53135/sci/updateorder";
  $params = new stdClass();
  $params->oid = $oid;
  $params->total = (string) $total;
  $params->curcode = $curcode;
  $params->returnURL = $returnURL;
  $params->cancelURL = $cancelURL;

  return sendToGateway($params, $gate, $key, $secret);
}

/**
 * Get a Bitcoin payment address for the order.
 *
 * Parameters:
 * oid		        Order ID to get an address for
 * confs		    Number of confirmations to require before order is Verified
 * key              Your CyPay application key
 * secret           Your CyPay application secret
 *
 * Return:
 * object containing (on success):
 * address          The Bitcoin address for this order
 *
 * on failure:
 * ERRORCODE
 * ERRORMESSAGE
 */
function getOrderAddress($oid, $confs = -1, $key = "", $secret = "") {
  //Replace parameters with defined values, if not set.
  if($confs < 0) $confs = CONFIRMATIONS;

  //Check for bad values before sending to gateway
  if(empty($oid) ||
     !is_numeric($confs) ||
     $confs < 0 ||
     $confs > 6)
    return new cyError(10, "Malformed Request");

  $gate = "http://cypay.co:53135/sci/getorderaddress";
  $params = new stdClass();
  $params->oid = $oid;
  $params->confs = (string) $confs;

  return sendToGateway($params, $gate, $key, $secret);

}
?>
