<?php

function beginner_endProtectionConfirm($caveID) {
  global $config, $db, $params;

  // Show confirmation request

  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/dialog.ihtml");
   
  tmpl_set($template, 'message', "M&ouml;chten Sie den Anf&auml;ngerschutz ".
	   "in Siedlung ".$caveID.
	   " wirklich unwiderruflich aufgeben? Sie k&ouml;nnen dann ab ".
           "sofort angreifen, aber auch angegriffen werden!");   

  tmpl_set($template, 'BUTTON/formname', 'confirm');
  tmpl_set($template, 'BUTTON/text', 'Anf&auml;ngerschutz beenden');
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', CAVE_DETAIL);

  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'endProtectionConfirm');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', 1);
  tmpl_iterate($template, 'BUTTON/ARGUMENT');
  
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'caveID');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', $caveID);
  
  tmpl_iterate($template, 'BUTTON'); 

  tmpl_set($template, 'BUTTON/formname', 'cancel');
  tmpl_set($template, 'BUTTON/text', 'Abbrechen');
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', CAVE_DETAIL);

  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'caveID');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', $caveID);
    
  return tmpl_parse($template);
}

