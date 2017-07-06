<?php

/**
 * @file
 * Gets the WSDL file.
 */

/**
 * Convert array to xml.
 */
function array_to_xml($array, &$xml) {
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      if (!is_numeric($key)) {
        $subnode = $xml->addChild("$key");
        array_to_xml($value, $subnode);
      }
      else {
        $subnode = $xml->addChild("item$key");
        array_to_xml($value, $subnode);
      }
    }
    else {
      $xml->addChild("$key", htmlspecialchars("$value"));
    }
  }
}

function send_cache_headers($seconds = 0) {
  if ($seconds == 0) {
    // No caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    return;
  }

  // Allow upstream caching $seconds from now
  $timestamp = time();

  header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', time() ) ) ; 
  header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', (time() + $seconds) ) ) ; 

}
