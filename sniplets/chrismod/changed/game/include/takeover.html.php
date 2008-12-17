<?php
function takeover_getContent($playerID, $caveID, $xCoord = NULL, $yCoord = NULL){
  global $config, $params, $resourceTypeList, $TAKEOVERMAXPOPULARITYPOINTS, $TAKEOVERMINRESOURCEVALUE;

  $template = @tmpl_open('templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/takeover.ihtml');


  if (getNumberOfCaves($playerID) >= $params->SESSION->user['takeover_max_caves']){
    tmpl_set($template, 'feedback', "Sie haben bereits die maximale Anzahl von " .
                                    $params->SESSION->user['takeover_max_caves'] .
                                    " Siedlung(en) erreicht.");
  } else {

    $beliebtheit  = $TAKEOVERMAXPOPULARITYPOINTS;
    $mindestgebot = $TAKEOVERMINRESOURCEVALUE;

    $resourcevalues = array();

    for ($i = 0; $i < sizeof($resourceTypeList); ++$i){
      array_push($resourcevalues, array('dbFieldName' => $resourceTypeList[$i]->dbFieldName,
                                        'name'        => $resourceTypeList[$i]->name,
                                        'value'       => $resourceTypeList[$i]->takeoverValue));
    }

    tmpl_set($template, 'TAKEOVER',
             array('beliebtheit'   => $beliebtheit,
                   'maxcaves'      => $params->SESSION->user['takeover_max_caves'],
                   'mindestgebot'  => $mindestgebot,
                   'targetXCoord'  => $params->POST->targetXCoord,
                   'targetYCoord'  => $params->POST->targetYCoord,
                   'RESOURCEVALUE' => $resourcevalues,
                   'HIDDEN'        => array('name' => 'modus',
                                            'value' => TAKEOVER_CHANGE)));

    for ($i = 0; $i < $beliebtheit; ++$i){
      tmpl_iterate($template, 'TAKEOVER/LEGENDE');
      tmpl_set($template, 'TAKEOVER/LEGENDE/status', $i);
      tmpl_set($template, 'TAKEOVER/LEGENDE', getStatusPic($i));
    }

    if (!($xCoord == "" || $yCoord == "" )){

      // neue Koordinaten

      // 1. pruefen, ob freie Hoehle
      // 2. neuen Eintrag in Cave_takeover (alten ueberschreiben)
      if (changeCaveIfReasonable($playerID, $xCoord, $yCoord)){
        tmpl_set($template, 'feedback', "Sie bieten nun f&uuml;r die Siedlung in (" . $xCoord . " | " . $yCoord . ").");
      } else {
        tmpl_set($template, 'feedback', "Sie k&ouml;nnen nicht f&uuml;r diese".
                                        " Siedlung (" . $xCoord . " | " . $yCoord . ")." .
                                        " bieten. W&auml;hlen sie eine freie Siedlung.");
      }
    }

    $takeover = new Takeover($playerID);

    if (!is_null($takeover)){

      tmpl_iterate($template, 'TAKEOVER/HIDDEN');
      tmpl_set($template, 'TAKEOVER/HIDDEN', array(array('name' => 'currentXCoord', 'value' => $takeover->xCoord),
                                                   array('name' => 'currentYCoord', 'value' => $takeover->yCoord)));

      tmpl_context($template, '/TAKEOVER/CHOSEN');
      tmpl_set($template, 'xCoord', $takeover->xCoord);
      tmpl_set($template, 'yCoord', $takeover->yCoord);
      tmpl_set($template, 'caveName', $takeover->caveName);
      tmpl_set($template, $takeover->getStatus());
      tmpl_set($template, 'bewegung', "?modus="          . MOVEMENT .
                                      "&caveID="         . $caveID .
                                      "&targetXCoord="   . $takeover->xCoord .
                                      "&targetYCoord="   . $takeover->yCoord .
                                      "&targetCaveName=" . unhtmlentities($takeover->caveName));

      if (sizeof($takeover->resources) != 0){
          tmpl_context($template, '/TAKEOVER/CHOSEN/RESOURCES/RESOURCE');
          tmpl_set($template, $takeover->resources);
          tmpl_set($template, '../SUM/sum', $takeover->resources_sum);
      }
      // this is a hack to prevent the bug in php-templates 1.1 while 1.2 is not working properly, this is needed
      else {
        tmpl_context($template, '/TAKEOVER/CHOSEN/RESOURCES/NONE');
        tmpl_set($template, 'none', 'keine');
      }


      if (sizeof($takeover->bidders) != 0){
        tmpl_context($template, '/TAKEOVER/CHOSEN/BIDDERS/BIDDER');
        tmpl_set($template, $takeover->bidders);
      }
      // this is a hack to prevent the bug in php-templates 1.1 while 1.2 is not working properly, this is needed
      else {
        tmpl_context($template, '/TAKEOVER/CHOSEN/BIDDERS/NOONE');
        tmpl_set($template, 'noone', 'niemand');
      }
    }
  }
  return tmpl_parse($template);
}

