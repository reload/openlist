<?php
/**
 * @file
 * Boot.
 */
define('OPENLIST_VERSION', '2.0.1');

require_once OPENLIST_ROOT . '/utils.php';

// Fetch library code
$GLOBALS['library_code'] = isset($_COOKIE['library_code']) ?
  $_COOKIE['library_code'] : (isset($_GET['library_code']) ?
    $_GET['library_code'] : FALSE);

if ($GLOBALS['library_code'] == FALSE) {
  // Status output for index file with no library code sent.
  $out = array('OpenList Version ' .  OPENLIST_VERSION);

  $out[] = 'Name:' . OPENLIST_INSTANCE_NAME;
  require_once OPENLIST_CLASSES_PATH . '/DB.php';
  $result = DB::q('SELECT VERSION();');
  if ($result) {
    $out[] = 'Status:OK';
  }
  $out[] = 'Servertime:' . gmdate('D, d M Y H:i:s \G\M\T', time());
  $out[] = 'Authenticated accounts:' . count($authkeys);
  send_cache_headers(1);
  exit(implode("<br>\n", $out));
}

/**
  * Authentication
  */

// Fetch authkey
$authkey = isset($_COOKIE['authkey']) ?
  $_COOKIE['authkey'] : (isset($_GET['authkey']) ?
    $_GET['authkey'] : FALSE);

if (OPENLIST_REQUIRE_AUTHKEY) {
  if (!isset($authkeys[$authkey])) {
    header("HTTP/1.1 401 Unauthorized");
    exit('Invalid authkey');
  }

  // @TODO Implement IP check
  //
  //if (!check_ip($authkeys[$authkey])) {
  //  header("HTTP/1.1 401 Unauthorized");
  //  exit('IP not whitelisted');
  //}

  $GLOBALS['library_access'] = $authkeys[$authkey];
}
else {
  $GLOBALS['library_access'] = array($GLOBALS['library_code']);
}

// Full boot
require_once OPENLIST_CLASSES_PATH . '/Dev.php';
require_once OPENLIST_CLASSES_PATH . '/DB.php';
require_once OPENLIST_CLASSES_PATH . '/EventHandler.php';
require_once OPENLIST_CLASSES_PATH . '/Admin.php';
require_once OPENLIST_CLASSES_PATH . '/OpenList.php';
require_once OPENLIST_CLASSES_PATH . '/OpenListDecorator.php';

require_once MODULES_LIST_FILE;

define('OPENLIST_RESPONSE_UNKNOWN_ERROR', 'Unknown error');
define('OPENLIST_RESPONSE_SUCCESS', 'Success');
define('OPENLIST_RESPONSE_UNKNOWN_FUNCTION', 'Unknown function');

if (!isset($_SERVER['PATH_INFO'])) {
  $_SERVER['PATH_INFO'] = '';
}
$input = explode('/', $_SERVER['PATH_INFO']);

if (in_array($input[1], array('json', 'php', 'xml'))) {
  EventHandler::trigger('boot');

  $function = $input[2];
  $args = array_diff_key($_GET, array_flip(array('admin', 'library_code')));
  $args = array_merge($args, $_POST);

  $response = OPENLIST_RESPONSE_UNKNOWN_ERROR;
  $result = array('error' => OPENLIST_RESPONSE_UNKNOWN_ERROR);

  if (method_exists('OpenList', $function)) {
    $reflection_method = new \ReflectionMethod('OpenList', $function);
    $arglist = array();
    foreach ($reflection_method->getParameters() as $param) {
      if (isset($args[$param->name])) {
        $arglist[] = $args[$param->name];
      }
    }

    $old = new OpenListDecorator();
    $response = OPENLIST_RESPONSE_SUCCESS;
    $result = call_user_func_array(array($old, $function), $arglist);
  }
  else {
    $response = OPENLIST_RESPONSE_UNKNOWN_FUNCTION;
    $result = array('error' => OPENLIST_RESPONSE_UNKNOWN_FUNCTION);

    if (Module::getModule($input[2]) && method_exists($input[2], $input[3])) {
      $reflection_method = new \ReflectionMethod($input[2], $input[3]);
      $arglist = array();
      foreach ($reflection_method->getParameters() as $param) {
        if (isset($args[$param->name])) {
          $arglist[] = $args[$param->name];
        }
      }

      $old = new OpenListDecorator();
      $response = OPENLIST_RESPONSE_SUCCESS;
      $result = call_user_func_array(array(
        $old,
        'callModule',
      ), array(
        $input[2],
        $input[3],
        $arglist,
      ));
    }
  }

  switch ($response) {
    case OPENLIST_RESPONSE_UNKNOWN_ERROR:
    case OPENLIST_RESPONSE_UNKNOWN_FUNCTION:
      http_response_code(400);
      break;
  }

  switch ($input[1]) {
    case 'json':
      header('Content-Type: application/json;charset=utf-8');
      echo json_encode($result);
      break;

    case 'php':
      echo serialize($result);
      break;

    case 'xml':
      header('Content-Type: text/xml;charset=utf-8');

      $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><root></root>");
      array_to_xml($result, $xml);
      echo $xml->asXML();

      break;
  }

  EventHandler::trigger('end');
}
elseif (in_array($input[1], array('page'))) {
  $calls = EventHandler::trigger('links');
  $url = substr($_SERVER['SCRIPT_URL'], 6);

  foreach ($calls as $links) {
    if (isset($links[$url])) {
      require_once $links[$url];
    }
  }
}
else {
  if (count($_GET) === 0) {
    try {
      EventHandler::trigger('boot');

      if (!IS_LOCAL) {
        ini_set("soap.wsdl_cache_enabled", WSDL_CACHE_NONE);
        ini_set('soap.wsdl_cache_ttl', 0);

        if (filemtime(OPENLIST_CLASSES_PATH . '/OpenList.php') < filemtime(WSDL_LOCAL_PATH)) {
          $server = new Zend_Soap_Server(WSDL_LOCAL_PATH);
        }
        else {
          $server = new Zend_Soap_Server($_SERVER['SCRIPT_URI'] . '?wsdl&local');
        }

        $server->setClass('OpenListDecorator');

        $server->handle();
      }
      else {
        $old = new OpenListDecorator();
        var_dump(call_user_func_array(array($old, $GLOBALS['local'][0]), $GLOBALS['local'][1]));
      }

      EventHandler::trigger('end');
    }
    catch(Exception $e) {

    }
  }
  else {
    if (isset($_GET['cron'])) {
      var_dump(EventHandler::trigger('cron', array(explode(',', $_GET['cron']))));
    }

    if (isset($_GET['admin']) && $_GET['admin'] == OPENLIST_ADMIN_GET_PASSWORD) {
      $call_list = array();

      if (isset($_GET['install'])) {
        $call_list['install'] = explode(',', $_GET['install']);
      }
      if (isset($_GET['uninstall'])) {
        $call_list['uninstall'] = explode(',', $_GET['uninstall']);
      }

      foreach ($call_list as $method => $modules) {
        foreach ($modules as $module) {
          echo $module . '.' . $method . ': ' . Module::admin($module, $method);
        }
      }
    }
  }
}
