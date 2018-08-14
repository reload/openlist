<?php
/**
 * Openlist index file | index.php
 *
 * @package     Openlist
 * @author      B14
 * @version     2.0
 */

/**
 * WSDL:
 * http://code.google.com/p/php-wsdl-creator/
 * http://framework.zend.com/manual/en/zend.soap.html
 *
 * SOAP:
 * http://dk.php.net/manual/en/class.soapserver.php
 */

require_once 'settings.php';

if (IS_PRODUCTION) {
  // Producton will return HTTP status code 500 on PHP errors
  ini_set('display_errors', 0); 
} else {
  // Development will output stuff
  ini_set('display_errors', 1);
  error_reporting(E_ERROR | E_WARNING | E_PARSE); 
}

// @TODO implement individual cache headers for each OpenList function
header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() - (24 * 60 * 60)));


if (!isset($_GET['wsdl'])) {
  require_once OPENLIST_ROOT . '/boot.php';
}
else {
  require_once OPENLIST_ROOT . '/wsdl.php';
}
