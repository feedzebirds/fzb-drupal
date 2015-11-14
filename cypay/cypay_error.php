<?php
/**
 * Class representing a CyPay error.
 *
 * Each error has a code and a message, which are public properties.
 */
class cyError { 
  public $code;
  public $message;

  function __construct($cod, $mess) {
    $this->code = $cod;
    $this->message = $mess;
  }
} 
?>
