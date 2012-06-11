<?php

// global placeholders
function apply_global_placeholders($output) {
  global $placeholders, $currenttemplate;
  foreach($placeholders as $pname => $poptions) {
    if(isset($poptions["usage"]) && $poptions["usage"] == "global" && (!isset($poptions["templates"]) || isset($poptions["templates"][$currenttemplate["existing"]]) || isset($poptions["templates"][$currenttemplate["virtual"]]))) {
      $output = str_replace($pname, $poptions["with"], $output);
    }
  }
  return $output;
}

?>
