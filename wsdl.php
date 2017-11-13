<?php

/**
 * @file
 * Gets the WSDL file.
 */

header('Content-Type: text/xml; charset=utf-8');

// Only create a new WSDL file if the OpenList.php file has changed, since
// the last WSDL file was created.
//
// NOTICE FALSE!
if (FALSE && filemtime(OPENLIST_CLASSES_PATH . '/OpenList.php') < filemtime(WSDL_LOCAL_PATH)) {
  echo file_get_contents(WSDL_LOCAL_PATH);
}
else {
  try {
    require_once OPENLIST_CLASSES_PATH . '/OpenList.php';

    $autodiscover = new Zend_Soap_AutoDiscover();

    $protocol = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
      $protocol = 'https';
    }
    $autodiscover->setClass('OpenList');
    $autodiscover->setUri($protocol . '://' . $_SERVER['HTTP_HOST']);
    $autodiscover->dump(WSDL_LOCAL_PATH);
    $autodiscover->handle();
  }
  catch(Exception $e) {

  }
}
