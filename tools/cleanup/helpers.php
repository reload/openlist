<?php

// General preparations



// Functions

/**
 * Return named argument from CLI or $_GET
 * All args take the form [name]=[value]
 *
 * Examples:
 * index.php?pattern=xyz&someting=this
 *
 * php -f index-php pattern=xyz something=this
 */
function arg($name, $default = NULL) {
  global $argv;
  if (php_sapi_name() == "cli") {
    // Find matching argument in $argv
    foreach($argv as $argument) {
      $parts = explode("=",$argument);

      if (isset($parts[1]) && $parts[0] == $name) {
        return $parts[1];
      }
    }
    return $default;
  } else {
    // Find matching argument in $_GET
    if (isset($_GET[$name])) {
      return $_GET[$name];
    } else {
      return $default;
    }
  }
}

function out($msg) {
  if (php_sapi_name() == "cli") {
    if (is_string($msg)) {
      echo "$msg\n";
    }
    return;
  }

  if (is_object($msg)) {
    $msg = "<pre>" . print_r($msg, TRUE) . "</pre>";
  } else if (is_array($msg)) {
    $msg = "<pre>" . print_r($msg, TRUE) . "</pre>";
  }
  echo "<p>$msg</p>";
}


function output_table($result, $printout=TRUE, $truncate_output = TRUE, $max_rows=25) {

  if (php_sapi_name() == "cli") { return; }

  if ($truncate_output === TRUE) {
    $truncate_output = 25;
  }

  if (!$result) {
    $db = DB::$db;
    $o = "Error no result:" . $db->error;
    echo "<p><pre>$o</pre></p>";
    return;
  }

  $fields_num = $result->field_count;

  $o = "<table border='1'><thead>";
  // printing table headers
  for($i=0; $i<$fields_num; $i++)
  {
      $field = $result->fetch_field();
      $o .= "<th>{$field->name}</th>";
  }
  $o .= "</thead>\n";
  // printing table rows


  while($row = $result->fetch_row() ) {
    if ($max_rows-- <= 0) {
      break;
    }

    $o .= "<tr>";
      for($i=0; $i<$fields_num; $i++) {
        $cell = $row[$i];
        if ($truncate_output) {
          $cell = substr($cell,0,$truncate_output);
        }
        $o .= "<td>$cell</td>";
      }
      $o .= "</tr>\n";
  }
  $o .= "</table>";

  if ($printout) {
    echo $o;
  }
  return $o;
}