function takeover_changeConfirm($playerID, $xCoord = 0, $yCoord = 0, $currentXCoord = 0, $currentYCoord = 0){

  global $config, $params;

	$template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/dialog.ihtml");

  if ($xCoord == "" || $yCoord == "" ){
    tmpl_set($template, 'message', "Zum Wechseln m&uuml;ssen Sie schon sowohl die x- als auch die y-Koordinate eingeben<br>");

    tmpl_set($template, 'BUTTON/formname', 'confirm');
    tmpl_set($template, 'BUTTON/text', 'Zur&uuml;ck');
    tmpl_set($template, 'BUTTON/modus_name', 'modus');
    tmpl_set($template, 'BUTTON/modus_value', TAKEOVER);
  } else if ($currentXCoord == $xCoord && $currentYCoord == $yCoord){
    tmpl_set($template, 'message', "F&uuml;r diese Siedlung (" . $currentXCoord . "|" . $currentYCoord . ") bieten Sie bereits!<br>");

    tmpl_set($template, 'BUTTON/formname', 'confirm');
    tmpl_set($template, 'BUTTON/text', 'Zur&uuml;ck');
    tmpl_set($template, 'BUTTON/modus_name', 'modus');
    tmpl_set($template, 'BUTTON/modus_value', TAKEOVER);

  } else {

    tmpl_set($template, 'message', "Entscheiden Sie sich wirklich" .
      " daf&uuml;r, die Siedlung zu wechseln, f&uuml;r die Sie bisher" .
      " geboten haben? Sobald Sie gewechselt haben, wird der" .
      " Clanh&auml;uptling Ihrer ehemalig bebotenen Siedlung Ihre" .
      " Zuwendungen vergessen haben und Ihre Bem&uuml;hungen zunichte" .
      " machen...<br>Sie wollen für die Siedlung in (" .  $xCoord .
      " | " . $yCoord .  ") bieten.");

    tmpl_set($template, 'BUTTON/formname',   'confirm');
    tmpl_set($template, 'BUTTON/text',       'Siedlung in (' . $xCoord . ' | ' . $yCoord .  ') kolonisieren');
    tmpl_set($template, 'BUTTON/modus_name', 'modus');
    tmpl_set($template, 'BUTTON/modus_value', TAKEOVER);

    $arguments = array();
    array_push($arguments, array('arg_name' => 'xCoord',     'arg_value' => $xCoord));
    array_push($arguments, array('arg_name' => 'yCoord',     'arg_value' => $yCoord));
    tmpl_set($template, 'BUTTON/ARGUMENT', $arguments);

    tmpl_iterate($template, 'BUTTON');

    tmpl_set($template, 'BUTTON/formname', 'cancel');
    tmpl_set($template, 'BUTTON/text', 'Lieber nicht...');
    tmpl_set($template, 'BUTTON/modus_name', 'modus');
    tmpl_set($template, 'BUTTON/modus_value', TAKEOVER);
  }
  return tmpl_parse($template);
}
?>
