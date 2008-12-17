<?php

function cave_giveUpConfirm($caveID) {
  global $config, $db, $params;

  // Show confirmation request

  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/dialog.ihtml");
   
  tmpl_set($template, 'message',
     "M&ouml;chten Sie die Siedlung {$caveID} wirklich aufgeben? Sie ".
     "verlieren die Kontrolle &uuml;ber alle Rohstoffe und alle ".
     "Einheiten, die sich hier befinden!");   

  tmpl_set($template, 'BUTTON/formname', 'confirm');
  tmpl_set($template, 'BUTTON/text', 'Aufgeben');
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', CAVE_DETAIL);

  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'caveGiveUpConfirm');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', 1);
  tmpl_iterate($template, 'BUTTON/ARGUMENT');
  
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'giveUpCaveID');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', $caveID);
  
  tmpl_iterate($template, 'BUTTON'); 

  tmpl_set($template, 'BUTTON/formname', 'cancel');
  tmpl_set($template, 'BUTTON/text', 'Abbrechen');
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', CAVE_DETAIL);
    
  return tmpl_parse($template);
}
