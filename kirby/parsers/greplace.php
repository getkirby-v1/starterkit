<?php

// global placeholders
function apply_global_placeholders($output) {
  global $placeholders;
  foreach($placeholders as $pname => $poptions) {
    if(isset($poptions["usage"]) && $poptions["usage"] == "global") {
      $output = str_replace($pname, $poptions["with"], $output);
    }
  }
  return $output;
}

?>
