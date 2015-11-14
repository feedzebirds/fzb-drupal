<?php
/*
 * Copyright 2011 Bear Bones limited
 *
 * Released under the GNU General Public License
 *
 * Author: Ira Miller
 * 
 * Set CyPay SCI gateway options here. These can be overridden by sending corresponding values
 * in an array to certain helper functions.
 *
 */

/*
 * KEY and SECRET should be set to the API credentials provided
 * for your CyPay application.
 */
  define('KEY', "");
  define('SECRET', "");

/*
 * $confirmations specifies how many confirmations you wish CyPay
 *                to wait before verifying a Bitcoin transaction.
 *                The default is 2.
 *                The maximum allowed value is 6, and the minimum is 0.
 */
  define('CONFIRMATIONS', 2);
?>
