<?php
	
$MAX_RESOURCE = 7;
$MAX_BUILDING = 25;
$MAX_SCIENCE = 40;
$MAX_UNIT = 61;
$MAX_DEFENSESYSTEM = 26;
$TAKEOVERMAXPOPULARITYPOINTS = 4;
$TAKEOVERMINRESOURCEVALUE = 300;
$WATCHTOWERVISIONRANGE = "4+[D10.ACT]";
$EXPOSEINVISIBLE = "0.0";
$WONDERRESISTANCE = "[E28.ACT]";
$FUELRESOURCEID = 1;
$MOVEMENTCOSTCONSTANT = "GREATEST(1/(1+ABS([E24.ACT])),1+[E24.ACT])/30";
$MOVEMENTSPEEDCONSTANT = "5*GREATEST(1/(1+ABS([E25.ACT])),1+[E25.ACT])";

	
/********************** Resourcetypes *********************/
class Resource {

  var $resourceID;
  var $name;
  var $dbFieldName;
  var $maxLevel;
  var $resProdFunction;

  var $ratingValue     = 0;
  var $takeoverValue   = 0;
  var $saveStorage     = 0;
  var $nodocumentation = 0;

  function Resource($resourceID, $name, $dbFieldName, $maxLevel, $resProdFunction){
    $this->resourceID      = $resourceID;
    $this->name            = $name;
    $this->dbFieldName     = $dbFieldName;
    $this->maxLevel        = $maxLevel;
    $this->resProdFunction = $resProdFunction;
  }
}

function init_resources(){
  global $resourceTypeList;

  // Bev&ouml;lkerung
  $tmp = new Resource(0,
                      "Bev&ouml;lkerung",
                      "resource_population",
                      "(POW(0.9+[B0.ACT]*0.015,[B5.ACT])-1)/(0.9+[B0.ACT]*0.015-1)*10+5",
                      "SIGN([R1.ACT]+(10*[B2.ACT]+4*[B7.ACT]+20*[D3.ACT])*(1+0.2*[S16.ACT])*(1+[E3.ACT])+[E2.ACT]-([R0.ACT]+[B1.ACT]+[B2.ACT]+[B3.ACT]+[B7.ACT]+[B14.ACT]+[B15.ACT]+2*([B4.ACT]+[B8.ACT]+[B11.ACT]+[B18.ACT]+[B19.ACT]+[B20.ACT]+[B23.ACT])+4*([B6.ACT]+[B10.ACT]+[B12.ACT]+[B13.ACT])+([U1.ACT]+[U2.ACT]+[U3.ACT]+[U4.ACT]+[U5.ACT]+[U6.ACT]+[U7.ACT]+[U8.ACT]+[U9.ACT]+[U10.ACT]+[U11.ACT])/50+([U12.ACT]+[U13.ACT]+[U14.ACT]+[U15.ACT]+[U16.ACT]+[U17.ACT]+[U18.ACT]+[U19.ACT]+[U20.ACT]+[U21.ACT]+[U22.ACT]+[U23.ACT]+[U24.ACT]+[U25.ACT]+[U26.ACT]+[U27.ACT]+[U28.ACT]+[U29.ACT]+[U30.ACT]+[U31.ACT]+[U32.ACT]+[U33.ACT]+[U34.ACT]+[U35.ACT]+[U36.ACT]+[U37.ACT]+[U38.ACT])/25+([U39.ACT]+[U40.ACT]+[U41.ACT]+[U42.ACT]+[U43.ACT]+[U44.ACT]+[U45.ACT]+[U46.ACT]+[U47.ACT]+[U48.ACT]+[U49.ACT]+[U50.ACT]+[U51.ACT]+[U52.ACT]+[U53.ACT]+[U54.ACT]+[U55.ACT]+[U56.ACT]+[U57.ACT]+[U58.ACT]+[U59.ACT]+[U60.ACT])/50*4))*GREATEST([R0.ACT]*(0.02+0.02*[B0.ACT]),1)*(1+[E1.ACT])+[E0.ACT]");

  $tmp->saveStorage     = 25;
  $tmp->ratingValue     = 15.0;
  
  $resourceTypeList[0]  = $tmp;

  // Nahrung
  $tmp = new Resource(1,
                      "Nahrung",
                      "resource_food",
                      "3000+5000*[B9.ACT]+4000*GREATEST(0,[B9.ACT]-5)*([B9.ACT]-5)",
                      "(10*[B2.ACT]+4*[B7.ACT]+20*[D3.ACT])*(1+0.2*[S16.ACT])*(1+[E3.ACT])+[E2.ACT]-([R0.ACT]+[B1.ACT]+[B2.ACT]+[B3.ACT]+[B7.ACT]+[B14.ACT]+[B15.ACT]+2*([B4.ACT]+[B8.ACT]+[B11.ACT]+[B18.ACT]+[B19.ACT]+[B20.ACT]+[B23.ACT])+4*([B6.ACT]+[B10.ACT]+[B12.ACT]+[B13.ACT])+([U1.ACT]+[U2.ACT]+[U3.ACT]+[U4.ACT]+[U5.ACT]+[U6.ACT]+[U7.ACT]+[U8.ACT]+[U9.ACT]+[U10.ACT]+[U11.ACT])/50+([U12.ACT]+[U13.ACT]+[U14.ACT]+[U15.ACT]+[U16.ACT]+[U17.ACT]+[U18.ACT]+[U19.ACT]+[U20.ACT]+[U21.ACT]+[U22.ACT]+[U23.ACT]+[U24.ACT]+[U25.ACT]+[U26.ACT]+[U27.ACT]+[U28.ACT]+[U29.ACT]+[U30.ACT]+[U31.ACT]+[U32.ACT]+[U33.ACT]+[U34.ACT]+[U35.ACT]+[U36.ACT]+[U37.ACT]+[U38.ACT])/25+([U39.ACT]+[U40.ACT]+[U41.ACT]+[U42.ACT]+[U43.ACT]+[U44.ACT]+[U45.ACT]+[U46.ACT]+[U47.ACT]+[U48.ACT]+[U49.ACT]+[U50.ACT]+[U51.ACT]+[U52.ACT]+[U53.ACT]+[U54.ACT]+[U55.ACT]+[U56.ACT]+[U57.ACT]+[U58.ACT]+[U59.ACT]+[U60.ACT])/50*4)");

  $tmp->saveStorage     = 150;
  $tmp->ratingValue     = 0.25;
  $tmp->takeoverValue   = 0.25;
  
  $resourceTypeList[1]  = $tmp;

  // Holz
  $tmp = new Resource(2,
                      "Holz",
                      "resource_wood",
                      "3000+2000*[B9.ACT]",
                      "(1.5*[B1.ACT]+0.5*[B7.ACT]+3*[D3.ACT])*(1+[E5.ACT])+[E4.ACT]");

  $tmp->saveStorage     = 150;
  $tmp->ratingValue     = 2.0;
  $tmp->takeoverValue   = 2.0;
  
  $resourceTypeList[2]  = $tmp;

  // Steine
  $tmp = new Resource(3,
                      "Steine",
                      "resource_stone",
                      "3000+2000*[B9.ACT]",
                      "(1.5*[B3.ACT]+0.5*[B7.ACT]+3*[D3.ACT])*(1+[E7.ACT])+[E6.ACT]");

  $tmp->saveStorage     = 150;
  $tmp->ratingValue     = 1.5;
  $tmp->takeoverValue   = 1.5;
  
  $resourceTypeList[3]  = $tmp;

  // Metall
  $tmp = new Resource(4,
                      "Metall",
                      "resource_metal",
                      "3000+2000*[B9.ACT]",
                      "[B14.ACT]*(1+[E9.ACT])+[E8.ACT]");

  $tmp->ratingValue     = 5.0;
  $tmp->takeoverValue   = 5.0;
  
  $resourceTypeList[4]  = $tmp;

  // Schwefel
  $tmp = new Resource(5,
                      "Schwefel",
                      "resource_sulfur",
                      "3000+2000*[B9.ACT]",
                      "[B15.ACT]*(1+[E11.ACT])+[E10.ACT]");

  $tmp->ratingValue     = 8.0;
  $tmp->takeoverValue   = 8.0;
  
  $resourceTypeList[5]  = $tmp;

  // G&ouml;ttliche Gunst
  $tmp = new Resource(6,
                      "G&ouml;ttliche Gunst",
                      "resource_religion",
                      "1500",
                      "GREATEST(GREATEST(SIGN([S14.ACT])*([B16.ACT]+[D1.ACT]),SIGN([S15.ACT])*([B17.ACT]+[D2.ACT])),GREATEST(SIGN([S29.ACT])*([B24.ACT]+[D3.ACT]),SIGN([S31.ACT])*([D4.ACT])))*(1+[E13.ACT])+[E12.ACT]");

  $tmp->saveStorage     = 250;
  $tmp->ratingValue     = 8.0;
  
  $resourceTypeList[6]  = $tmp;

}

	
/********************** Buildingtypes *********************/
class Building {
  var $buildingID;
  var $name;
  var $description;
  var $dbFieldName;
  var $position;
  var $maxLevel;
  var $productionTimeFunction;

  var $ratingValue             = 0;
  var $resourceProductionCost  = array();
  var $unitProductionCost      = array();
  var $buildingProductionCost  = array();
  var $externalProductionCost  = array();
  var $buildingDepList         = array();
  var $maxBuildingDepList      = array();
  var $defenseSystemDepList    = array();
  var $maxDefenseSystemDepList = array();
  var $resourceDepList         = array();
  var $maxResourceDepList      = array();
  var $scienceDepList          = array();
  var $maxScienceDepList       = array();
  var $unitDepList             = array();
  var $maxUnitDepList          = array();
  var $nodocumentation         = 0;

  function Building($buildingID, $name, $description, $dbFieldName, $position, $maxLevel, $productionTimeFunction){
    $this->buildingID             = $buildingID;
    $this->name                   = $name;
    $this->description            = $description;
    $this->dbFieldName            = $dbFieldName;
    $this->position               = $position;
    $this->maxLevel               = $maxLevel ;
    $this->productionTimeFunction = $productionTimeFunction;
  }
}

function init_buildings(){

  global $buildingTypeList;
  
  // Feuerstelle
  $tmp = new Building(0,
                      "Feuerstelle",
                      "<p>Die Bev&ouml;lkerung w&auml;chst mit jedem Tick um zwei Prozent. Erst eine Feuerstelle mit romantischer Atmosph&auml;re regt die Bev&ouml;lkerung an, sich verst&auml;rkt fortzupflanzen. Au&szlig;erdem steigt die Bev&ouml;lkerungsobergrenze mit jeder Stufe der Feuerstelle leicht an.</p><p>Pro Ausbaustufe werden weitere zwei Prozent mehr erschaffen.</p>",
                      "building_fireplace",
                      0,
                      "10",
                      "(1440+240*POW([B0.ACT],2))*20/(20+[R0.ACT])");

  $tmp->ratingValue = 2;
  $tmp->resourceProductionCost = array(2 => "(20+8*[B0.ACT])*POW(2,GREATEST(0,[B0.ACT]-4))", 3 => "(70+40*POW([B0.ACT]+.1,1.8))*POW(2,GREATEST(0,[B0.ACT]-3))");
  
  $tmp->scienceDepList    = array(6 => "1");
  $tmp->maxScienceDepList = array(6 => "-1");
  
  $buildingTypeList[0] = $tmp;

  // Holzf&auml;ller
  $tmp = new Building(1,
                      "Holzf&auml;ller",
                      "<p>Um Holz f&uuml;r H&ouml;hlenausbauten zu erhalten, m&uuml;ssen ausreichend Holzf&auml;ller vorhanden sein. Jede Ausbaustufe bringt eineinhalb Holzeinheiten mehr pro Tick. Holz ist zum Beispiel f&uuml;r die Kn&uuml;ppelproduktion und f&uuml;r den Bau von Feuerstellen und vielen anderen H&ouml;hlenausbauten zwingend erforderlich. Jeder Holzf&auml;ller verbraucht bei seiner gef&auml;hrlichen T&auml;tigkeit pro Tick eine Nahrungseinheit.</p><p>Die Ausbildung wird sowohl durch mehr Ausbildungsst&auml;tten als auch durch eine gr&ouml;&szlig;ere Bev&ouml;lkerung beschleunigt.</p>",
                      "building_wood_shack",
                      1,
                      "50",
                      "720/(GREATEST(1,[B8.ACT])+[R0.ACT]/100)");

  $tmp->ratingValue = 2;
  $tmp->resourceProductionCost = array(0 => "1", 2 => "(4+4*[B1.ACT])*POW(1.5,GREATEST(0,[B1.ACT]-19))", 3 => "(20+4*POW([B1.ACT]+0.1,1.5))*POW(1.5,GREATEST(0,[B1.ACT]-19))");
  
  $tmp->buildingDepList    = array(8 => "1");
  $tmp->maxBuildingDepList = array(8 => "-1");
  
  $tmp->scienceDepList    = array(5 => "1");
  $tmp->maxScienceDepList = array(5 => "-1");
  
  $buildingTypeList[1] = $tmp;

  // J&auml;ger
  $tmp = new Building(2,
                      "J&auml;ger",
                      "<p>Die J&auml;ger k&ouml;nnen gr&ouml;&szlig;ere Tiere erlegen und sind daher in der Nahrungsproduktion wesentlich effektiver als die einfachen Sammler. Sie produzieren pro Stufe 10 Nahrungseinheiten, ein J&auml;ger ern&auml;hrt also etwa eine Schlafst&auml;tte. Damit auch die J&auml;ger selbst bei guter Laune bleiben, ben&ouml;tigen sie pro Tick eine Nahrungseinheit.</p><p>Die Ausbildung wird sowohl durch mehr Ausbildungsst&auml;tten als auch durch eine gr&ouml;&szlig;ere Bev&ouml;lkerung beschleunigt.</p>",
                      "building_hunters_shack",
                      2,
                      "50",
                      "720/(GREATEST(1,[B8.ACT])+[R0.ACT]/100)");

  $tmp->ratingValue = 2;
  $tmp->resourceProductionCost = array(0 => "1", 2 => "(20+4*POW([B2.ACT]+0.1,1.5))*POW(1.5,GREATEST(0,[B2.ACT]-26))", 3 => "(20+4*POW([B2.ACT]+0.1,1.5))*POW(1.5,GREATEST(0,[B2.ACT]-26))");
  
  $tmp->buildingDepList    = array(8 => "1");
  $tmp->maxBuildingDepList = array(8 => "-1");
  
  $tmp->scienceDepList    = array(4 => "1");
  $tmp->maxScienceDepList = array(4 => "-1");
  
  $buildingTypeList[2] = $tmp;

  // Steinbrecher
  $tmp = new Building(3,
                      "Steinbrecher",
                      "<p>Steinbrecher brauchen ebenso wie J&auml;ger und Holzf&auml;ller einen Arbeitsplatz. Jede Stufe eines Steinbrechers produziert eineinhalb Steineinheiten zus&auml;tzlich. Die Steine werden f&uuml;r den Ausbau von H&ouml;hleneinrichtungen und zur Ausbildung der Kn&uuml;ppelkrieger, Steinschleuderer und vieler anderer Einheiten ben&ouml;tigt. Um gute Arbeit zu leisten, wird pro Stufe jeweils eine Nahrungseinheit verbraucht.</p><p>Die Ausbildung wird sowohl durch mehr Ausbildungsst&auml;tten als auch durch eine gr&ouml;&szlig;ere Bev&ouml;lkerung beschleunigt.</p>",
                      "building_quarry",
                      3,
                      "50",
                      "720/(GREATEST(1,[B8.ACT])+[R0.ACT]/100)");

  $tmp->ratingValue = 2;
  $tmp->resourceProductionCost = array(0 => "1", 2 => "(20+4*POW([B3.ACT]+0.1,1.5))*POW(1.5,GREATEST(0,[B3.ACT]-19))", 3 => "(4+4*[B3.ACT])*POW(1.5,GREATEST(0,[B3.ACT]-19))");
  
  $tmp->buildingDepList    = array(8 => "1");
  $tmp->maxBuildingDepList = array(8 => "-1");
  
  $tmp->scienceDepList    = array(1 => "1");
  $tmp->maxScienceDepList = array(1 => "-1");
  
  $buildingTypeList[3] = $tmp;

  // Trainingsgel&auml;nde
  $tmp = new Building(4,
                      "Trainingsgel&auml;nde",
                      "<p>Hier werden kr&auml;ftige Krieger ausgebildet und trainiert, die deinen Stamm nicht nur bewachen sollen, sondern auch andere St&auml;mme angreifen, ausrauben oder besch&uuml;tzen k&ouml;nnen. Au&szlig;erdem k&ouml;nnen die Krieger auch zum Rohstofftransport eingesetzt werden. Damit die Ausbilder auch ausreichende H&auml;rte an den Tag legen k&ouml;nnen, brauchen sie pro Tick und Ausbaustufe zwei Nahrungseinheiten.</p>",
                      "building_barrack",
                      4,
                      "10",
                      "(1440+720*POW([B4.ACT]+0.1,1.5))*(20/(20+[R0.ACT]))");

  $tmp->ratingValue = 4;
  $tmp->resourceProductionCost = array(0 => "2", 1 => "40+(100*POW([B4.ACT]+0.1,1.4))", 2 => "40+[B4.ACT]*300", 3 => "60+POW([B4.ACT]+0.1,1.4)*200");
  
  $buildingTypeList[4] = $tmp;

  // Schlafst&auml;tte
  $tmp = new Building(5,
                      "Schlafst&auml;tte",
                      "<p>W&auml;hrend eine einfache H&ouml;hle nur f&uuml;nf wenig angenehme Schlafpl&auml;tze bietet, sind diese behauenen Schlafst&auml;tten wesentlich gem&uuml;tlicher. Die kuschelige Atmosph&auml;re erh&ouml;ht die Bev&ouml;lkerungsobergrenze pro Ausbaustufe um weitere zehn Bewohner, allerdings mit leicht abnehmender Tendenz bei weiterem Ausbau.</p><p>Sind mehr Bewohner als Schlafst&auml;tten vorhanden, nimmt die Bev&ouml;lkerung beim n&auml;chsten Tick ab.</p>",
                      "building_sleeping_place",
                      5,
                      "50",
                      "(240+180*[B5.ACT])*(20/(20+[R0.ACT]))");

  $tmp->ratingValue = 2;
  $tmp->resourceProductionCost = array(2 => "(3+15*POW([B5.ACT]+0.1,1.2))*POW(1.5,GREATEST(0,[B5.ACT]-35))", 3 => "20*POW(1.15,GREATEST(0,[B5.ACT]))");
  
  $buildingTypeList[5] = $tmp;

  // Kn&uuml;ppelmanufaktur
  $tmp = new Building(6,
                      "Kn&uuml;ppelmanufaktur",
                      "<p>Hier werden die schweren Holzkn&uuml;ppel f&uuml;r die Kn&uuml;ppelkrieger hergestellt. Bei der sehr anstrengenden T&auml;tigkeit werden pro Tick und Ausbaustufe drei Nahrungseinheiten verbraucht.</p><p>Bei einer h&ouml;heren Ausbaustufe k&ouml;nnen die Kn&uuml;ppelkrieger schneller ausgebildet werden.</p><p>Die Kn&uuml;ppelmanufaktur kann nicht in einer H&ouml;hle gebaut werden, in der bereits ein Bestiarium steht.</p>",
                      "building_club_factory",
                      6,
                      "10",
                      "(2880+1440*POW([B6.ACT]+0.1,1.5))*(20/(1+[R0.ACT]))");

  $tmp->ratingValue = 5;
  $tmp->resourceProductionCost = array(0 => "4", 1 => "160+160*[B6.ACT]", 2 => "600+600*[B6.ACT]", 3 => "800+800*[B6.ACT]*POW(1.1,[B6.ACT]+1)", 4 => "[B6.ACT]*8*POW([B6.ACT]+0.1,1.4)");
  
  $tmp->buildingDepList    = array(7 => "6", 10 => "0", 12 => "0", 13 => "0", 22 => "1");
  $tmp->maxBuildingDepList = array(7 => "-1", 10 => "0", 12 => "0", 13 => "0", 22 => "-1");
  
  $tmp->scienceDepList    = array(2 => "1");
  $tmp->maxScienceDepList = array(2 => "-1");
  
  $buildingTypeList[6] = $tmp;

  // Sammler
  $tmp = new Building(7,
                      "Sammler",
                      "<p>Ein einfacher Sammler sammelt Holz, Steine und Nahrung (je 0.5 Steine und Holz, 4 Nahrung pro Tick). Sammler sind auch f&uuml;r die Ausbildung von Kn&uuml;ppelmanufakturen und Facharbeiterausbildungsst&auml;tten zwingend erforderlich. Zur Ausbildung werden einige Steine, etwas Holz und Beispiele f&uuml;r genie&szlig;bare Beeren und W&uuml;rmer zum &Uuml;ben ben&ouml;tigt. Jeder Sammler verbraucht pro Tick selbst eine Nahrungseinheit.</p><p>Die Ausbildung der Sammler kann nicht beschleunigt werden.</p>",
                      "building_gatherer",
                      7,
                      "30",
                      "150+10*[B7.ACT]");

  $tmp->ratingValue = 1;
  $tmp->resourceProductionCost = array(0 => "1", 2 => "(5+2*[B7.ACT])*POW(2,GREATEST(0,[B7.ACT]-18))", 3 => "(5+2*[B7.ACT])*POW(2,GREATEST(0,[B7.ACT]-18))");
  
  $buildingTypeList[7] = $tmp;

  // Facharbeiterausbildungsst&auml;tte
  $tmp = new Building(8,
                      "Facharbeiterausbildungsst&auml;tte",
                      "<p>Um Arbeiter auszubilden, die sich auf eine bestimmte T&auml;tigkeit spezialisieren k&ouml;nnen (J&auml;ger, Holzf&auml;ller, Projektilbearbeiter, Metallschmelzer etc.), wird die Facharbeiterausbildungsst&auml;tte ben&ouml;tigt. Dieses Geb&auml;ude ist ebenfalls Voraussetzung f&uuml;r den Sch&ouml;pfungsgelehrten des Uga-Spielers.</p><p>Eine h&ouml;here Stufe beschleunigt dabei die Ausbildung der verschiedenen Facharbeiter.</p>",
                      "building_trainingcenter",
                      8,
                      "10",
                      "(1440+720*POW([B8.ACT]+0.1,1.5))*(20/(20+[R0.ACT]))");

  $tmp->ratingValue = 3;
  $tmp->resourceProductionCost = array(0 => "2", 1 => "20+60*[B8.ACT]", 2 => "60+150*POW([B8.ACT]+0.1,1.4)", 3 => "40+80*[B8.ACT]");
  
  $tmp->buildingDepList    = array(7 => "6");
  $tmp->maxBuildingDepList = array(7 => "-1");
  
  $tmp->scienceDepList    = array(2 => "1");
  $tmp->maxScienceDepList = array(2 => "-1");
  
  $buildingTypeList[8] = $tmp;

  // Lagerh&ouml;hle
  $tmp = new Building(9,
                      "Lagerh&ouml;hle",
                      "<p>Eine einfache H&ouml;hle kann nur maximal 3000 Einheiten von jeder Ressource aufnehmen. Durch den Bau von Lagerh&ouml;hlen wird die Lagerkapazit&auml;t f&uuml;r alle Ressourcen in deiner H&ouml;hle um 2000, bzw. im Falle von Nahrung um mindestens 5000 (ab Stufe 7 noch deutlich mehr), pro Stufe vergr&ouml;&szlig;ert.</p>",
                      "building_storehouse",
                      9,
                      "100",
                      "(1440+720*POW([B9.ACT]+0.1,1.5))*(20/(20+[R0.ACT]))");

  $tmp->ratingValue = 3;
  $tmp->resourceProductionCost = array(2 => "100+80*POW([B9.ACT]+0.1,1.5)", 3 => "50+150*[B9.ACT]", 4 => "LEAST(30*POW(GREATEST(0,[B9.ACT]-2),3),1000+2000*[B9.ACT]*[B9.ACT]/([B9.ACT]+2))", 5 => "LEAST(15*POW(GREATEST(0,[B9.ACT]-2),3),500+1000*[B9.ACT]*[B9.ACT]/([B9.ACT]+2))");
  
  $tmp->buildingDepList    = array(5 => "3");
  $tmp->maxBuildingDepList = array(5 => "-1");
  
  $buildingTypeList[9] = $tmp;

  // Schildkr&ouml;tenschildausgr&auml;ber
  $tmp = new Building(10,
                      "Schildkr&ouml;tenschildausgr&auml;ber",
                      "<p>Dieser Facharbeiter gr&auml;bt die Schilder der Riesenschildkr&ouml;ten aus, die vor allem f&uuml;r die Ausr&uuml;stung der Schildkr&ouml;tenschildtr&auml;ger notwendig sind. Wenn mehr Ausgr&auml;ber vorhanden sind, k&ouml;nnen die Schildtr&auml;ger schneller ausgebildet und ausger&uuml;stet werden.</p><p>Der Schildkr&ouml;tenschildausgr&auml;ber kann nicht in einer H&ouml;hle t&auml;tig werden, in der es ein Bestiarium oder ein Konstruktionszentrum gibt. Diese verbrauchen zu viel der geraden Fl&auml;che, die von den Schildkr&ouml;ten als Brutpl&auml;tze verwendet werden.</p>",
                      "building_turtle",
                      10,
                      "10",
                      "(2880+1440*POW([B10.ACT]+0.1,1.5))*(20/(1+[R0.ACT]))");

  $tmp->ratingValue = 5;
  $tmp->resourceProductionCost = array(0 => "4", 1 => "160+160*[B10.ACT]", 2 => "600+600*[B10.ACT]", 3 => "800+800*[B10.ACT]*POW(1.1,[B10.ACT]+1)", 4 => "[B10.ACT]*8*POW([B10.ACT]+0.1,1.4)");
  
  $tmp->buildingDepList    = array(6 => "0", 12 => "0", 13 => "0", 22 => "3");
  $tmp->maxBuildingDepList = array(6 => "0", 12 => "0", 13 => "0", 22 => "-1");
  
  $tmp->scienceDepList    = array(5 => "2");
  $tmp->maxScienceDepList = array(5 => "-1");
  
  $buildingTypeList[10] = $tmp;

  // Projektilbearbeiter
  $tmp = new Building(11,
                      "Projektilbearbeiter",
                      "<p>In dieser Arbeitsst&auml;tte werden einfache Holzpfeile, Speere und andere Geschosse f&uuml;r die verschiedenen Fernkampfeinheiten hergestellt. Eine h&ouml;here Ausbaustufe f&uuml;hrt dabei zu einer schnelleren Produktion dieser Waffen.</p><p>Leider ist der Betrieb einer St&auml;tte zur Herstellung von Projektilen in einer H&ouml;hle, die mit einer Rennstrecke ausger&uuml;stet ist, durch die Sicherheitsbeh&ouml;rden verboten worden. Fr&uuml;her kam es immer wieder vor, da&szlig; die frisch gebackenen Eigent&uuml;mer neuer Projektile oftmals der Versuchung, ihre Sch&auml;tzchen direkt an den schwer zu treffenden L&auml;ufern zu testen, nicht widerstehen konnten, was bei hinreichend gro&szlig;er Treffsicherheit oftmals eine nicht zu tolerierende Dezimierung der Stammesbev&ouml;lkerung zur Folge hatte.</p>",
                      "building_projectile",
                      11,
                      "10",
                      "(1440+720*POW([B11.ACT]+0.1,1.5))/(GREATEST(1,[B8.ACT]-1)+[R0.ACT]/60)");

  $tmp->ratingValue = 4;
  $tmp->resourceProductionCost = array(0 => "2", 1 => "60+60*[B11.ACT]", 2 => "250+245*[B11.ACT]*POW(1.1,[B11.ACT]+1)", 3 => "100+200*[B11.ACT]");
  
  $tmp->buildingDepList    = array(8 => "2", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(8 => "-1", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(3 => "3", 5 => "5", 8 => "1", 13 => "6");
  $tmp->maxScienceDepList = array(3 => "-1", 5 => "-1", 8 => "-1", 13 => "-1");
  
  $buildingTypeList[11] = $tmp;

  // Konstruktionszentrum
  $tmp = new Building(12,
                      "Konstruktionszentrum",
                      "<p>Diese Einrichtung ist f&uuml;r alle Erweiterungen, Kampfger&auml;te (z.B. Katapulte) und Verteidigungsanlagen notwendig, die zum Bau technische Fertigkeiten verlangen. Achtung: Das Konstruktionszentrum kann nicht in einer H&ouml;hle gebaut werden, in der schon ein Bestiarium oder ein Schildkr&ouml;tenschildausgr&auml;ber vorhanden sind.</p>",
                      "building_constructioncenter",
                      12,
                      "10",
                      "(2880+1440*POW([B12.ACT]+0.1,1.5))*(20/(1+[R0.ACT]))");

  $tmp->ratingValue = 5;
  $tmp->resourceProductionCost = array(0 => "4", 1 => "160+160*[B12.ACT]", 2 => "600+600*[B12.ACT]", 3 => "800+800*[B12.ACT]*POW(1.1,[B12.ACT]+1)", 4 => "[B12.ACT]*8*POW([B12.ACT]+0.1,1.4)");
  
  $tmp->buildingDepList    = array(6 => "0", 10 => "0", 13 => "0", 22 => "3");
  $tmp->maxBuildingDepList = array(6 => "0", 10 => "0", 13 => "0", 22 => "-1");
  
  $tmp->scienceDepList    = array(11 => "1");
  $tmp->maxScienceDepList = array(11 => "-1");
  
  $buildingTypeList[12] = $tmp;

  // Bestiarium
  $tmp = new Building(13,
                      "Bestiarium",
                      "<p>Das Bestiarium ist Voraussetzung f&uuml;r die Z&auml;hmung und Ausbildung aller tierischen Kampfeinheiten. Achtung: Diese Einrichtung kann nicht in einer H&ouml;hle gebaut werden, in der schon ein Konstruktionszentrum, eine Kn&uuml;ppelmanufaktur oder ein Schildkr&ouml;tenschildausgr&auml;ber errichtet worden ist. Gegebenenfalls k&ouml;nnen die anderen Geb&auml;ude abgerissen werden, um den Bau eines Bestiariums zu erm&ouml;glichen.</p>",
                      "building_beast",
                      13,
                      "10",
                      "(2880+1440*POW([B13.ACT]+0.1,1.5))*(20/(1+[R0.ACT]))");

  $tmp->ratingValue = 5;
  $tmp->resourceProductionCost = array(0 => "4", 1 => "160+160*[B13.ACT]", 2 => "600+600*[B13.ACT]", 3 => "800+800*[B13.ACT]*POW(1.1,[B13.ACT]+1)", 4 => "[B13.ACT]*8*POW([B13.ACT]+0.1,1.4)");
  
  $tmp->buildingDepList    = array(2 => "12", 6 => "0", 10 => "0", 12 => "0", 22 => "2");
  $tmp->maxBuildingDepList = array(2 => "-1", 6 => "0", 10 => "0", 12 => "0", 22 => "-1");
  
  $tmp->scienceDepList    = array(6 => "3", 10 => "2");
  $tmp->maxScienceDepList = array(6 => "-1", 10 => "-1");
  
  $buildingTypeList[13] = $tmp;

  // Metallschmelzer
  $tmp = new Building(14,
                      "Metallschmelzer",
                      "<p>Der Metallschmelzer ist in der Lage, einfaches Metall-Erz aufzusp&uuml;ren und aus diesem das wertvolle Metall zu gewinnen. Allerdings sind erst umfangreiche Forschungsreihen mit dem Feuer notwendig, bis diese hohe Kunst erlernt ist. Jeder Metallschmelzer produziert jeweils eine Einheit Metall pro Tick.</p>",
                      "building_melting",
                      14,
                      "10",
                      "1440/GREATEST(1,([B8.ACT]-5))");

  $tmp->ratingValue = 3;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "30", 2 => "150+150*[B14.ACT]*POW(1.1,[B14.ACT])", 3 => "100+100*[B14.ACT]");
  
  $tmp->buildingDepList    = array(0 => "4", 8 => "6");
  $tmp->maxBuildingDepList = array(0 => "-1", 8 => "-1");
  
  $tmp->scienceDepList    = array(1 => "6", 9 => "1");
  $tmp->maxScienceDepList = array(1 => "-1", 9 => "-1");
  
  $buildingTypeList[14] = $tmp;

  // Schwefelgewinner
  $tmp = new Building(15,
                      "Schwefelgewinner",
                      "<p>Anders als Metall kann der Schwefel direkt gesammelt und mu&szlig; nur gereinigt werden. Allerdings sind Schwefelvorkommen selten und nur schwer aufzufinden, daher ben&ouml;tigen die Schwefelgewinner eine gute und lange Ausbildung. Jeder Schwefelgewinner produziert eine Einheit Schwefel pro Tick.</p>",
                      "building_sulfurcollector",
                      15,
                      "6",
                      "2580*4/POW(GREATEST(2,[B8.ACT]-5),2)");

  $tmp->ratingValue = 3;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "60", 2 => "150+150*([B15.ACT]+4)*POW(1.1,[B15.ACT]+4)", 3 => "100+100*([B15.ACT]+4)");
  
  $tmp->buildingDepList    = array(8 => "7");
  $tmp->maxBuildingDepList = array(8 => "-1");
  
  $tmp->scienceDepList    = array(1 => "8", 12 => "1");
  $tmp->maxScienceDepList = array(1 => "-1", 12 => "-1");
  
  $buildingTypeList[15] = $tmp;

  // Uga-Weihst&auml;tte
  $tmp = new Building(16,
                      "Uga-Weihst&auml;tte",
                      "<p>Ein einfacher Steinaltar zur Anbetung Ugas, dem guten Gott der Sch&ouml;pfung. In den gr&ouml;&szlig;eren Ausbaustufen hingegen ein m&auml;chtiges, reich bemaltes Monument. Dieser Altar kann nat&uuml;rlich nur von Uga-Spielern errichtet werden und produziert je nach Ausbaustufe mehr oder weniger g&ouml;ttliche Gunst.</p>",
                      "building_ugaaltar",
                      16,
                      "3",
                      "2880*(20/(1+[R0.ACT]))");

  $tmp->ratingValue = 2;
  $tmp->resourceProductionCost = array(1 => "60", 2 => "150", 3 => "200");
  
  $tmp->scienceDepList    = array(14 => "1", 15 => "0", 29 => "0", 31 => "0", 30 => "3");
  $tmp->maxScienceDepList = array(14 => "-1", 15 => "0", 29 => "0", 31 => "0", 30 => "-1");
  
  $buildingTypeList[16] = $tmp;

  // Agga-Opferst&auml;tte
  $tmp = new Building(17,
                      "Agga-Opferst&auml;tte",
                      "<p>Hier werden dem f&uuml;rchterlichen Agga Tier- und Menschenopfer dargebracht. Besteht die einfachste Ausbaustufe haupts&auml;chlich aus einer Schlachtbank, wird bei der h&ouml;chsten Ausbaustufe das Tier- bzw. Menschenopfer vollautomatisch gequ&auml;lt und zur Ader gelassen. Nat&uuml;rlich l&auml;&szlig;t Agga bei einer solchen Opferfabrik dem Stamm auch mehr seiner g&ouml;ttlichen Gunst zuteil werden. Dieser Altar kann nat&uuml;rlich nur von Agga-Spielern errichtet werden.</p>",
                      "building_aggaaltar",
                      17,
                      "3",
                      "2880*(20/(1+[R0.ACT]))");

  $tmp->ratingValue = 2;
  $tmp->resourceProductionCost = array(0 => "20", 1 => "30", 2 => "100", 3 => "150");
  
  $tmp->scienceDepList    = array(14 => "0", 15 => "1", 29 => "0", 31 => "0", 30 => "3");
  $tmp->maxScienceDepList = array(14 => "0", 15 => "-1", 29 => "0", 31 => "0", 30 => "-1");
  
  $buildingTypeList[17] = $tmp;

  // Flugschule
  $tmp = new Building(18,
                      "Flugschule",
                      "<p>FIXME</p>",
                      "building_flightschool",
                      18,
                      "10",
                      "(1440+720*POW([B18.ACT]+0.1,1.5))/(GREATEST(1,[B8.ACT]-1)+[R0.ACT]/60)");

  $tmp->ratingValue = 4;
  $tmp->resourceProductionCost = array(0 => "2", 1 => "60+60*[B18.ACT]", 2 => "250+245*[B18.ACT]*POW(1.1,[B18.ACT]+1)", 3 => "100+200*[B18.ACT]");
  
  $tmp->buildingDepList    = array(8 => "2", 11 => "0", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(8 => "-1", 11 => "0", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(3 => "2", 13 => "6", 28 => "2");
  $tmp->maxScienceDepList = array(3 => "-1", 13 => "-1", 28 => "-1");
  
  $buildingTypeList[18] = $tmp;

  // Schleimgrube
  $tmp = new Building(19,
                      "Schleimgrube",
                      "<p>Eine Grube, gef&uuml;llt mit einer ekligen, schleimigen Mischung aus Erde, Wasser, verfaulten Pflanzenresten und Exkrementen. In diesem N&auml;hrboden werden die seltsamsten Dinge gez&uuml;chtet.</p>",
                      "building_slimePit",
                      19,
                      "10",
                      "(1440+720*POW([B19.ACT]+0.1,1.5))/(GREATEST(1,[B8.ACT]-1)+[R0.ACT]/60)");

  $tmp->ratingValue = 4;
  $tmp->resourceProductionCost = array(0 => "2", 1 => "60+60*[B19.ACT]", 2 => "250+245*[B19.ACT]*POW(1.1,[B19.ACT]+1)", 3 => "100+200*[B19.ACT]");
  
  $tmp->buildingDepList    = array(8 => "2", 11 => "0", 18 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(8 => "-1", 11 => "0", 18 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(13 => "6", 27 => "2", 32 => "2");
  $tmp->maxScienceDepList = array(13 => "-1", 27 => "-1", 32 => "-1");
  
  $buildingTypeList[19] = $tmp;

  // Rennstrecke
  $tmp = new Building(20,
                      "Rennstrecke",
                      "<p>Sport war schon zu pr&auml;historischen Zeiten eine willkommene Abwechslung. Von wegen die Griechen haben die Olympiade erfunden! Na ja, zumindest werden die Teilnehmer durch das Training schneller.</p><p>Allerdings kann eine Rennstrecke aus Gefahrengr&uuml;nden nicht in einer H&ouml;hle gebaut werden, in der es schon einen Projektilbearbeiter gibt.</p>",
                      "building_runningTrack",
                      20,
                      "10",
                      "(1440+720*POW([B20.ACT]+0.1,1.5))/(GREATEST(1,[B8.ACT]-1)+[R0.ACT]/60)");

  $tmp->ratingValue = 4;
  $tmp->resourceProductionCost = array(0 => "2", 1 => "60+60*[B20.ACT]", 2 => "250+245*[B20.ACT]*POW(1.1,[B20.ACT]+1)", 3 => "100+200*[B20.ACT]");
  
  $tmp->buildingDepList    = array(8 => "2", 11 => "0", 18 => "0", 19 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(8 => "-1", 11 => "0", 18 => "0", 19 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(13 => "6", 28 => "2", 33 => "2");
  $tmp->maxScienceDepList = array(13 => "-1", 28 => "-1", 33 => "-1");
  
  $buildingTypeList[20] = $tmp;

  // Thronsaal
  $tmp = new Building(21,
                      "Thronsaal",
                      "<p>Gut, der Thronsaal ist eher ein feuchtes Gew&ouml;lbe in einer Ecke der H&ouml;hle denn ein wirklicher Saal, aber f&uuml;r damalige Verh&auml;ltnisse durchaus bequem und prunkvoll.</p><p>Der Saal kann nahezu beliebig ausgebaut und mit zus&auml;tzlichem Prunk versehen werden. Das hat zur Folge, da&szlig; Ihrer Bev&ouml;lkerung Ihre wahre Gr&ouml;&szlig;e klar vor Augen gef&uuml;hrt wird. F&uuml;r einen gr&ouml;&szlig;eren Anf&uuml;hrer aber werfen sich Ihre Untertanen nat&uuml;rlich mit gr&ouml;&szlig;erer Hingabe in den Kampf gegen feindliche &Uuml;bernahmen ihrer kleinen H&ouml;hlengesellschaft.</p><p>Soll hei&szlig;en: Jede Stufe dieses Saales erh&ouml;ht betr&auml;chtlich die Kampfkraft, mit der Ihre Bev&ouml;lkerung in einer &Uuml;bernahmeschlacht zu Werke geht.</p>",
                      "building_throne",
                      21,
                      "40",
                      "1440/GREATEST(1,[R0.ACT]/50)");

  $tmp->ratingValue = 2;
  $tmp->resourceProductionCost = array(1 => "40+40*POW([B21.ACT],2)", 2 => "100+100*POW([B21.ACT],2)", 3 => "100+100*POW([B21.ACT],2)", 4 => "40*POW(GREATEST(0,[B21.ACT]-2),2)", 5 => "30*POW(GREATEST(0,[B21.ACT]-2),2)");
  
  $tmp->buildingDepList    = array(0 => "2");
  $tmp->maxBuildingDepList = array(0 => "-1");
  
  $tmp->scienceDepList    = array(0 => "4", 8 => "2");
  $tmp->maxScienceDepList = array(0 => "-1", 8 => "-1");
  
  $buildingTypeList[21] = $tmp;

  // Windkraftrad
  $tmp = new Building(22,
                      "Windkraftrad",
                      "<p>Gegen Ende des dritten Zeitalters beklagten sich die Arbeiter in den Kn&uuml;ppelmanufakturen bitterlich &uuml;ber die Arbeitsbedingungen. Viele litten wegen der handbetriebenen Kn&uuml;ppel-Schnitz-und-Drechsel-Maschinen an Gelenkproblemen. Ein Welle von Aufst&auml;nden und Streiks durchzog das Uga-Agga-Tal. Zum ersten Mal in der Geschichte wurden die Aufschreie der Arbeiter erh&ouml;rt: Die Arbeit an Windkraftanlagen begann. Gro&szlig;e mit Fellen bespannte Astr&auml;der treiben nun die Maschinen an, und das schneller und regelm&auml;&szlig;iger als von Menschenhand. Nun wuchs der Kn&uuml;ppelstapel in wenigen Minuten um ein neues Exemplar an, statt wie fr&uuml;her stundenlang an einem Kn&uuml;ppel zu werken. </p><p>Daher auch der umgangssprachliche Name: \"Stattwerke\". </p><p>Durch die verbesserten Produktionsmethoden wurden leider schnellere und h&auml;rtere Kriege m&ouml;glich, in denen meist aber auch die Windkraftr&auml;der zerst&ouml;rt wurden. F&uuml;r den Wiederaufbau wurden weitere Arbeitskr&auml;fte ben&ouml;tigt, was enorme Kosten verursachte, doch der Fortschritt und die modernen Formen der Energiegewinnung waren nicht aufzuhalten. Deshalb ist es heute nicht mehr m&ouml;glich, eine Kn&uuml;ppelmanufaktur zu bauen, ohne einige Windr&auml;der zu errichten.</p>",
                      "building_windmachine",
                      22,
                      "4",
                      "1440/GREATEST(1,[R0.ACT]/20)");

  $tmp->ratingValue = 1;
  $tmp->resourceProductionCost = array(1 => "40+40*POW([B22.ACT],2)", 2 => "100+100*POW([B22.ACT],2)", 3 => "100+100*POW([B22.ACT],2)");
  
  $tmp->scienceDepList    = array(28 => "1");
  $tmp->maxScienceDepList = array(28 => "-1");
  
  $buildingTypeList[22] = $tmp;

  // Botanischer Garten
  $tmp = new Building(23,
                      "Botanischer Garten",
                      "<p>FIXME</p>",
                      "building_botanicgarden",
                      23,
                      "10",
                      "(1440+720*POW([B23.ACT]+0.1,1.5))/(GREATEST(1,[B8.ACT]-1)+[R0.ACT]/60)");

  $tmp->ratingValue = 4;
  $tmp->resourceProductionCost = array(0 => "2", 1 => "60+60*[B23.ACT]", 2 => "250+245*[B23.ACT]*POW(1.1,[B23.ACT]+1)", 3 => "100+200*[B23.ACT]");
  
  $tmp->buildingDepList    = array(8 => "2", 11 => "0", 18 => "0", 19 => "0", 20 => "0");
  $tmp->maxBuildingDepList = array(8 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0");
  
  $tmp->scienceDepList    = array(13 => "6", 27 => "2", 34 => "2");
  $tmp->maxScienceDepList = array(13 => "-1", 27 => "-1", 34 => "-1");
  
  $buildingTypeList[23] = $tmp;

  // Quelle des Lebens: Gelbfleckiger Tumtumbaum
  $tmp = new Building(24,
                      "Quelle des Lebens: Gelbfleckiger Tumtumbaum",
                      "<p>FIXME</p>",
                      "building_tumtum",
                      24,
                      "3",
                      "2880*(20/(1+[R0.ACT]))");

  $tmp->ratingValue = 2;
  $tmp->resourceProductionCost = array(0 => "20", 1 => "30", 2 => "100", 3 => "150");
  
  $tmp->scienceDepList    = array(14 => "0", 15 => "0", 29 => "1", 31 => "0", 30 => "3");
  $tmp->maxScienceDepList = array(14 => "0", 15 => "0", 29 => "-1", 31 => "0", 30 => "-1");
  
  $buildingTypeList[24] = $tmp;

}

	

/********************** Sciencetypes *********************/
class Science {
  var $scienceID;
  var $name;
  var $description;
  var $dbFieldName;
  var $position;
  var $maxLevel;
  var $productionTimeFunction;

  var $resourceProductionCost  = array();
  var $unitProductionCost      = array();
  var $buildingProductionCost  = array();
  var $externalProductionCost  = array();
  var $buildingDepList         = array();
  var $maxBuildingDepList      = array();
  var $defenseSystemDepList    = array();
  var $maxDefenseSystemDepList = array();
  var $resourceDepList         = array();
  var $maxResourceDepList      = array();
  var $scienceDepList          = array();
  var $maxScienceDepList       = array();
  var $unitDepList             = array();
  var $maxUnitDepList          = array();
  var $nodocumentation         = 0;

  function Science($scienceID, $name, $description, $dbFieldName, $position, $maxLevel, $productionTimeFunction){
    $this->scienceID              = $scienceID;
    $this->name                   = $name;
    $this->description            = $description;
    $this->dbFieldName            = $dbFieldName;
    $this->position               = $position;
    $this->maxLevel               = $maxLevel ;
    $this->productionTimeFunction = $productionTimeFunction;
  }
}

function init_sciences(){

  global $scienceTypeList;
  
  // H&ouml;hlenmalerei
  $tmp = new Science(0,
                     "H&ouml;hlenmalerei",
                     "<p>Die H&ouml;hlenmenschen der Steinzeit haben mittels Bemalung der H&ouml;hlenw&auml;nde ihr Wissen f&uuml;r nachfolgende Generationen konserviert. Die Erfindung der H&ouml;hlenmalerei ist die Voraussetzung f&uuml;r alle weiteren Forschungen.</p><p>Eine h&ouml;here Stufe der H&ouml;hlenmalerei beschleunigt au&szlig;erdem die gesamte Forschungst&auml;tigkeit.</p>",
                     "science_painting",
                     0,
                     "10",
                     "480+360*[S0.ACT]");

  $tmp->resourceProductionCost = array(1 => "50*GREATEST(0,[S0.ACT]-1)", 2 => "5+50*POW([S0.ACT],2)");
  

  $scienceTypeList[0] = $tmp;

  // H&ouml;here Steineskunde
  $tmp = new Science(1,
                     "H&ouml;here Steineskunde",
                     "<p>Diese Forschung ist notwendig, um neue Anwendungen f&uuml;r die allgegenw&auml;rtigen Steine zu finden. Au&szlig;erdem ist die Steineskunde f&uuml;r den richtigen Umgang mit Feuersteinen erforderlich.</p>",
                     "science_stone",
                     1,
                     "10",
                     "(1440+720*[S1.ACT])/GREATEST(1,[S0.ACT])");

  $tmp->resourceProductionCost = array(1 => "5+20*[S1.ACT]", 3 => "100+100*POW([S1.ACT]+0.1,1.5)");
  
  $tmp->scienceDepList    = array(0 => "1");
  $tmp->maxScienceDepList = array(0 => "-1");
  

  $scienceTypeList[1] = $tmp;

  // Materialbearbeitung
  $tmp = new Science(2,
                     "Materialbearbeitung",
                     "<p>Die Erforschung der einfachen Handwerkskunst ist f&uuml;r das Erlernen verschiedener handwerklicher Fertigkeiten notwendig und Voraussetzung f&uuml;r die Herstellung von Werkzeugen und Waffen.</p>",
                     "science_craftmanship",
                     2,
                     "10",
                     "(1440+720*[S2.ACT])/GREATEST(1,[S0.ACT])");

  $tmp->resourceProductionCost = array(1 => "5+20*[S2.ACT]", 2 => "80+80*POW([S2.ACT]+0.1,1.5)", 3 => "70+70*POW([S2.ACT]+0.1,1.5)");
  
  $tmp->scienceDepList    = array(0 => "1");
  $tmp->maxScienceDepList = array(0 => "-1");
  

  $scienceTypeList[2] = $tmp;

  // &Auml;rod&uuml;namik
  $tmp = new Science(3,
                     "&Auml;rod&uuml;namik",
                     "<p>Ein armwedelnder Neandertaler, der einen Stein in der Hand hielt, ist durch Zufall &uuml;ber dieses Konzept gestolpert. Es ist der Ausgangspunkt f&uuml;r viele weitere bahnbrechende Erfindungen, die sich mit dem Schleudern und Werfen von Gegenst&auml;nden befassen. Um die Bewegungen der Objekte durch dieses d&uuml;nne Element zu verstehen, sind viele Steine n&ouml;tig.</p>",
                     "science_aerodynamic",
                     3,
                     "10",
                     "(1440+720*[S3.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S3.ACT]", 2 => "400+1000*[S3.ACT]", 3 => "400+1000*[S3.ACT]");
  
  $tmp->scienceDepList    = array(0 => "2", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[3] = $tmp;

  // Fallenstellerei
  $tmp = new Science(4,
                     "Fallenstellerei",
                     "<p>Die Fallenstellerei erlaubt den Einsatz diverser Arten von Fallen. Diese sind ein wirkungsvolles Hilfsmittel sowohl bei der Nahrungsbeschaffung als auch beim Einfangen von Tieren. Sie k&ouml;nnen aber auch verwendet werden, um feindlichen K&auml;mpfern eine unerfreuliche &Uuml;berraschung zu bereiten.</p>",
                     "science_trapping",
                     4,
                     "10",
                     "(1440+720*[S4.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "30+35*[S4.ACT]", 2 => "100+85*POW([S4.ACT]+0.1,1.5)", 3 => "80+80*[S4.ACT]");
  
  $tmp->buildingDepList    = array(7 => "4");
  $tmp->maxBuildingDepList = array(7 => "-1");
  
  $tmp->scienceDepList    = array(0 => "2", 2 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 2 => "-1");
  

  $scienceTypeList[4] = $tmp;

  // Keilwerkzeuge
  $tmp = new Science(5,
                     "Keilwerkzeuge",
                     "<p>Verschiedene Arten von Keilwerkzeugen (wie zum Beispiel die Steinaxt) sind sowohl zum Bearbeiten von Holz und Steinen, aber auch bei der Jagd und im Kampf von gro&szlig;em Nutzen.</p>",
                     "science_advanced_tools",
                     5,
                     "10",
                     "(1440+720*[S5.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "5+5*[S5.ACT]", 2 => "120+120*POW([S5.ACT]+0.1,1.5)", 3 => "120+120*POW([S5.ACT]+0.1,1.5)");
  
  $tmp->scienceDepList    = array(0 => "2", 2 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 2 => "-1");
  

  $scienceTypeList[5] = $tmp;

  // Feuer
  $tmp = new Science(6,
                     "Feuer",
                     "<p>Die Beherrschung des Feuers und die F&auml;higkeit, es jederzeit kontrolliert entfachen zu k&ouml;nnen, war ein Meilenstein in der Entwicklung der Menschheit. Es sind viele Steine und reichlich trockenes Holz n&ouml;tig, um die richtige Nutzung der Feuersteine zu erlernen.</p>",
                     "science_fire",
                     6,
                     "10",
                     "(1440+720*[S6.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S6.ACT]", 2 => "400+1000*[S6.ACT]", 3 => "400+1000*[S6.ACT]");
  
  $tmp->scienceDepList    = array(0 => "3", 1 => "2", 5 => "2", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 1 => "-1", 5 => "-1", 13 => "-1");
  

  $scienceTypeList[6] = $tmp;

  // Rad
  $tmp = new Science(7,
                     "Rad",
                     "<p>Durch die Erfindung des Rades konnten die Menschen auch schwere Lasten &uuml;ber gr&ouml;&szlig;ere Entfernungen transportieren. Anfangs dienten dazu nur einfache Baumst&auml;mme, &uuml;ber die Gegenst&auml;nde gerollt werden konnten. Sp&auml;ter kamen dann verschiedene Scheiben aus Stein und Holz dazu, bis schlie&szlig;lich die uns bekannten runden Scheiben entwickelt wurden.</p>",
                     "science_wheel",
                     7,
                     "10",
                     "(1440+720*[S7.ACT])/GREATEST(1,[S0.ACT]-2)");

  $tmp->resourceProductionCost = array(1 => "500+500*[S7.ACT]", 2 => "100+200*[S7.ACT]", 3 => "100+50*POW([S7.ACT]+0.1,1.5)");
  
  $tmp->scienceDepList    = array(0 => "3", 3 => "2", 5 => "2");
  $tmp->maxScienceDepList = array(0 => "-1", 3 => "-1", 5 => "-1");
  

  $scienceTypeList[7] = $tmp;

  // Schnitzerei
  $tmp = new Science(8,
                     "Schnitzerei",
                     "<p>Die Schnitzerei ist die verbesserte Fertigkeit der Holzbearbeitung mit geeigneten Knochen- und Steinwerkzeugen. Die Erlernung der Schnitzerei ist f&uuml;r die Erforschung der h&ouml;heren Konstruktionskunst notwendig, und sie ist Voraussetzung f&uuml;r alle aus Holz gefertigten Waffen mit Ausnahme der einfachen Kn&uuml;ppel.</p>",
                     "science_carving",
                     8,
                     "10",
                     "(1440+720*[S8.ACT])/GREATEST(1,[S0.ACT]-2)");

  $tmp->resourceProductionCost = array(1 => "5+25*POW([S8.ACT],2)", 2 => "300+250*POW([S8.ACT]+0.1,1.5)", 3 => "200+200*[S8.ACT]");
  
  $tmp->scienceDepList    = array(0 => "3", 2 => "3", 5 => "2");
  $tmp->maxScienceDepList = array(0 => "-1", 2 => "-1", 5 => "-1");
  

  $scienceTypeList[8] = $tmp;

  // Metallverarbeitung
  $tmp = new Science(9,
                     "Metallverarbeitung",
                     "<p>Verschiedene Metalle, wie z.B. Kupfer und Bronze, dienen zur Herstellung verbesserter Werkzeuge und Waffen. Das Erzgestein wird von einem Metallschmelzer gesammelt, eingeschmolzen und anschlie&szlig;end mit Formen aus Sand oder Stein in die gew&uuml;nschte Form gebracht.</p>",
                     "science_metalurgy",
                     9,
                     "10",
                     "(1440+720*[S9.ACT])/GREATEST(1,[S0.ACT]-3)");

  $tmp->resourceProductionCost = array(1 => "100+100*[S9.ACT]", 2 => "600+800*POW([S9.ACT]+0.1,1.1)", 3 => "400+600*POW([S9.ACT]+0.1,1.4)");
  
  $tmp->scienceDepList    = array(0 => "4", 1 => "5", 6 => "3");
  $tmp->maxScienceDepList = array(0 => "-1", 1 => "-1", 6 => "-1");
  

  $scienceTypeList[9] = $tmp;

  // Tierz&auml;hmung
  $tmp = new Science(10,
                     "Tierz&auml;hmung",
                     "<p>Nachdem der Mensch gelernt hatte, die wilden Tiere zu z&auml;hmen, konnte er sie domestizieren und zur Unterst&uuml;tzung bei der Jagd oder im Kampf einsetzen. Ohne die F&auml;higkeit der Tierz&auml;hmung kann kein Bestiarium errichtet werden. Eine h&ouml;here Forschungsstufe erlaubt die Z&auml;hmung gef&auml;hrlicherer Tiere.</p>",
                     "science_domestication",
                     10,
                     "10",
                     "(2880+1440*POW([S10.ACT]+0.1,1.4))/GREATEST(1,[S0.ACT]-3)");

  $tmp->resourceProductionCost = array(1 => "600+600*[S10.ACT]", 2 => "800+800*[S10.ACT]", 3 => "800+800*[S10.ACT]");
  
  $tmp->scienceDepList    = array(0 => "4", 4 => "3", 6 => "3");
  $tmp->maxScienceDepList = array(0 => "-1", 4 => "-1", 6 => "-1");
  

  $scienceTypeList[10] = $tmp;

  // H&ouml;here Konstruktionskunst
  $tmp = new Science(11,
                     "H&ouml;here Konstruktionskunst",
                     "<p>Die Erforschung der h&ouml;heren Konstruktionskunst ist notwendig, um mechanische Kampfeinheiten herzustellen und komplexere Geb&auml;ude und Verteidigungsanlagen aufzubauen. Diese Fertigkeit ist auch Voraussetzung f&uuml;r die Errichtung eines Konstruktionszentrums.</p>",
                     "science_construction",
                     11,
                     "10",
                     "(2880+1440*POW([S11.ACT]+0.1,1.4))/GREATEST(1,[S0.ACT]-3)");

  $tmp->resourceProductionCost = array(1 => "800+800*[S11.ACT]", 2 => "600+600*[S11.ACT]", 3 => "800+800*[S11.ACT]");
  
  $tmp->scienceDepList    = array(0 => "4", 7 => "2", 8 => "2");
  $tmp->maxScienceDepList = array(0 => "-1", 7 => "-1", 8 => "-1");
  

  $scienceTypeList[11] = $tmp;

  // Schwefelgewinnung
  $tmp = new Science(12,
                     "Schwefelgewinnung",
                     "<p>Das Auffinden der seltenen nat&uuml;rlichen Schwefelvorkommen erfordert ein langes Studium der h&ouml;heren Steineskunde. Schwefel brennt gut und stinkt dabei f&uuml;rchterlich, was zur Z&auml;hmung von Flederm&auml;usen und zur Verteidigung der eigenen H&ouml;hle eingesetzt werden kann.</p>",
                     "science_sulfur",
                     12,
                     "10",
                     "(2880+1440*POW([S12.ACT]+0.1,1.4))/GREATEST(1,[S0.ACT]-4)");

  $tmp->resourceProductionCost = array(1 => "950+800*[S12.ACT]", 2 => "1200+1000*[S12.ACT]", 3 => "800+800*[S12.ACT]");
  
  $tmp->scienceDepList    = array(0 => "5", 1 => "7", 6 => "4");
  $tmp->maxScienceDepList = array(0 => "-1", 1 => "-1", 6 => "-1");
  

  $scienceTypeList[12] = $tmp;

  // M&uuml;hstik
  $tmp = new Science(13,
                     "M&uuml;hstik",
                     "<p>Schnell erkannten die fr&uuml;hen Menschen, da&szlig; es neben Steinen, Holz und Nahrung noch mehr im Leben geben mu&szlig;. Wenn dein Stamm die M&uuml;hstik erlernt hat, kann er sich f&uuml;r die Anbetung eines der beiden G&ouml;tter dieser Welt entscheiden: Uga oder Agga. Beide gew&auml;hren unterschiedliche Vor- und Nachteile. Aber nat&uuml;rlich kann man sich auch daf&uuml;r entscheiden, keinen Gott zu verehren.</p><p>In den pr&auml;historischen Zeiten kam fast alles Wissen aus der M&uuml;hstik. Da fast nichts erkl&auml;rbar war, wurden selbst so banale Sachen wie das Feuer &uuml;bernat&uuml;rlichen Kr&auml;ften zugeschrieben - wobei \"&uuml;bernat&uuml;rlich\" wohl f&uuml;r \"kl&uuml;ger als der Mensch\" zu stehen hat. Damit sind grundlegende Kenntnisse in der M&uuml;hstik Voraussetzung f&uuml;r das Voranschreiten auf einer Vielzahl von Wissensgebieten.</p>",
                     "science_mysticism",
                     13,
                     "10",
                     "(1440+720*[S13.ACT])/GREATEST(1,[S0.ACT])");

  $tmp->resourceProductionCost = array(1 => "200+200*[S13.ACT]");
  
  $tmp->scienceDepList    = array(0 => "1");
  $tmp->maxScienceDepList = array(0 => "-1");
  

  $scienceTypeList[13] = $tmp;

  // Uga
  $tmp = new Science(14,
                     "Uga",
                     "<p>Die Anbetung von Uga, dem guten Gott der Sch&ouml;pfung. Als Anh&auml;nger von Uga erh&auml;lt dein Stamm tags&uuml;ber einen kleinen Bonus auf die Rohstoffproduktion und kann den Sch&ouml;pfungsgelehrten ausbilden. Allerdings f&uuml;rchten die Anh&auml;nger Ugas die Finsternis und k&auml;mpfen nachts mit verminderter St&auml;rke, da sie leicht in Panik geraten.</p><p>Je h&ouml;her man seinen Glauben entwickelt, desto h&ouml;her ist die Wahrscheinlichkeit, da&szlig; Uga das Flehen um ein Wunder erh&ouml;rt.</p><p>Achtung: Jeder Stamm kann nur einen der beiden G&ouml;tter anbeten, und die Entscheidung kann nicht r&uuml;ckg&auml;ngig gemacht werden.</p>",
                     "science_uga",
                     14,
                     "10",
                     "(2880+1440*[S14.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S14.ACT]", 2 => "100+1000*[S14.ACT]", 3 => "100+1000*[S14.ACT]", 4 => "10*[S14.ACT]");
  
  $tmp->scienceDepList    = array(0 => "2", 13 => "5", 30 => "2", 15 => "0", 29 => "0", 31 => "0", 32 => "1", 28 => "1", 34 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 30 => "-1", 15 => "0", 29 => "0", 31 => "0", 32 => "-1", 28 => "-1", 34 => "-1");
  

  $scienceTypeList[14] = $tmp;

  // Agga
  $tmp = new Science(15,
                     "Agga",
                     "<p>Die Anbetung von Agga, dem b&ouml;sen Gott des Krieges. Da die Anh&auml;nger von Agga die Dunkelheit vorziehen, erh&auml;lt dein Stamm in der Nacht einen kleinen Bonus im Kampf (um so gr&ouml;&szlig;er, je dunkler es ist) und kann au&szlig;erdem besondere Kampfeinheiten ausbilden. Diese Spezialisierung f&uuml;hrt allerdings tags&uuml;ber zu einer verminderten Rohstoffproduktion.</p><p>Je h&ouml;her man seinen Glauben entwickelt, desto h&ouml;her ist die Wahrscheinlichkeit, da&szlig; Agga das Flehen um ein Wunder erh&ouml;rt.</p><p>Achtung: Jeder Stamm kann nur einen der beiden G&ouml;tter anbeten, und die Entscheidung kann nicht r&uuml;ckg&auml;ngig gemacht werden.</p>",
                     "science_agga",
                     15,
                     "10",
                     "(2880+1440*[S15.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(0 => "10*[S15.ACT]", 1 => "100+800*[S15.ACT]", 2 => "100+800*[S15.ACT]", 3 => "100+800*[S15.ACT]", 5 => "8*[S15.ACT]");
  
  $tmp->scienceDepList    = array(0 => "2", 3 => "1", 6 => "1", 13 => "5", 30 => "2", 14 => "0", 29 => "0", 31 => "0", 35 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 3 => "-1", 6 => "-1", 13 => "-1", 30 => "-1", 14 => "0", 29 => "0", 31 => "0", 35 => "-1");
  

  $scienceTypeList[15] = $tmp;

  // Bewirtschaftung
  $tmp = new Science(16,
                     "Bewirtschaftung",
                     "<p>Nachdem die St&auml;mme jahrtausendelang als Nomaden gelebt haben, lie&szlig;en sie sich nun nieder, um auf besonders geeignetem Boden Pflanzen anzubauen und Haustiere zu halten. Die Nahrungsproduktion wird durch jede Stufe dieser Forschung um 20% der Grundproduktion erh&ouml;ht.</p>",
                     "science_agriculture",
                     16,
                     "10",
                     "(2880+1440*[S16.ACT])/GREATEST(1,[S0.ACT]-2)");

  $tmp->resourceProductionCost = array(1 => "1000+2000*[S16.ACT]", 2 => "1000+2000*[S16.ACT]", 4 => "10+100*[S16.ACT]");
  
  $tmp->buildingDepList    = array(2 => "14", 7 => "14");
  $tmp->maxBuildingDepList = array(2 => "-1", 7 => "-1");
  
  $tmp->scienceDepList    = array(0 => "3", 5 => "6");
  $tmp->maxScienceDepList = array(0 => "-1", 5 => "-1");
  

  $scienceTypeList[16] = $tmp;

  // Kirkalot, Halbgott der irdischen Verg&auml;nglichkeit
  $tmp = new Science(17,
                     "Kirkalot, Halbgott der irdischen Verg&auml;nglichkeit",
                     "<p>Nach reiflichen &Uuml;berlegungen kommen die Geistlichen zu dem Schlu&szlig;, da&szlig; die Erde und der Tod und alles damit zusammenh&auml;ngende von einem Halbgott geformt und gewartet wird.</p><p>Die Anh&auml;nger des Halbgottes Kirkalot erhalten au&szlig;ergew&ouml;hnliche F&auml;higkeiten im Bereich irdischer Verg&auml;nglichkeit - verlieren aufgrund ihres Glaubens aber die meisten F&auml;higkeiten in den anderen Bereichen.</p>",
                     "science_kirkalot",
                     17,
                     "10",
                     "(2880+1440*[S17.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S17.ACT]", 2 => "100+1000*[S17.ACT]", 3 => "100+1000*[S17.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "0", 15 => "0", 29 => "0", 31 => "2", 27 => "6", 25 => "0");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1", 27 => "-1", 25 => "0");
  

  $scienceTypeList[17] = $tmp;

  // Slavomir, Halbgott des Windes
  $tmp = new Science(18,
                     "Slavomir, Halbgott des Windes",
                     "<p>Die Winde des Tales aber werden von einem ungeheuren Riesen erzeugt, der fernab von jeglicher H&ouml;hle in einer versteckten Schlucht wohnt. Sein Name aber ist Slavomir, H&uuml;ter der Winde und Vater der vier windigen Schnecken Svanta, Wosta, Slawa und Dazbag.</p><p>Auf einem riesigen H&uuml;gel aus purem Schneckenschleim sitzt er und schmiedet abends mit seinem Hammer aus glitzernder Luft neue Winde und L&uuml;fte und haucht ab und an wundersame und befl&uuml;gelnde Gedanken hinein, die die Sterblichen des Nachts in ihren kleinen Hirnen erahnen. Am Morgen jedoch steht er am Rande der Schlucht und erntet mit seiner Sichel die von ihm gearbeiteten und von seinen T&ouml;chtern ausgebrachten Winde.</p><p>Die Verehrer Slavomirs kommen in den Genu&szlig; von ungeahnter Schnelligkeit und guten Winden. Gute Tr&auml;ume vers&uuml;&szlig;en ihre N&auml;chte und lassen jeden neuen Tag zu einem gro&szlig;artigen Erlebnis werden.</p><p>Die Anh&auml;nger des Halbgottes Slavomir erhalten au&szlig;ergew&ouml;hnliche F&auml;higkeiten im Bereich Wind - verlieren aufgrund ihres Glaubens aber die meisten F&auml;higkeiten in den anderen Bereichen.</p>",
                     "science_slavomir",
                     18,
                     "10",
                     "(2880+1440*[S18.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S18.ACT]", 2 => "100+1000*[S18.ACT]", 3 => "100+1000*[S18.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "2", 15 => "0", 29 => "0", 31 => "0", 22 => "0", 24 => "0", 28 => "6");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 22 => "0", 24 => "0", 28 => "-1");
  

  $scienceTypeList[18] = $tmp;

  // Nicknehm, Halbgott der Botanik
  $tmp = new Science(19,
                     "Nicknehm, Halbgott der Botanik",
                     "<p>FIXME</p><p>Nach reiflichen &Uuml;berlegungen kommen die Geistlichen zu dem Schlu&szlig;, da&szlig; das Leben und alles damit zusammenh&auml;ngende von einem Halbgott geformt und gewartet wird.</p><p>Die Anh&auml;nger des Halbgottes Nicknehm erhalten au&szlig;ergew&ouml;hnliche F&auml;higkeiten im Bereich Leben - verlieren aufgrund ihres Glaubens aber die meisten F&auml;higkeiten in den anderen Bereichen.</p>",
                     "science_nicknehm",
                     19,
                     "10",
                     "(2880+1440*[S19.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S19.ACT]", 2 => "100+1000*[S19.ACT]", 3 => "100+1000*[S19.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "0", 15 => "0", 29 => "2", 31 => "0", 36 => "6", 23 => "0", 39 => "0");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 36 => "-1", 23 => "0", 39 => "0");
  

  $scienceTypeList[19] = $tmp;

  // Paffi, Halbgott des Feuers
  $tmp = new Science(20,
                     "Paffi, Halbgott des Feuers",
                     "<p>Nach reiflichen &Uuml;berlegungen kommen die Geistlichen zu dem Schlu&szlig;, da&szlig; das Feuer und alles damit zusammenh&auml;ngende von einem Halbgott geformt und gewartet wird.</p><p>Die Anh&auml;nger des Halbgottes Paffi erhalten au&szlig;ergew&ouml;hnliche F&auml;higkeiten im Bereich Feuer - verlieren aufgrund ihres Glaubens aber die meisten F&auml;higkeiten in den anderen Bereichen.</p>",
                     "science_paffi",
                     20,
                     "10",
                     "(2880+1440*[S20.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S20.ACT]", 2 => "100+1000*[S20.ACT]", 3 => "100+1000*[S20.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "0", 15 => "2", 29 => "0", 31 => "0", 6 => "6", 21 => "0", 26 => "0");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 6 => "-1", 21 => "0", 26 => "0");
  

  $scienceTypeList[20] = $tmp;

  // Sirat, Halbgott der &Auml;rod&uuml;namik
  $tmp = new Science(21,
                     "Sirat, Halbgott der &Auml;rod&uuml;namik",
                     "<p>Die V&ouml;gel wurden ganz still und Sirat erhob seinen Kopf und schaute umher. Da kamen auch schon seine Pestv&ouml;gel angeflogen und berichteten ihm, was Schlimmes geschehen war: \"Die Luft, &auml;chz, die Luft, sie ist weg. Du mu&szlig;t etwas unternehmen!\".</p><p>Sirat erhob sich. Er flog &uuml;ber das Uga Agga Land und schaute sich das Unvorstellbare an. Da begriff er, Agga hatte eine neue Aufgabe f&uuml;r ihn. Er sah zwar aus wie ein m&auml;chtiger Vogel, sollte aber noch mehr bew&auml;ltigen. Er schwang seine gro&szlig;en Fl&uuml;gel blitzschnell und flog zu dem gro&szlig;en Felsen, von dem er das ganze Tal &uuml;berblicken konnte. Sirat atmete tief ein und pustete beim ausatmen ganz viel glitzernde Luft heraus. Sie verteilte sich &uuml;ber das ganze Tal. Die Pestv&ouml;gel kamen angeflogen und er sprach:</p><p>\"Ab heute bin ich f&uuml;r die &Auml;rod&uuml;namik verantwortlich und sollte auch nur irgendeiner dieser H&ouml;hlenbewohner undankbar sein, werde ich ihm die Luft rauben.\" Und so flog er wieder davon, zur&uuml;ck zu seinem Nest, wo er weiter &uuml;ber seinem heimlichen Schatz br&uuml;ten konnte. Welche Wunder h&auml;lt er noch parat?</p><p>Alle, die den einzig wahren Halbgott Sirat anbeten, belohnt er mit au&szlig;ergew&ouml;hnlichen Fertigkeiten auf den Gebiet des Fernkampfs und mit einer Brise frischer Luft. Da Sirat die frische Luft hoch oben gew&ouml;hnt ist, verlieren seine Anh&auml;nger aber die F&auml;higkeiten auf den meisten anderen Gebieten.</p><p>FIXME</p>",
                     "science_sirat",
                     21,
                     "10",
                     "(2880+1440*[S21.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S21.ACT]", 2 => "100+1000*[S21.ACT]", 3 => "100+1000*[S21.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "0", 15 => "2", 29 => "0", 31 => "0", 3 => "6", 20 => "0", 26 => "0");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 3 => "-1", 20 => "0", 26 => "0");
  

  $scienceTypeList[21] = $tmp;

  // Gharlane, Halbg&ouml;ttin des Wassers
  $tmp = new Science(22,
                     "Gharlane, Halbg&ouml;ttin des Wassers",
                     "<p>Nach reiflichen &Uuml;berlegungen kommen die Geistlichen zu dem Schlu&szlig;, da&szlig; das Wasser und alles damit zusammenh&auml;ngende von einer Halbg&ouml;ttin geformt und gewartet wird.</p><p>Die Anh&auml;nger der Halbg&ouml;ttin Gharlane erhalten au&szlig;ergew&ouml;hnliche F&auml;higkeiten im Bereich Wasser - verlieren aufgrund ihres Glaubens aber die meisten F&auml;higkeiten in den anderen Bereichen.</p>",
                     "science_gharlane",
                     22,
                     "10",
                     "(2880+1440*[S22.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S22.ACT]", 2 => "100+1000*[S22.ACT]", 3 => "100+1000*[S22.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "2", 15 => "0", 29 => "0", 31 => "0", 32 => "6", 18 => "0", 24 => "0");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 32 => "-1", 18 => "0", 24 => "0");
  

  $scienceTypeList[22] = $tmp;

  // Enzio, Halbgott der Zeit
  $tmp = new Science(23,
                     "Enzio, Halbgott der Zeit",
                     "<p>Zum Ende des zweiten Zeitalters wechselte Enzio, der Sohn Aggas, nach seines Vaters schmachvollen Niederlage und einem heftigen Streit die Seiten und diente im dritten Zeitalter dem alten Widersacher Uga.</p><p>Angewidert von dessen selbstherrlicher aber naiven G&uuml;te wandte sich Enzio letztendlich aber auch von diesem Gott ab. Seine Forschungen in der Disziplin Zeit, brachten ihn zu dem Schlu&szlig;, da&szlig; die Ewigkeit der beiden G&ouml;tter nur eine billige Illusion und an Uga und Agga eigentlich nichts g&ouml;ttlich sei. Im vierten Zeitalter verneinte Enzio das Vorhandensein der g&ouml;ttlichen, &uuml;bernat&uuml;rlichen Macht und sagte sich von seinen Vorfahren los. F&uuml;r seine Anh&auml;nger allerdings wurde Enzio dadurch entgegen seinen eigenen Absichten selbst zu einer Art Gottheit...</p><p>Die Anh&auml;nger des Halbgottes Enzio erhalten au&szlig;ergew&ouml;hnliche F&auml;higkeiten im Bereich Zeit - verlieren aufgrund ihres Glaubens, bzw. ihres Nicht-Glaubens an die Gottheiten Uga und Agga, aber alle alten F&auml;higkeiten, die sich auf die alte Lehre der Gottheiten Uga und Agga st&uuml;tzen.</p>",
                     "science_enzio",
                     23,
                     "10",
                     "(2880+1440*[S23.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S23.ACT]", 2 => "100+1000*[S23.ACT]", 3 => "100+1000*[S23.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "0", 15 => "0", 29 => "2", 31 => "0", 33 => "6", 19 => "0", 39 => "0");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 33 => "-1", 19 => "0", 39 => "0");
  

  $scienceTypeList[23] = $tmp;

  // Trubatsch, Halbgott des Lichts
  $tmp = new Science(24,
                     "Trubatsch, Halbgott des Lichts",
                     "<p>Nach reiflichen &Uuml;berlegungen kommen die Geistlichen zu dem Schlu&szlig;, da&szlig; das Licht und alles damit zusammenh&auml;ngende von einem Halbgott geformt und gewartet wird.</p><p>Die Anh&auml;nger des Halbgottes Trubatsch erhalten au&szlig;ergew&ouml;hnliche F&auml;higkeiten im Bereich Licht - verlieren aufgrund ihres Glaubens aber die meisten F&auml;higkeiten in den anderen Bereichen.</p>",
                     "science_trubatsch",
                     24,
                     "10",
                     "(2880+1440*[S24.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S24.ACT]", 2 => "100+1000*[S24.ACT]", 3 => "100+1000*[S24.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "2", 15 => "0", 29 => "0", 31 => "0", 34 => "6", 18 => "0", 22 => "0");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 34 => "-1", 18 => "0", 22 => "0");
  

  $scienceTypeList[24] = $tmp;

  // Der Alte Mann, Halbgott des Nebels
  $tmp = new Science(25,
                     "Der Alte Mann, Halbgott des Nebels",
                     "<p>FIXME  NEBEL</p>",
                     "science_nitsch",
                     25,
                     "10",
                     "(2880+1440*[S25.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S25.ACT]", 2 => "100+1000*[S25.ACT]", 3 => "100+1000*[S25.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "0", 15 => "0", 29 => "0", 31 => "2", 38 => "6", 17 => "0");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1", 38 => "-1", 17 => "0");
  

  $scienceTypeList[25] = $tmp;

  // Firak, Halbgott des Schattens
  $tmp = new Science(26,
                     "Firak, Halbgott des Schattens",
                     "<p>Nach reiflichen &Uuml;berlegungen kommen die Geistlichen zu dem Schlu&szlig;, da&szlig; der Schatten und alles damit zusammenh&auml;ngende von einem Halbgott geformt und gewartet wird.</p><p>Die Anh&auml;nger des Halbgottes Firak erhalten au&szlig;ergew&ouml;hnliche F&auml;higkeiten im Bereich Schatten - verlieren aufgrund ihres Glaubens aber die meisten F&auml;higkeiten in den anderen Bereichen.</p>",
                     "science_firak",
                     26,
                     "10",
                     "(2880+1440*[S26.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S26.ACT]", 2 => "100+1000*[S26.ACT]", 3 => "100+1000*[S26.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "0", 15 => "2", 29 => "0", 31 => "0", 35 => "6", 20 => "0", 21 => "0");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 35 => "-1", 20 => "0", 21 => "0");
  

  $scienceTypeList[26] = $tmp;

  // Erde
  $tmp = new Science(27,
                     "Erde",
                     "<p>Die Erde, eines der Elemente und eine wichtige m&uuml;hstische Disziplin, die von einem Stamm erforscht werden kann.</p><p>Das Erforschen der m&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_earth",
                     27,
                     "10",
                     "(1440+720*[S27.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S27.ACT]", 2 => "400+1000*[S27.ACT]", 3 => "400+1000*[S27.ACT]");
  
  $tmp->scienceDepList    = array(0 => "1", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[27] = $tmp;

  // Wind
  $tmp = new Science(28,
                     "Wind",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_wind",
                     28,
                     "10",
                     "(1440+720*[S28.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S28.ACT]", 2 => "400+1000*[S28.ACT]", 3 => "400+1000*[S28.ACT]");
  
  $tmp->scienceDepList    = array(0 => "1", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[28] = $tmp;

  // Leben
  $tmp = new Science(29,
                     "Leben",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_life",
                     29,
                     "10",
                     "(2880+1440*[S29.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S29.ACT]", 2 => "100+1000*[S29.ACT]", 3 => "100+1000*[S29.ACT]", 4 => "10*[S29.ACT]");
  
  $tmp->scienceDepList    = array(0 => "2", 13 => "5", 30 => "2", 14 => "0", 15 => "0", 31 => "0", 33 => "1", 36 => "1", 37 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 30 => "-1", 14 => "0", 15 => "0", 31 => "0", 33 => "-1", 36 => "-1", 37 => "-1");
  

  $scienceTypeList[29] = $tmp;

  // Gottesverehrung
  $tmp = new Science(30,
                     "Gottesverehrung",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_huldigung",
                     30,
                     "10",
                     "(1440+720*[S30.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S30.ACT]", 2 => "400+1000*[S30.ACT]", 3 => "400+1000*[S30.ACT]");
  
  $tmp->scienceDepList    = array(0 => "2", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[30] = $tmp;

  // Tod
  $tmp = new Science(31,
                     "Tod",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_death",
                     31,
                     "10",
                     "(2880+1440*[S31.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S31.ACT]", 2 => "100+1000*[S31.ACT]", 3 => "100+1000*[S31.ACT]", 4 => "10*[S31.ACT]");
  
  $tmp->scienceDepList    = array(0 => "2", 13 => "5", 30 => "2", 14 => "0", 15 => "0", 29 => "0", 27 => "1", 38 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 30 => "-1", 14 => "0", 15 => "0", 29 => "0", 27 => "-1", 38 => "-1");
  

  $scienceTypeList[31] = $tmp;

  // Wasser
  $tmp = new Science(32,
                     "Wasser",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_water",
                     32,
                     "10",
                     "(1440+720*[S32.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S32.ACT]", 2 => "400+1000*[S32.ACT]", 3 => "400+1000*[S32.ACT]");
  
  $tmp->scienceDepList    = array(0 => "1", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[32] = $tmp;

  // Zeit
  $tmp = new Science(33,
                     "Zeit",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_time",
                     33,
                     "10",
                     "(1440+720*[S33.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S33.ACT]", 2 => "400+1000*[S33.ACT]", 3 => "400+1000*[S33.ACT]");
  
  $tmp->scienceDepList    = array(0 => "1", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[33] = $tmp;

  // Licht
  $tmp = new Science(34,
                     "Licht",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_light",
                     34,
                     "10",
                     "(1440+720*[S34.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S34.ACT]", 2 => "400+1000*[S34.ACT]", 3 => "400+1000*[S34.ACT]");
  
  $tmp->scienceDepList    = array(0 => "1", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[34] = $tmp;

  // Schatten
  $tmp = new Science(35,
                     "Schatten",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_shadow",
                     35,
                     "10",
                     "(1440+720*[S35.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S35.ACT]", 2 => "400+1000*[S35.ACT]", 3 => "400+1000*[S35.ACT]");
  
  $tmp->scienceDepList    = array(0 => "1", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[35] = $tmp;

  // Botanik
  $tmp = new Science(36,
                     "Botanik",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_botanic",
                     36,
                     "10",
                     "(1440+720*[S36.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S36.ACT]", 2 => "400+1000*[S36.ACT]", 3 => "400+1000*[S36.ACT]");
  
  $tmp->scienceDepList    = array(0 => "1", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[36] = $tmp;

  // Raum
  $tmp = new Science(37,
                     "Raum",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_space",
                     37,
                     "10",
                     "(1440+720*[S37.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S37.ACT]", 2 => "400+1000*[S37.ACT]", 3 => "400+1000*[S37.ACT]");
  
  $tmp->scienceDepList    = array(0 => "1", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[37] = $tmp;

  // Nebel
  $tmp = new Science(38,
                     "Nebel",
                     "<p>Das Erforschen der M&uuml;hstischen Disziplinen bringt nicht nur Wissen &uuml;ber die G&ouml;tter und die Welt, die sie geschaffen haben, sondern auch einigen praktischen Nutzen in Bezug auf die Kriegskunst und das Erwirken von nicht v&ouml;llig erkl&auml;rbaren Ph&auml;nomenen, den sogenannten Wundern.</p>",
                     "science_fog",
                     38,
                     "10",
                     "(1440+720*[S38.ACT])/GREATEST(1,[S0.ACT]-1)");

  $tmp->resourceProductionCost = array(1 => "400+1000*[S38.ACT]", 2 => "400+1000*[S38.ACT]", 3 => "400+1000*[S38.ACT]");
  
  $tmp->scienceDepList    = array(0 => "1", 13 => "1");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1");
  

  $scienceTypeList[38] = $tmp;

  // Mandragoran, Halbgott des Raumes
  $tmp = new Science(39,
                     "Mandragoran, Halbgott des Raumes",
                     "<p>FIXME</p>",
                     "science_mandragoran",
                     39,
                     "10",
                     "(2880+1440*[S39.ACT])/GREATEST(1,[S0.ACT]-5)");

  $tmp->resourceProductionCost = array(1 => "100+1000*[S39.ACT]", 2 => "100+1000*[S39.ACT]", 3 => "100+1000*[S39.ACT]");
  
  $tmp->scienceDepList    = array(0 => "6", 13 => "7", 14 => "0", 15 => "0", 29 => "2", 31 => "0", 37 => "6", 19 => "0", 23 => "0");
  $tmp->maxScienceDepList = array(0 => "-1", 13 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 37 => "-1", 19 => "0", 23 => "0");
  

  $scienceTypeList[39] = $tmp;

}

	
/********************** Unittypes *********************/
class Unit {
  var $unitID;
  var $name;
  var $description;
  var $dbFieldName;
  var $position;
  var $ranking;
  var $productionTimeFunction;
  var $encumbranceList;
  var $visible;

  var $attackRange;
  var $attackAreal;
  var $attackRate;
  var $defenseRate;
  var $hitPoints;

  var $rangedDamageResistance;

  

  var $spyChance     = 0;
  var $spyValue      = 0;
  var $antiSpyChance = 0;
  var $spyQuality    = 0;

  var $foodCost = 1;
  var $wayCost = 1;
  var $resourceProductionCost  = array();
  var $unitProductionCost      = array();
  var $buildingProductionCost  = array();
  var $externalProductionCost  = array();
  var $buildingDepList         = array();
  var $maxBuildingDepList      = array();
  var $defenseSystemDepList    = array();
  var $maxDefenseSystemDepList = array();
  var $resourceDepList         = array();
  var $maxResourceDepList      = array();
  var $scienceDepList          = array();
  var $maxScienceDepList       = array();
  var $unitDepList             = array();
  var $maxUnitDepList          = array();
  var $fuelResourceID          = 0;
  var $fuelFactor              = 0;
  var $nodocumentation         = 0;

  function Unit($unitID, $name, $description, $dbFieldName, $position, $ranking, $productionTimeFunction,
                $attackRange, $attackAreal, $attackRate, $defenseRate, $rangedDamageResistance, $hitPoints,
                $encumbranceList, $visible){
  $this->unitID                 = $unitID;
  $this->name                   = $name;
  $this->description            = $description;
  $this->dbFieldName            = $dbFieldName;
  $this->position               = $position;
  $this->ranking                = $ranking;
  $this->productionTimeFunction = $productionTimeFunction;

  $this->attackRange            = $attackRange;
  $this->attackAreal            = $attackAreal;
  $this->attackRate             = $attackRate;
  $this->defenseRate            = $defenseRate;
  $this->rangedDamageResistance = $rangedDamageResistance;
  $this->hitPoints              = $hitPoints;
  $this->encumbranceList        = $encumbranceList;
  $this->visible                = $visible;
  }
}

function init_units(){

  global $unitTypeList;
  
  // Faustk&auml;mpfer
  $tmp = new Unit(0,
                  "Faustk&auml;mpfer",
                  "<p>Ohne gro&szlig;e Ausbildung wehren sich die H&ouml;hlenbewohner mit ihren blo&szlig;en F&auml;usten gegen Eindringlinge und unternehmen auch mal hin und wieder Besuche bei ihren Nachbarn.</p>",
                  "unit_boxer",
                  0,
                  7,
                  "120",
                  0,
                  0,
                  4,
                  6,
                  6,
                  10,
                  array(0 => "1", 1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.35;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "10");
  

  $unitTypeList[0] = $tmp;

  // Bemanntes Geb&uuml;sch
  $tmp = new Unit(1,
                  "Bemanntes Geb&uuml;sch",
                  "<p>unsichtbar</p><p>Das bemannte Geb&uuml;sch ist eine der fiesesten Standardeinheiten der H&ouml;hlenmenschen. Nicht nur, da&szlig; der K&auml;mpfer aufgrund seiner Tarnung extrem schwer zu entdecken ist, er gl&auml;nzt auch noch durch die ausgefeiltesten hinterh&auml;ltigen Kampftaktiken:</p><p>Im Get&uuml;mmel stellt er vorbeieilenden Gegnern fies ein Bein oder schl&auml;gt ihnen unerwartet von hinten auf den Kopf, nur um sich dann wieder blitzschnell in seiner Tarnung zu verstecken.</p><p>Allerdings leidet die Beweglichkeit des K&auml;mpfers etwas unter dem hohen Tarnungsaufwand.</p>",
                  "unit_walkingShrubbery",
                  1,
                  7,
                  "108-([D0.Act]*[D0.ACT])",
                  0,
                  0,
                  4,
                  8,
                  8,
                  10,
                  array(),
                  0);

  $tmp->foodCost = 0.35;
  $tmp->wayCost  = 1.2;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->spyQuality    = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "20", 2 => "20", 3 => "5");
  
  $tmp->buildingDepList    = array(2 => "2", 4 => "2", 7 => "12");
  $tmp->maxBuildingDepList = array(2 => "-1", 4 => "-1", 7 => "-1");
  
  $tmp->scienceDepList    = array(36 => "1");
  $tmp->maxScienceDepList = array(36 => "-1");
  

  $unitTypeList[1] = $tmp;

  // Neugieriger Pilzsammler
  $tmp = new Unit(2,
                  "Neugieriger Pilzsammler",
                  "<p>Schon sehr fr&uuml;h begannen die Sammler eines Stammes, sich auf bestimmte Dinge zu spezialisieren: Einige waren besonders gut im Auffinden von n&uuml;tzlichen Rohstoffen, manche waren besonders erfolgreich beim Sammeln von Nahrung, und andere spezialisierten sich (mehr oder weniger erfolgreich) auf das Sammeln von Informationen. Einer von diesen ist der neugierige Pilzsammler, der seine Rolle als Nahrungssammler h&auml;ufig nur als Tarnung verwendet, um sich &uuml;berall im Ruhe umsehen zu k&ouml;nnen.</p><p>Dennoch ist er weder unauff&auml;llig noch besonders geschickt darin, so da&szlig; seine Informationen oft ungenau und unzuverl&auml;ssig sind (was damit zusammenh&auml;ngen kann, da&szlig; er gelegentlich von unbekannten Pilzen nascht, die er auf seinen Wanderungen findet).</p>",
                  "unit_fungusGatherer",
                  2,
                  8,
                  "108-([D0.Act]*[D0.ACT])",
                  2,
                  0,
                  2,
                  8,
                  8,
                  10,
                  array(1 => "3"),
                  1);

  $tmp->foodCost = 0.4;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->spyQuality    = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "40", 2 => "15", 3 => "10");
  
  $tmp->buildingDepList    = array(4 => "2", 7 => "12");
  $tmp->maxBuildingDepList = array(4 => "-1", 7 => "-1");
  
  $tmp->scienceDepList    = array(37 => "1");
  $tmp->maxScienceDepList = array(37 => "-1");
  

  $unitTypeList[2] = $tmp;

  // Steinschleuderer
  $tmp = new Unit(3,
                  "Steinschleuderer",
                  "<p>Der Steinschleuderer schleudert bei einem Angriff aus sicherer Entfernung Steine auf die Feinde. Wird er allerdings direkt angegriffen, ist er fast wehrlos. W&auml;hrend der Ausbildung sind etliche Tests und eine gro&szlig;e Anzahl Steine von N&ouml;ten. Je mehr Steinbrecherh&uuml;tten vorhanden sind, um so schneller verl&auml;uft auch die Ausbildung eines Steinschleuderers.</p><p>Der Steinschleuderer kann wie der Kn&uuml;ppelkrieger einen Stammesangeh&ouml;rigen und jeweils drei Ressourceneinheiten mit sich f&uuml;hren.</p>",
                  "unit_stoneThrower",
                  3,
                  9,
                  "108-([D0.Act]*[D0.ACT])",
                  6,
                  0,
                  2,
                  8,
                  8,
                  10,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.45;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "15", 3 => "35");
  
  $tmp->buildingDepList    = array(3 => "8", 4 => "2");
  $tmp->maxBuildingDepList = array(3 => "-1", 4 => "-1");
  
  $tmp->scienceDepList    = array(1 => "1", 3 => "2");
  $tmp->maxScienceDepList = array(1 => "-1", 3 => "-1");
  

  $unitTypeList[3] = $tmp;

  // Amazone
  $tmp = new Unit(4,
                  "Amazone",
                  "<p>FIXME</p>",
                  "unit_amazone",
                  4,
                  8,
                  "108-([D0.Act]*[D0.ACT])",
                  2,
                  0,
                  2,
                  8,
                  8,
                  10,
                  array(),
                  0);

  $tmp->foodCost = 0.4;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "20", 2 => "5", 3 => "35");
  
  $tmp->buildingDepList    = array(4 => "2", 5 => "6");
  $tmp->maxBuildingDepList = array(4 => "-1", 5 => "-1");
  
  $tmp->scienceDepList    = array(35 => "2");
  $tmp->maxScienceDepList = array(35 => "-1");
  

  $unitTypeList[4] = $tmp;

  // Bemannte Transporttrage
  $tmp = new Unit(5,
                  "Bemannte Transporttrage",
                  "<p>Die Trage wird von vier Personen bemannt und eignet sich zum Transport von je 20 Holz, Nahrung und Stein.</p>",
                  "unit_carrier",
                  5,
                  12,
                  "108-([D0.Act]*[D0.ACT])",
                  0,
                  0,
                  0,
                  10,
                  10,
                  25,
                  array(1 => "20", 2 => "20", 3 => "20"),
                  1);

  $tmp->foodCost = 0.6;
  $tmp->wayCost  = 1.4;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "4", 1 => "20", 2 => "45");
  
  $tmp->buildingDepList    = array(1 => "4", 4 => "2");
  $tmp->maxBuildingDepList = array(1 => "-1", 4 => "-1");
  
  $tmp->scienceDepList    = array(34 => "2");
  $tmp->maxScienceDepList = array(34 => "-1");
  

  $unitTypeList[5] = $tmp;

  // Neandertaler
  $tmp = new Unit(6,
                  "Neandertaler",
                  "<p>Nicht mehr direkt verwandt mit den Homo Sapiens, gedenken die letzten lebenden Vertreter dieses aussterbenden Zweiges ihrer gemeinsamen Abstammung vom Homo Erectus und ziehen mit ihnen in den Krieg.</p><p>Nicht ganz so intelligent wie ihre menschlichen Verwandten, sind sie doch erstaunlich widerstandsf&auml;hig.</p>",
                  "unit_neandertaler",
                  6,
                  10,
                  "108-([D0.Act]*[D0.ACT])",
                  0,
                  0,
                  6,
                  15,
                  15,
                  10,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.5;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "50", 2 => "20", 3 => "16");
  
  $tmp->buildingDepList    = array(0 => "3", 4 => "2");
  $tmp->maxBuildingDepList = array(0 => "-1", 4 => "-1");
  
  $tmp->scienceDepList    = array(5 => "1", 33 => "2");
  $tmp->maxScienceDepList = array(5 => "-1", 33 => "-1");
  

  $unitTypeList[6] = $tmp;

  // Fackell&auml;ufer
  $tmp = new Unit(7,
                  "Fackell&auml;ufer",
                  "<p>FIXME</p><p>KAMIKAZE</p>",
                  "unit_torchrunner",
                  7,
                  7,
                  "108-([D0.Act]*[D0.ACT])",
                  0,
                  0,
                  10,
                  2,
                  20,
                  10,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.35;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "10", 2 => "20", 3 => "15");
  
  $tmp->buildingDepList    = array(4 => "3", 11 => "0", 18 => "0", 19 => "0", 20 => "2", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(6 => "3");
  $tmp->maxScienceDepList = array(6 => "-1");
  

  $unitTypeList[7] = $tmp;

  // Schildkr&ouml;te
  $tmp = new Unit(8,
                  "Schildkr&ouml;te",
                  "<p>FIXME</p>",
                  "unit_turtle",
                  8,
                  14,
                  "108-([D0.Act]*[D0.ACT])",
                  0,
                  0,
                  2,
                  20,
                  20,
                  20,
                  array(),
                  1);

  $tmp->foodCost = 0.7;
  $tmp->wayCost  = 1.2;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "30", 2 => "35", 3 => "35");
  
  $tmp->buildingDepList    = array(4 => "3", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "2");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(32 => "3");
  $tmp->maxScienceDepList = array(32 => "-1");
  

  $unitTypeList[8] = $tmp;

  // Berittene Rennschnecke
  $tmp = new Unit(9,
                  "Berittene Rennschnecke",
                  "<p>Vor der Z&auml;hmung der gro&szlig;en Rennschnecke war diese bereits durch ihre fabelhafte Geschwindigkeit und wundersame Wendigkeit ber&uuml;hmt geworden. Dem gro&szlig;en Urvater aller Schamanen M&uuml;h soll der Legende nach die Ehre zuteil geworden sein, die erste Rennschnecke zu z&auml;hmen, was dem beinlosen M&uuml;h zweifelsohne eine erhebliche Erleichterung gewesen sein mu&szlig;. Zugegebenerma&szlig;en war ihm dabei eine der T&ouml;chter Slavomirs - Svanta - eine gro&szlig;e Hilfe, denn auch die Rennschnecken sind Wesen Slavomirs.</p><p>Bemannt mit einem Krieger, der Kiesel nach den Gegnern schmei&szlig;t, handelt es sich um eine gef&auml;hrliche Kampfeinheit.</p>",
                  "unit_snail",
                  9,
                  8,
                  "108-([D0.Act]*[D0.ACT])",
                  4,
                  0,
                  2,
                  8,
                  8,
                  10,
                  array(),
                  1);

  $tmp->foodCost = 0.4;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "50", 2 => "10", 3 => "35");
  
  $tmp->buildingDepList    = array(2 => "8", 4 => "3", 11 => "0", 18 => "0", 19 => "2", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(2 => "-1", 4 => "-1", 11 => "0", 18 => "0", 19 => "-1", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(4 => "1", 28 => "1");
  $tmp->maxScienceDepList = array(4 => "-1", 28 => "-1");
  

  $unitTypeList[9] = $tmp;

  // Garstigen Wurm
  $tmp = new Unit(10,
                  "Garstigen Wurm",
                  "<p>FIXME</p>",
                  "unit_worm",
                  10,
                  1,
                  "54-(0.5 * [D0.Act]*[D0.ACT])",
                  0,
                  5,
                  0,
                  1,
                  1,
                  1,
                  array(),
                  1);

  $tmp->foodCost = 0.05;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "2", 1 => "10", 2 => "10", 3 => "10");
  
  $tmp->buildingDepList    = array(1 => "6", 4 => "3", 11 => "0", 18 => "0", 19 => "2", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(1 => "-1", 4 => "-1", 11 => "0", 18 => "0", 19 => "-1", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(4 => "1", 27 => "1");
  $tmp->maxScienceDepList = array(4 => "-1", 27 => "-1");
  

  $unitTypeList[10] = $tmp;

  // Kn&uuml;ppelkrieger
  $tmp = new Unit(11,
                  "Kn&uuml;ppelkrieger",
                  "<p>Der Kn&uuml;ppelkrieger verbraucht w&auml;hrend der Ausbildung unglaublich viele Kn&uuml;ppel, um die richtige Schlagtechnik zu erlernen. Au&szlig;erdem wird er mit beherzten Steinw&uuml;rfen schmerzunempfindlicher gemacht. Ein Kn&uuml;ppelkrieger kann nicht nur k&auml;mpfen, sondern auch Ressourcen von H&ouml;hle zu H&ouml;hle tragen.</p><p>Er tr&auml;gt dabei bis zu drei Einheiten pro Ressource, allerdings kann er nur einen Stammesangeh&ouml;rigen mitnehmen.</p>",
                  "unit_clubWarrior",
                  11,
                  9,
                  "108-([D0.Act]*[D0.ACT])",
                  0,
                  0,
                  10,
                  8,
                  8,
                  10,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.45;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "20", 2 => "30", 3 => "10");
  
  $tmp->buildingDepList    = array(4 => "3", 6 => "1", 10 => "0", 12 => "0", 13 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "-1", 10 => "0", 12 => "0", 13 => "0");
  
  $tmp->scienceDepList    = array(38 => "3");
  $tmp->maxScienceDepList = array(38 => "-1");
  

  $unitTypeList[11] = $tmp;

  // Rennender Steinschleuderer
  $tmp = new Unit(12,
                  "Rennender Steinschleuderer",
                  "<p>Der Rennende Steinschleuderer ist, wie der Name schon sagt, ein schneller Steinschleuderer.</p><p>Durch die hohe Anlaufgeschwindigkeit erh&ouml;ht sich die Durchschlagskraft der Steine enorm. Die Ausbildung verlangt die Standardausbildung eines Steinschleuderers sowie Training auf der Rennstrecke.</p><p>Der Rennende Steinschleuderer kann wie der Steinschleuderer einen Stammesangeh&ouml;rigen und jeweils drei Ressourceneinheiten mit sich f&uuml;hren.</p>",
                  "unit_runningStoneThrower",
                  12,
                  12,
                  "66-([D0.ACT]+4*([B20.ACT]-3))",
                  10,
                  0,
                  4,
                  10,
                  10,
                  10,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.6;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(2 => "10", 3 => "20");
  
  $tmp->unitProductionCost = array(3 => "1");
  
  $tmp->buildingDepList    = array(3 => "14", 4 => "4", 11 => "0", 18 => "0", 19 => "0", 20 => "3", 23 => "0");
  $tmp->maxBuildingDepList = array(3 => "-1", 4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(1 => "5", 3 => "4", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 26 => "0", 20 => "0");
  $tmp->maxScienceDepList = array(1 => "-1", 3 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 26 => "0", 20 => "0");
  

  $unitTypeList[12] = $tmp;

  // Maus
  $tmp = new Unit(13,
                  "Maus",
                  "<p>FIXME</p>",
                  "unit_mouse",
                  13,
                  2,
                  "102-([D0.ACT]+4*([B13.ACT]-2))",
                  0,
                  0,
                  1,
                  2,
                  2,
                  2,
                  array(),
                  0);

  $tmp->foodCost = 0.1;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "50", 2 => "30", 3 => "15");
  
  $tmp->buildingDepList    = array(2 => "10", 4 => "4", 6 => "0", 10 => "0", 12 => "0", 13 => "2");
  $tmp->maxBuildingDepList = array(2 => "-1", 4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1");
  
  $tmp->scienceDepList    = array(4 => "4", 35 => "4", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 21 => "0", 20 => "0");
  $tmp->maxScienceDepList = array(4 => "-1", 35 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 21 => "0", 20 => "0");
  

  $unitTypeList[13] = $tmp;

  // Esel
  $tmp = new Unit(14,
                  "Esel",
                  "<p>FIXME</p>",
                  "unit_donkey",
                  14,
                  7,
                  "118-([D0.ACT]+4*([B23.ACT]-3))",
                  0,
                  0,
                  1,
                  10,
                  10,
                  10,
                  array(1 => "10", 2 => "10", 3 => "10", 4 => "10", 5 => "10"),
                  1);

  $tmp->foodCost = 0.35;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "50", 2 => "10", 3 => "10");
  
  $tmp->buildingDepList    = array(4 => "4", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "3");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(4 => "4", 34 => "4", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 18 => "0", 22 => "0");
  $tmp->maxScienceDepList = array(4 => "-1", 34 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 18 => "0", 22 => "0");
  

  $unitTypeList[14] = $tmp;

  // Wolf
  $tmp = new Unit(15,
                  "Wolf",
                  "<p>FIXME</p>",
                  "unit_wolf",
                  15,
                  10,
                  "118-([D0.ACT]+4*([B20.ACT]-3))",
                  0,
                  0,
                  10,
                  10,
                  10,
                  10,
                  array(),
                  1);

  $tmp->foodCost = 0.5;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "75", 2 => "30", 3 => "25");
  
  $tmp->buildingDepList    = array(2 => "13", 4 => "4", 11 => "0", 18 => "0", 19 => "0", 20 => "3", 23 => "0");
  $tmp->maxBuildingDepList = array(2 => "-1", 4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(4 => "4", 33 => "4", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 39 => "0", 19 => "0");
  $tmp->maxScienceDepList = array(4 => "-1", 33 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 39 => "0", 19 => "0");
  

  $unitTypeList[15] = $tmp;

  // Holzwurm
  $tmp = new Unit(16,
                  "Holzwurm",
                  "<p>FIXME</p>",
                  "unit_woodworm",
                  16,
                  1,
                  "28-LEAST(27,([D0.ACT]+GREATEST(0,[B1.ACT]-8)))",
                  0,
                  2,
                  0,
                  1,
                  1,
                  1,
                  array(),
                  1);

  $tmp->foodCost = 0.05;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(2 => "25");
  
  $tmp->buildingDepList    = array(1 => "8", 4 => "4", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "3");
  $tmp->maxBuildingDepList = array(1 => "-1", 4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(4 => "4", 36 => "4", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 39 => "0", 23 => "0");
  $tmp->maxScienceDepList = array(4 => "-1", 36 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 39 => "0", 23 => "0");
  

  $unitTypeList[16] = $tmp;

  // Jubjubvogel
  $tmp = new Unit(17,
                  "Jubjubvogel",
                  "<p>FIXME</p>",
                  "unit_cat",
                  17,
                  3,
                  "106-([D0.ACT]+4*([B13.ACT]-2))",
                  0,
                  0,
                  2,
                  4,
                  4,
                  4,
                  array(),
                  1);

  $tmp->foodCost = 0.15;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->spyQuality    = 0;
  $tmp->resourceProductionCost = array(1 => "135", 2 => "20", 3 => "40");
  
  $tmp->buildingDepList    = array(4 => "4", 6 => "0", 10 => "0", 12 => "0", 13 => "2");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1");
  
  $tmp->scienceDepList    = array(4 => "4", 37 => "4", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 19 => "0", 23 => "0");
  $tmp->maxScienceDepList = array(4 => "-1", 37 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 19 => "0", 23 => "0");
  

  $unitTypeList[17] = $tmp;

  // Baumstammramme
  $tmp = new Unit(18,
                  "Baumstammramme",
                  "<p>Bei der Baumstammramme handelt es sich um einen von vier Kriegern getragenen Stamm, der zum Einrei&szlig;en von Bauten und zum Erst&uuml;rmen von Toren verwendet wird.</p>",
                  "unit_woodenRam",
                  18,
                  33,
                  "108-([D0.Act]*[D0.ACT])",
                  0,
                  50,
                  0,
                  40,
                  40,
                  50,
                  array(),
                  1);

  $tmp->foodCost = 1.65;
  $tmp->wayCost  = 1.2;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(2 => "40");
  
  $tmp->unitProductionCost = array(0 => "4");
  
  $tmp->buildingDepList    = array(1 => "12", 4 => "4", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "3");
  $tmp->maxBuildingDepList = array(1 => "-1", 4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(2 => "3", 27 => "4", 14 => "0", 15 => "0", 29 => "0", 31 => "1", 25 => "0");
  $tmp->maxScienceDepList = array(2 => "-1", 27 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1", 25 => "0");
  

  $unitTypeList[18] = $tmp;

  // Berserker
  $tmp = new Unit(19,
                  "Berserker",
                  "<p>Der Berserker ist die gef&uuml;rchteteste menschliche Nahkampfeinheit. Gesch&uuml;tzt durch Schildkr&ouml;tenpanzer, bewaffnet mit Metall&auml;xten und gro&szlig;en, metallbeschlagenen Kn&uuml;ppeln und nat&uuml;rlich ausgestattet mit einem geringen Intellekt metzeln Berserker alles platt, was ihnen in den Weg ger&auml;t.</p><p>Ein besonderer Spa&szlig; ist die tats&auml;chliche Beleidigung von eigenen Brandstiftern. Wenn diese dann ihre t&ouml;dliche Fracht entz&uuml;nden, holen die Berserker mit einem gezielten Schlag aus, um sowohl den Brandstifter als auch den Brandsatz tief in die feindlichen Linien zu schlagen. In vielen Geschichten wurde diese Verhalten f&auml;lschlicherweise als Berserkerwut beschrieben.</p>",
                  "unit_berserk",
                  19,
                  17,
                  "124-([D0.ACT]+4*([B18.ACT]-4))",
                  0,
                  0,
                  20,
                  15,
                  15,
                  15,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.85;
  $tmp->wayCost  = 1.1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "100", 2 => "50", 3 => "20");
  
  $tmp->buildingDepList    = array(4 => "5", 11 => "0", 18 => "4", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "-1", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(5 => "2", 6 => "5", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 26 => "0", 21 => "0");
  $tmp->maxScienceDepList = array(5 => "-1", 6 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 26 => "0", 21 => "0");
  

  $unitTypeList[19] = $tmp;

  // Wasserb&uuml;ffel
  $tmp = new Unit(20,
                  "Wasserb&uuml;ffel",
                  "<p>FIXME</p>",
                  "unit_waterox",
                  20,
                  22,
                  "114-([D0.ACT]+4*([B13.ACT]-3))",
                  0,
                  0,
                  5,
                  30,
                  30,
                  30,
                  array(),
                  1);

  $tmp->foodCost = 1.1;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "60", 2 => "25", 3 => "20", 4 => "2");
  
  $tmp->buildingDepList    = array(4 => "5", 6 => "0", 10 => "0", 12 => "0", 13 => "3");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1");
  
  $tmp->scienceDepList    = array(32 => "5", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 24 => "0", 18 => "0");
  $tmp->maxScienceDepList = array(32 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 24 => "0", 18 => "0");
  

  $unitTypeList[20] = $tmp;

  // Berittener Gepard
  $tmp = new Unit(21,
                  "Berittener Gepard",
                  "<p>Der berittene Gepard ist eine sehr n&uuml;tzliche Einheit, um einen Feind mit der Wellentaktik zu zerm&uuml;rben.</p><p>Mit seiner Schnelligkeit und seinem dennoch hohen Schaden kann der Berittene Gepard im Vergleich zum Kn&uuml;ppelkrieger doppelt so oft angreifen und dem Gegner so keine Verschnaufpausen g&ouml;nnen.</p><p>Die Geschichte des Berittenen Geparden ist wie bei den meisten dressierten Tieren eine &auml;u&szlig;erst blutige. Gerade die Schnelligkeit des Geparden machte es in fr&uuml;heren Zeiten unm&ouml;glich, ihn zu fangen. Erst mit der Entwicklung besserer B&ouml;gen und der Entdeckung kampfunf&auml;hig machender Gifte gelang es, den Geparden einzufangen. Dennoch verging bis zur endg&uuml;ltigen Dressur noch eine lange Zeit.</p><p>Auch heute k&ouml;nnen nur ausgebildete und sehr leichte Krieger einen Geparden reiten. Dabei wird der Gepard durch sanften Druck mit den Hacken gelenkt. Zu starker Druck f&uuml;hrt meist zum Abwurf und einer kostenlosen kosmetischen Behandlung durch den Geparden. In friedlichen Zeiten vollf&uuml;hren die Dompteure Kunstst&uuml;cke zur Freude der kleinen und gro&szlig;en Kinder.</p>",
                  "unit_gepard",
                  21,
                  10,
                  "120-([D0.ACT]+4*([B20.ACT]-4))",
                  0,
                  0,
                  10,
                  10,
                  10,
                  10,
                  array(),
                  1);

  $tmp->foodCost = 0.5;
  $tmp->wayCost  = 0.6;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "95", 2 => "25", 3 => "20", 4 => "2");
  
  $tmp->buildingDepList    = array(4 => "5", 11 => "0", 18 => "0", 19 => "0", 20 => "4", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(10 => "2", 28 => "5", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 19 => "0", 24 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 28 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 19 => "0", 24 => "0");
  

  $unitTypeList[21] = $tmp;

  // Nebelgeist
  $tmp = new Unit(22,
                  "Nebelgeist",
                  "<p>FIXME</p>",
                  "unit_fogghost",
                  22,
                  12,
                  "107-([D0.ACT]+4*([B18.ACT]-4))",
                  0,
                  0,
                  12,
                  10,
                  10,
                  15,
                  array(),
                  0);

  $tmp->foodCost = 0.6;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "5", 1 => "100", 2 => "25", 3 => "20", 4 => "2");
  
  $tmp->buildingDepList    = array(4 => "5", 11 => "0", 18 => "4", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "-1", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(38 => "5", 37 => "3", 14 => "0", 15 => "0", 29 => "0", 31 => "1", 17 => "0");
  $tmp->maxScienceDepList = array(38 => "-1", 37 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1", 17 => "0");
  

  $unitTypeList[22] = $tmp;

  // Bogensch&uuml;tze
  $tmp = new Unit(23,
                  "Bogensch&uuml;tze",
                  "<p>Der Bogensch&uuml;tze ist eine der klassischen Kampfeinheiten und fehlt in keiner gutsortierten Armee.</p><p>Ausgestattet mit einem Bogen bestehend aus einem Mammutzahn oder einem kr&auml;ftigen Ast schie&szlig;t der Bogensch&uuml;tze mit Pfeilen, deren Spitze aus spitzen Steinen besteht.</p><p>&Uuml;ber den Bogensch&uuml;tzen an sich gibt es nicht viel zu berichten. Au&szlig;er nat&uuml;rlich seiner komischen Kleidung. Aus unerfindlichen Gr&uuml;nden tragen alle Bogensch&uuml;tzen nicht die &uuml;bliche Felle, sondern eher d&uuml;nne Stoffe, die stark an Strumpfhosen erinnern.</p><p>In grauer Vorzeit war ein Bogensch&uuml;tze namens Roben Hut zu zweifelhaftem Ruhm gelangt, als er mit einer Horde komischer Gesellen im Wald wohnte und versucht hat, die potth&auml;&szlig;liche Stammesf&uuml;hrertochter Marianne aus den F&auml;ngen eines ehrenwerten Mannes zu rei&szlig;en und dabei f&uuml;r seine Zwecke die Armen ausbeutete. Einige &Auml;ltere erz&auml;hlen diese Geschichte genau andersherum, aber Geschichte wird nun einmal von Siegern geschrieben.</p><p>Ein Bogensch&uuml;tze kann drei Einheiten von jeder Ressource transportieren.</p>",
                  "unit_archer",
                  23,
                  14,
                  "120-(4*([B11.ACT]-5)+[D0.ACT])",
                  15,
                  0,
                  2,
                  10,
                  10,
                  10,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.7;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "30", 2 => "40", 3 => "12", 4 => "2");
  
  $tmp->buildingDepList    = array(4 => "6", 11 => "5", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "-1", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(3 => "6", 8 => "2", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 20 => "0", 26 => "0");
  $tmp->maxScienceDepList = array(3 => "-1", 8 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 20 => "0", 26 => "0");
  

  $unitTypeList[23] = $tmp;

  // Fledermaus
  $tmp = new Unit(24,
                  "Fledermaus",
                  "<p>unsichtbar</p><p>FIXME</p>",
                  "unit_bat",
                  24,
                  3,
                  "70-(4*([B18.ACT]-5)+[D0.ACT])",
                  0,
                  0,
                  2,
                  4,
                  4,
                  4,
                  array(),
                  0);

  $tmp->foodCost = 0.15;
  $tmp->wayCost  = 0.5;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->spyQuality    = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "10", 2 => "10", 3 => "10", 5 => "2");
  
  $tmp->unitProductionCost = array(13 => "1");
  
  $tmp->buildingDepList    = array(4 => "6", 11 => "0", 18 => "5", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "-1", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(10 => "2", 35 => "6", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 20 => "0", 21 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 35 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 20 => "0", 21 => "0");
  

  $unitTypeList[24] = $tmp;

  // Riesenschildkr&ouml;te
  $tmp = new Unit(25,
                  "Riesenschildkr&ouml;te",
                  "<p>FIXME</p>",
                  "unit_giantturtle",
                  25,
                  24,
                  "60-(4*([B23.ACT]-5)+[D0.ACT])",
                  0,
                  0,
                  3,
                  40,
                  40,
                  30,
                  array(),
                  1);

  $tmp->foodCost = 1.2;
  $tmp->wayCost  = 1.2;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "130", 2 => "18", 3 => "2");
  
  $tmp->unitProductionCost = array(8 => "1");
  
  $tmp->buildingDepList    = array(4 => "6", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "5");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(10 => "3", 32 => "6", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 18 => "0", 24 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 32 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 18 => "0", 24 => "0");
  

  $unitTypeList[25] = $tmp;

  // Rollender B.U.S.
  $tmp = new Unit(26,
                  "Rollender B.U.S.",
                  "<p>Die Abk&uuml;rzung rollender B.U.S. steht f&uuml;r rollender Breiter Untersatz aus Stein, wobei der Name Programm ist. Das gerade erfundene Prinzip der Verwendung von Baumst&auml;mmen als Unterlegrollen f&uuml;r schwere Gegenst&auml;nde wurde hier optimiert und durch ein stabiles Chassis erg&auml;nzt. Mit dieser Kombination stellt der B.U.S. einen deutlichen Fortschritt in Hinblick auf Transportkapazit&auml;t und Komfort im Transportwesen dar. Gerade bei den Strecken vom Gipfel ins Tal kann er sein volles Potential ausspielen.</p>",
                  "unit_rollingCarrier",
                  26,
                  13,
                  "60-(4*([B20.ACT]-5)+[D0.ACT])",
                  0,
                  0,
                  0,
                  20,
                  20,
                  20,
                  array(1 => "50", 2 => "50", 3 => "50"),
                  1);

  $tmp->foodCost = 0.65;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(3 => "80");
  
  $tmp->unitProductionCost = array(5 => "1");
  
  $tmp->buildingDepList    = array(4 => "6", 11 => "0", 18 => "0", 19 => "0", 20 => "5", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(7 => "5", 34 => "6", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 22 => "0", 18 => "0");
  $tmp->maxScienceDepList = array(7 => "-1", 34 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 22 => "0", 18 => "0");
  

  $unitTypeList[26] = $tmp;

  // Durchtrainierte Rennschnecke
  $tmp = new Unit(27,
                  "Durchtrainierte Rennschnecke",
                  "<p>Aus Langeweile und voller Neid auf den Erfolg ihrer Schwester Svanta fing Lovina, die j&uuml;ngste Tochter Slavomirs, damit an, die berittenen Rennschnecken durch gezielte W&uuml;rfe von Holzst&uuml;cken und Steinen zu necken. Diese wu&szlig;ten sich nicht anders zu helfen als st&auml;ndig ihren gezielten W&uuml;rfen auszuweichen. Nach kurzer Zeit erlangten sie eine Wendig- und Schnelligkeit, die sie von den Berittenen Rennschnecken deutlich unterschied.</p>",
                  "unit_trainedSnail",
                  27,
                  11,
                  "70-(4*([B19.ACT]-5)+[D0.ACT])",
                  8,
                  0,
                  4,
                  10,
                  10,
                  10,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.55;
  $tmp->wayCost  = 0.6;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "120", 2 => "5", 3 => "5");
  
  $tmp->unitProductionCost = array(9 => "1");
  
  $tmp->buildingDepList    = array(4 => "6", 11 => "0", 18 => "0", 19 => "5", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "0", 19 => "-1", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(1 => "4", 10 => "3", 28 => "6", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 22 => "0", 24 => "0");
  $tmp->maxScienceDepList = array(1 => "-1", 10 => "-1", 28 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 22 => "0", 24 => "0");
  

  $unitTypeList[27] = $tmp;

  // Gepanzerter Neandertaler
  $tmp = new Unit(28,
                  "Gepanzerter Neandertaler",
                  "<p>Im Laufe der Karriere kann ein Neandertaler sich durch Leistungen im Kampf eine R&uuml;stung und eine bessere Keule verdienen.</p><p>Dennoch stehen die Neandertaler meist in den vorderen Reihen und sind eher als Ablenkung gedacht, als im Kampf wirklich von Bedeutung zu sein. Grund daf&uuml;r ist haupts&auml;chlich die geringe Intelligenz der Neandertaler, die die Kampftaktik einfach nicht verstehen und immer die veraltete \"Hau-drauf-wenn&#39;s-sich-bewegt\"-Taktik anwenden.</p>",
                  "unit_armoredNeandertaler",
                  28,
                  18,
                  "54-(4*([B10.ACT]-4)+[D0.ACT])",
                  0,
                  0,
                  10,
                  30,
                  30,
                  15,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.9;
  $tmp->wayCost  = 1.2;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "100", 2 => "10", 3 => "30");
  
  $tmp->unitProductionCost = array(6 => "1");
  
  $tmp->buildingDepList    = array(0 => "5", 4 => "6", 6 => "0", 10 => "4", 12 => "0", 13 => "0");
  $tmp->maxBuildingDepList = array(0 => "-1", 4 => "-1", 6 => "0", 10 => "-1", 12 => "0", 13 => "0");
  
  $tmp->scienceDepList    = array(5 => "4", 33 => "6", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 39 => "0", 19 => "0");
  $tmp->maxScienceDepList = array(5 => "-1", 33 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 39 => "0", 19 => "0");
  

  $unitTypeList[28] = $tmp;

  // Erwachter Wald
  $tmp = new Unit(29,
                  "Erwachter Wald",
                  "<p>unsichtbar</p><p>Erwachte W&auml;lder befinden sich h&auml;ufig in der N&auml;he von H&ouml;hlen der Anh&auml;nger Ugas. Obwohl klar und deutlich als Wald identifiziert, f&uuml;hrt er doch ein unheimliches Eigenleben. H&auml;ufig wechselt der Wald &uuml;ber Nacht seinen Standort und taucht unverhofft bei Tagesanbruch mitten in einem vorher frei passierbaren Pfad auf. Die Anh&auml;nger Ugas lenken die Verfluchten W&auml;lder, indem sie junge Hasen an ein Seil binden. Am n&auml;chsten Tag steht der Wald genau da, wo das H&auml;schen angebunden war.</p>",
                  "unit_sentientForest",
                  29,
                  15,
                  "70-(4*([B19.ACT]-5)+[D0.ACT])",
                  0,
                  0,
                  10,
                  20,
                  20,
                  15,
                  array(),
                  1);

  $tmp->foodCost = 0.75;
  $tmp->wayCost  = 1.2;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->spyQuality    = 0;
  $tmp->resourceProductionCost = array(2 => "50", 6 => "2");
  
  $tmp->unitProductionCost = array(1 => "1");
  
  $tmp->buildingDepList    = array(1 => "12", 4 => "6", 11 => "0", 18 => "0", 19 => "5", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(1 => "-1", 4 => "-1", 11 => "0", 18 => "0", 19 => "-1", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(5 => "7", 36 => "6", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 39 => "0", 23 => "0");
  $tmp->maxScienceDepList = array(5 => "-1", 36 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 39 => "0", 23 => "0");
  

  $unitTypeList[29] = $tmp;

  // Druide
  $tmp = new Unit(30,
                  "Druide",
                  "<p>Nachts versammeln sich an den Steinkreisen im Tal immer wieder in lange Gew&auml;ndern geh&uuml;llten Gestalten und laufen die ganze Nacht mit Fackeln in der Hand im Kreis murmelnd und singend drumherum. Seit Ihr angefangen habt, Euch f&uuml;r ihre Riten zu interessieren, sind ein paar sogar bereit, f&uuml;r einen ordentlichen Haufen Schwefelbrocken (an denen sie &uuml;brigens gleich mit rollenden Augen schn&uuml;ffeln), f&uuml;r Euch ein paar Dinge zu erledigen. Erstaunliche Leute, ihr ewiges im-Kreis-Laufen und ihr monotoner Singsang haben sie derart abgeh&auml;rtet, dass selbst eure Neandertaler neidisch sind. Essen wollen sie anscheinend auch kaum etwas und zu allem &Uuml;berfluss macht es den Anschein, dass die Natur selbst ihnen im Unterholz den Weg ebnet.</p><p>Allerdings hat diese N&auml;he zur Natur auch ihre T&uuml;cken. Die Erdw&auml;lle abzutragen und die Rattengruben zuzusch&uuml;tten konntet ihr ihnen ja noch austreiben. Dumm ist alleridngs, dass sie sich strikt weigern, nicht mehr auf Eure Arbeiter einzupr&uuml;geln. Das w&auml;re Raubbau an Mutter Natur. Solange ihr aber nur ihre Geschenke sammelt, haben sie keine Einw&auml;nde.</p><p>Hinweis: Diese Einheit kann sicherlich zu mehr dienen, als nur zum k&auml;mpfen. Die enge Beziehung des Druiden zur Natur k&ouml;nnte sich vielleicht irgendwann als sehr n&uuml;tzlich erweisen....</p>",
                  "unit_druid",
                  30,
                  12,
                  "70-(4*([B19.ACT]-5)+[D0.ACT])",
                  8,
                  0,
                  4,
                  12,
                  12,
                  10,
                  array(),
                  1);

  $tmp->foodCost = 0.6;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "100", 2 => "15", 3 => "10", 4 => "3", 5 => "1");
  
  $tmp->unitProductionCost = array(2 => "1");
  
  $tmp->buildingDepList    = array(7 => "18", 4 => "6", 11 => "5", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(7 => "-1", 4 => "-1", 11 => "-1", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(37 => "6", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 19 => "0", 23 => "0");
  $tmp->maxScienceDepList = array(37 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 19 => "0", 23 => "0");
  

  $unitTypeList[30] = $tmp;

  // Elefant
  $tmp = new Unit(31,
                  "Elefant",
                  "<p>FIXME</p>",
                  "unit_elephant",
                  31,
                  29,
                  "120-(4*([B23.ACT]-5)+[D0.ACT])",
                  0,
                  15,
                  5,
                  50,
                  50,
                  30,
                  array(1 => "6", 2 => "6", 3 => "6", 4 => "1", 5 => "1"),
                  1);

  $tmp->foodCost = 1.45;
  $tmp->wayCost  = 1.1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "5", 1 => "100", 2 => "10", 3 => "10", 4 => "5");
  
  $tmp->buildingDepList    = array(4 => "6", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "5");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(6 => "6", 27 => "6", 14 => "0", 15 => "0", 29 => "0", 31 => "1", 25 => "0");
  $tmp->maxScienceDepList = array(6 => "-1", 27 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1", 25 => "0");
  

  $unitTypeList[31] = $tmp;

  // Rennender Kn&uuml;ppelkrieger
  $tmp = new Unit(32,
                  "Rennender Kn&uuml;ppelkrieger",
                  "<p>Die gepanzerten Kn&uuml;ppelkrieger sind die von Aggas Anh&auml;ngern durch Schildkr&ouml;tenpanzer verbesserte Version der alt bekannten Kn&uuml;ppelkrieger. Zu einem Gepanzerten Kn&uuml;ppelkrieger werden nur kampferprobte Kn&uuml;ppelkrieger bef&ouml;rdert.</p><p>Unter den Gepanzerten Kn&uuml;ppelkriegern gilt ein aus einem Ast des Schnellwuchernden Gestr&uuml;pps hergestellter Kn&uuml;ppel als besonderes Statussymbol, das sich allerdings die Wenigsten leisten k&ouml;nnen. Dabei fehlt es nicht an Reichtum, sondern an Kraft und Ausdauer.</p>",
                  "unit_runningClubWarrior",
                  32,
                  12,
                  "84-(4*([B20.ACT]-5)+[D0.ACT])",
                  0,
                  0,
                  15,
                  10,
                  10,
                  10,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 0.6;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(2 => "10", 3 => "10");
  
  $tmp->unitProductionCost = array(11 => "1");
  
  $tmp->buildingDepList    = array(4 => "6", 6 => "4", 10 => "0", 12 => "0", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "5", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "-1", 10 => "0", 12 => "0", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(38 => "6", 14 => "0", 15 => "0", 29 => "0", 31 => "1", 17 => "0");
  $tmp->maxScienceDepList = array(38 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1", 17 => "0");
  

  $unitTypeList[32] = $tmp;

  // Brandstifter
  $tmp = new Unit(33,
                  "Brandstifter",
                  "<p>Kamikaze</p><p>Der Brandstifter ist ein typisches Beispiel f&uuml;r die nat&uuml;rliche Selektion. Die Starken und die Schwerf&auml;lligen schlagen die Laufbahn eines Nahk&auml;mpfers ein. Die Beweglichen und die Behenden werden Fernk&auml;mpfer. Die kleinen schlauen K&auml;mpfer reiten irgendwelche Tiere. Die ganz Schlauen &uuml;bernehmen die Taktikf&uuml;hrung im Kampf vom R&uuml;cken der Brontosaurier.</p><p>Und die kleinen dummen... die werden Brandstifter. Sobald ein K&auml;mpfer das zweite Mal von einem Tier gefallen ist, wird er in einen dunklen Zwinger gesperrt. Erst vor dem Kampf werden die Brandstifter wieder herausgelassen. Der kleine dumme K&auml;mpfer bekommt eine Fackel und ein wenig Schwefel in die Hand. Dann wird ihm gesagt, da&szlig; ein bestimmter gegnerischer Krieger b&ouml;se Dinge &uuml;ber seine Mutter gesagt h&auml;tte und schon ist man das Problem des dummen Jungen und dazu auch noch einen Feind los.</p><p>Warnung: Aus Sicherheitsgr&uuml;nden sei hier explizit darauf hingewiesen, dem Brandstifter die Fackel und den Schwefel erst direkt vor Beginn des Kampfes auszuh&auml;ndigen!</p>",
                  "unit_arsonist",
                  33,
                  14,
                  "56-(4*([B20.ACT]-6)+[D0.ACT])",
                  0,
                  0,
                  30,
                  2,
                  20,
                  10,
                  array(1 => "2", 2 => "2", 3 => "2"),
                  1);

  $tmp->foodCost = 0.7;
  $tmp->wayCost  = 1.0;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "10", 2 => "3", 3 => "1", 5 => "1");
  
  $tmp->unitProductionCost = array(7 => "1");
  
  $tmp->buildingDepList    = array(4 => "7", 7 => "20", 11 => "0", 18 => "0", 19 => "0", 20 => "6", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 7 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(6 => "7", 12 => "2", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 21 => "0", 26 => "0");
  $tmp->maxScienceDepList = array(6 => "-1", 12 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 21 => "0", 26 => "0");
  

  $unitTypeList[33] = $tmp;

  // Baumhirte
  $tmp = new Unit(34,
                  "Baumhirte",
                  "<p>unsichtbar</p><p>Die Baumhirten sind die gr&ouml;&szlig;ten und st&auml;rksten der V&ouml;lker. Ihre Gliedma&szlig;en sind &auml;u&szlig;erst stabil, sie k&ouml;nnen Stein und Stahl zerrei&szlig;en, sobald sie entsprechend angestachelt sind - ein Anblick, der zwar selten ist, aber nur von wenigen gerne gesehen wird. Sie sind von Natur aus gutm&uuml;tig, denken recht langsam und handeln nie unbesonnen, es sei denn, sie sind unglaublich ver&auml;rgert.</p><p>Obwohl sie das &auml;lteste aller V&ouml;lker sind, schliefen die Baumhirten eine lange Zeit, bis sie vom Rufe Ugas geweckt wurden, ihr Schicksal im anbrechenden Zeitalter zu erf&uuml;llen. Die Baumhirten sind ein schwindendes Volk, teilweise, weil sie sich aus &Uuml;berdru&szlig;, Achtlosigkeit oder Bitterkeit in ihre schlafende Baumform zur&uuml;ckgezogen haben. Ein anderer Grund ist das Verschwinden der Baumhirtenfrauen, die sich innerhalb vieler Jahre von ihren M&auml;nnern getrennt haben und aus der Geschichte verschwunden sind.</p><p>Die Baumhirten k&ouml;nnen sich nahezu unsichtbar bewegen und (wie Aggas tollw&uuml;tige Flederm&auml;use) vom Gegner unbemerkt einer feindlichen H&ouml;hle n&auml;hern. Ein Baumhirte kann keine Ressourcen tragen.</p>",
                  "unit_treeHerdsman",
                  34,
                  15,
                  "86-(4*([B23.ACT]-6)+[D0.ACT])",
                  0,
                  0,
                  10,
                  20,
                  20,
                  15,
                  array(),
                  1);

  $tmp->foodCost = 0.75;
  $tmp->wayCost  = 1.2;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->spyQuality    = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "70", 2 => "100");
  
  $tmp->buildingDepList    = array(4 => "7", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "6");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(36 => "7", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 23 => "0", 39 => "0");
  $tmp->maxScienceDepList = array(36 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 23 => "0", 39 => "0");
  

  $unitTypeList[34] = $tmp;

  // Banderschnatz
  $tmp = new Unit(35,
                  "Banderschnatz",
                  "<p>FIXME</p>",
                  "unit_hawk",
                  35,
                  5,
                  "106-(4*([B18.ACT]-6)+[D0.ACT])",
                  0,
                  0,
                  6,
                  4,
                  4,
                  6,
                  array(),
                  1);

  $tmp->foodCost = 0.25;
  $tmp->wayCost  = 0.6;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "100", 2 => "35", 3 => "25", 4 => "2", 5 => "2");
  
  $tmp->buildingDepList    = array(4 => "7", 11 => "0", 18 => "6", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "0", 18 => "-1", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(37 => "7", 10 => "3", 28 => "5", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 19 => "0", 23 => "0");
  $tmp->maxScienceDepList = array(37 => "-1", 10 => "-1", 28 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 19 => "0", 23 => "0");
  

  $unitTypeList[35] = $tmp;

  // Steinbrockenkegler
  $tmp = new Unit(36,
                  "Steinbrockenkegler",
                  "<p>Die Steinbrockenkegler erfreuen sich gro&szlig;er Beliebtheit. Zum einen haben sie im Kampf einen gewissen Sicherheitsabstand zur Kampflinie und zum anderen sind ihre w&ouml;chentlichen Wettk&auml;mpfe eine der Hauptattraktionen in den H&ouml;hlen Aggas. Au&szlig;erdem geh&ouml;ren die Besten von ihnen zu denjenigen, die bei der totalen Sonnenfinsternis am Schleimklumpenbowling teilnehmen d&uuml;rfen.</p><p>Im Kampf rollen die Steinbrockenkegler gr&ouml;&szlig;ere kugel&auml;hnliche Steine in Richtung der feindlichen Linien. H&auml;ufig l&auml;uft dabei ein Junge mit einer Steintafel hinter Ihnen her, um die Zahl der Opfer zu notieren und die selten vorkommenden \"Pudel\", d.h. ein Wurf ohne Opfer, festzuhalten. Als Belohnung f&uuml;r mehrere \"Pudel\" darf derjenige Steinbrockenkegler das Schleimklumpenbowling bei der n&auml;chsten Sonnenfinsternis aus einer v&ouml;llig neuen Perspektive sehen.</p>",
                  "unit_brickBowler",
                  36,
                  20,
                  "116-(4*([B20.ACT]-6)+0.5*([B3.ACT]-16)+[D0.ACT])",
                  20,
                  0,
                  4,
                  15,
                  20,
                  15,
                  array(1 => "2", 2 => "2", 3 => "2"),
                  1);

  $tmp->foodCost = 1;
  $tmp->wayCost  = 1.1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "20", 2 => "2", 3 => "80");
  
  $tmp->buildingDepList    = array(3 => "16", 4 => "8", 11 => "0", 18 => "0", 19 => "0", 20 => "6", 23 => "0");
  $tmp->maxBuildingDepList = array(3 => "-1", 4 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(1 => "7", 3 => "8", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 20 => "0", 26 => "0");
  $tmp->maxScienceDepList = array(1 => "-1", 3 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 20 => "0", 26 => "0");
  

  $unitTypeList[36] = $tmp;

  // Riese
  $tmp = new Unit(37,
                  "Riese",
                  "<p>FIXME</p>",
                  "unit_giant",
                  37,
                  37,
                  "106-(4*([B11.ACT]-6)+[D0.ACT])",
                  5,
                  0,
                  5,
                  50,
                  50,
                  50,
                  array(1 => "8", 2 => "8", 3 => "8"),
                  1);

  $tmp->foodCost = 1.85;
  $tmp->wayCost  = 1.1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "200", 2 => "80", 3 => "80", 4 => "20", 5 => "10");
  
  $tmp->buildingDepList    = array(4 => "8", 11 => "6", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 11 => "-1", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(6 => "5", 32 => "8", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 18 => "0", 24 => "0");
  $tmp->maxScienceDepList = array(6 => "-1", 32 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 18 => "0", 24 => "0");
  

  $unitTypeList[37] = $tmp;

  // S&auml;belzahntiger
  $tmp = new Unit(38,
                  "S&auml;belzahntiger",
                  "<p>Die Z&auml;hmung der urzeitlichen S&auml;belzahntiger ist keine leichte Aufgabe und hat schon viele gute Krieger das Leben gekostet.</p><p>Lediglich in den ersten beiden Lebensjahren ist ein S&auml;belzahntiger durch die Uga-Anh&auml;nger unter Anrufung von Slavomir, dem Halbgott des Windes, dressierbar. Deshalb werden weibliche Tiger gefangen und mit den gr&ouml;&szlig;ten und st&auml;rksten m&auml;nnlichen Tigern in einen von rotem Fackelschein erleuchteten Zwinger gesteckt. Keine 5 Minuten sp&auml;ter schl&auml;ft der m&auml;nnliche Tiger und das Weibchen guckt ziemlich ver&auml;rgert auf ihn herab. Die so gez&uuml;chteten Jungen werden von speziell ausgebildeten Dompteuren dressiert.</p><p>Viele halbstarke Jugendliche sehen das Betreten eines K&auml;figs mit einem S&auml;belzahntigerjungen als Mutprobe an, um den M&auml;dels zu imponieren. Dadurch haben viele &auml;u&szlig;erst mutige Jungen den Tod gefunden und den M&auml;dchen eine Beziehung mit wesentlich intelligenteren Jungen beschert.</p><p>Wenn die Abrichtung der S&auml;belzahntiger erfolgreich ist, k&ouml;nnen die Tiere als furchterregende Waffe im Kampf eingesetzt werden. Der berittene S&auml;belzahntiger ist eine der gef&auml;hrlichsten Nahkampfeinheiten, da er sehr schnell angreifen und dabei gro&szlig;en Schaden anrichten kann.</p>",
                  "unit_sabertooth",
                  38,
                  17,
                  "54-(4*([B13.ACT]-4)+[D0.ACT])",
                  0,
                  0,
                  15,
                  20,
                  20,
                  15,
                  array(),
                  1);

  $tmp->foodCost = 0.85;
  $tmp->wayCost  = 0.4;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "200", 2 => "10", 3 => "5", 4 => "5", 5 => "12");
  
  $tmp->unitProductionCost = array(21 => "1");
  
  $tmp->buildingDepList    = array(4 => "8", 6 => "0", 10 => "0", 12 => "0", 13 => "4", 11 => "0", 18 => "0", 19 => "0", 20 => "6", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(28 => "5", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 22 => "0", 24 => "0");
  $tmp->maxScienceDepList = array(28 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 22 => "0", 24 => "0");
  

  $unitTypeList[38] = $tmp;

  // Elite-Steinschleuderer
  $tmp = new Unit(39,
                  "Elite-Steinschleuderer",
                  "<p>FIXME</p>",
                  "unit_eliteStoneThrower",
                  39,
                  23,
                  "70-([D0.ACT]+10*([B10.ACT]-6))",
                  20,
                  0,
                  4,
                  25,
                  25,
                  15,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 1.15;
  $tmp->wayCost  = 1.0;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "20", 2 => "2", 3 => "65", 4 => "10", 6 => "3");
  
  $tmp->unitProductionCost = array(12 => "1");
  
  $tmp->buildingDepList    = array(3 => "20", 4 => "9", 6 => "0", 10 => "6", 12 => "0", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "7", 23 => "0");
  $tmp->maxBuildingDepList = array(3 => "-1", 4 => "-1", 6 => "0", 10 => "-1", 12 => "0", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(3 => "9", 5 => "8", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 21 => "1", 20 => "0", 26 => "0");
  $tmp->maxScienceDepList = array(3 => "-1", 5 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 21 => "-1", 20 => "0", 26 => "0");
  

  $unitTypeList[39] = $tmp;

  // Gepanzerter Eliteberserker
  $tmp = new Unit(40,
                  "Gepanzerter Eliteberserker",
                  "<p>Der Gepanzerte Eliteberserker ist die n&auml;chste Stufe der Berserkerlaufbahn, die allerdings nur von wenigen erreicht wird. Kaum verwunderlich ist, da&szlig; nicht unbedingt die tapfersten, sondern eher die zur&uuml;ckhaltenden Berserker diese Stufe erreichen.</p><p>Vor allem die Panzerung wird durch ein aufwendiges Verfahren noch weiter verbessert, aber auch die Waffen sind im Vergleich zu den normalen Gepanzerten Berserkern deutlich verbessert worden. Mit am G&uuml;rtel h&auml;ngenden Wurf&auml;xten und selbst Geb&auml;ude besch&auml;digenden Kn&uuml;ppeln aus dem Holz des schnellwuchernden Dornengestr&uuml;pps sind die Eliteberserker die ultimative Nahkampfallzweckwaffe.</p><p>Besonderes Kennzeichen ist die aufgemalte Zielscheibe in H&ouml;he des Herzens. Sobald ein Pfeil an ihrer R&uuml;stung abprallt, rufen die Eliteberserker \"Hey, das kitzelt!\" oder auch \"Nur heute! Drei Treffer, freie Auswahl!\", womit meist die freie Wahl der abzuschlagenden Gliedma&szlig;en gemeint ist.</p>",
                  "unit_eliteBerserk",
                  40,
                  27,
                  "70-([D0.ACT]+10*([B10.ACT]-6))",
                  0,
                  0,
                  30,
                  30,
                  30,
                  20,
                  array(),
                  1);

  $tmp->foodCost = 1.35;
  $tmp->wayCost  = 1.0;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "90", 2 => "20", 3 => "30", 4 => "10", 5 => "3", 6 => "6");
  
  $tmp->unitProductionCost = array(19 => "1");
  
  $tmp->buildingDepList    = array(4 => "9", 6 => "0", 10 => "6", 12 => "0", 13 => "0", 11 => "0", 18 => "7", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "-1", 12 => "0", 13 => "0", 11 => "0", 18 => "-1", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(6 => "9", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 20 => "1", 21 => "0", 26 => "0");
  $tmp->maxScienceDepList = array(6 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 20 => "-1", 21 => "0", 26 => "0");
  

  $unitTypeList[40] = $tmp;

  // Abtr&uuml;nnige Vampirfledermaus
  $tmp = new Unit(41,
                  "Abtr&uuml;nnige Vampirfledermaus",
                  "<p>unsichtbar</p><p>Gerade in den H&ouml;hlen der Agga-Anbeter finden sich fast immer eine Menge Flederm&auml;use dieser Gattung. Warum gerade bei dieser Menschengattung ist eigentlich nicht n&auml;her bekannt, aber alles, was mit dem Gott Agga zu tun hat, scheint die blutsaugenden Biester anzuziehen. Interessanterweise gaben die Wanderprediger Ugas diesen Flederm&auml;usen ihren heutigen Namen, denn es befinden sich h&auml;ufig auch ehemals gel&auml;uterte Vampirflederm&auml;use darunter, die den entsetzlichen Qu&auml;lereien der Wanderprediger Ugas entkommen konnten.</p><p>W&auml;hrend die Flederm&auml;use zu Anfang bei den Agga-Anbetern nur als l&auml;stiges (und manchmal auch t&ouml;dliches) &Uuml;bel betrachtet wurden, wurde mit der Verf&uuml;gbarkeit von Schwefel ein m&auml;chtiges Mittel zur Kontrolle der federlosen Flieger in die H&auml;nde der b&ouml;sen Menschen gelegt, denn scheinbar k&ouml;nnen die Flederm&auml;use - aus welchen Gr&uuml;nden auch immer - den wohlriechenden Duft von Schwefel nicht ertragen.</p><p>Mit etwas &Uuml;bung und reichlich Schwefel sind die Gefolgsleute des Schattens in der Lage, die Flederm&auml;use zu z&auml;hmen und gezielt gegen ihre Feinde einzusetzen. Ihre F&auml;higkeiten im Kampf sind zwar &auml;u&szlig;erst beschr&auml;nkt, allerdings sind sie als Sp&auml;her fast un&uuml;bertroffen.</p>",
                  "unit_shadowbat",
                  41,
                  6,
                  "60-([D0.ACT]+10*([B13.ACT]-6))",
                  0,
                  0,
                  4,
                  8,
                  8,
                  6,
                  array(),
                  0);

  $tmp->foodCost = 0.3;
  $tmp->wayCost  = 0.2;$tmp->spyValue      = 6;
  $tmp->spyChance     = 1.2;
  $tmp->antiSpyChance = 0.6;
  $tmp->spyQuality    = 0.8;
  $tmp->resourceProductionCost = array(1 => "60", 2 => "35", 3 => "50", 5 => "8", 6 => "4");
  
  $tmp->unitProductionCost = array(24 => "1");
  
  $tmp->buildingDepList    = array(4 => "9", 6 => "0", 10 => "0", 12 => "0", 13 => "6", 11 => "0", 18 => "7", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1", 11 => "0", 18 => "-1", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(10 => "9", 35 => "9", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 26 => "1", 20 => "0", 21 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 35 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 26 => "-1", 20 => "0", 21 => "0");
  

  $unitTypeList[41] = $tmp;

  // Brontosaurier
  $tmp = new Unit(42,
                  "Brontosaurier",
                  "<p>Der Brontosaurier ist ein &auml;u&szlig;erst friedliebender Pflanzenfresser.</p><p>Da&szlig; er von den Anh&auml;ngern Ugas als Kampfeinheit eingesetzt wird, ist auf den ersten Blick wieder einmal ein Ausdruck der widersinnigen Taktik der Aggas. Aber wenn man eine Armee Aggas heranziehen sieht, entdeckt man sofort die Vorteile des Brontosauriers. Er ist gro&szlig;! Der Reiter hat vom R&uuml;cken des Brontosauriers einen ausgezeichneten &Uuml;berblick und gibt Befehle an die entlegensten Einheiten des Heeres. Als Taktikzentrale eignet sich der Brontosaurier gerade wegen seiner friedliebenden Art, da er aufgrund seiner Gr&ouml;&szlig;e und Widerstandsf&auml;higkeit schwer zu besiegen ist und vor allem im Gegensatz zu fast allen anderen tierischen Einheiten Aggas nicht sofort in einen Blutrausch verf&auml;llt und sich sinnlos mitten ins Get&uuml;mmel st&uuml;rzt.</p>",
                  "unit_brontosaurus",
                  42,
                  68,
                  "160-([D0.ACT]+10*([B13.ACT]-6)+4*([B23.ACT]-7))",
                  0,
                  0,
                  5,
                  100,
                  100,
                  100,
                  array(),
                  1);

  $tmp->foodCost = 3.4;
  $tmp->wayCost  = 1.2;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "2500", 2 => "10", 3 => "10", 6 => "4");
  
  $tmp->buildingDepList    = array(4 => "9", 6 => "0", 10 => "0", 12 => "0", 13 => "6", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "7");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(10 => "8", 32 => "9", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 22 => "1", 18 => "0", 24 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 32 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 22 => "-1", 18 => "0", 24 => "0");
  

  $unitTypeList[42] = $tmp;

  // Lasttier
  $tmp = new Unit(43,
                  "Lasttier",
                  "<p>Das Lasttier ist gen&uuml;gsam und tr&auml;gt gro&szlig;e Lasten schnell &uuml;ber lange Strecken.</p><p>Das Lasttier kann 70 Einheiten von jeder Sorte Rohstoff tragen.</p>",
                  "unit_animalCarrier",
                  43,
                  23,
                  "90-([D0.ACT]+10*([B13.ACT]-6))",
                  0,
                  0,
                  10,
                  30,
                  30,
                  30,
                  array(1 => "60", 2 => "60", 3 => "60", 4 => "30", 5 => "30"),
                  1);

  $tmp->foodCost = 1.15;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "100", 2 => "20", 4 => "6", 5 => "3", 6 => "3");
  
  $tmp->unitProductionCost = array(14 => "1");
  
  $tmp->buildingDepList    = array(4 => "9", 6 => "0", 10 => "0", 12 => "0", 13 => "6", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "7");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(10 => "6", 34 => "9", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 24 => "1", 18 => "0", 22 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 34 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 24 => "-1", 18 => "0", 22 => "0");
  

  $unitTypeList[43] = $tmp;

  // Elite-Rennschnecke
  $tmp = new Unit(44,
                  "Elite-Rennschnecke",
                  "<p>Slavomir erkannte schnell die gesteigerte Kampfkraft, die in den durchtrainierten Schnecken steckte. Er gab diese Schnecken seiner &auml;ltersten Tochter Dalarna, die die Schnecken mit einem Schutzpanzer aus Metall ausstattete. Der Schneckenschleim wurde zus&auml;tzlich noch mit Schwefel angereichert - neben seiner heilenden Wirkung hatte dieser Schleim die Besonderheiten, den Kindern der H&ouml;hlen als Rutschbahn zu dienen, das Wachstum von Erdbeeren zu beschleunigen und im Winter als Brennmaterial zu dienen. Nie w&uuml;rden die Krieger diesen Schleim daf&uuml;r benutzen, ihre Gegner darauf ausrutschen zu lassen oder andere H&ouml;hlen vollzuschleimen...</p>",
                  "unit_eliteSnail",
                  44,
                  25,
                  "70-([D0.ACT]+10*([B13.ACT]-6))",
                  16,
                  0,
                  10,
                  30,
                  30,
                  15,
                  array(),
                  1);

  $tmp->foodCost = 1.25;
  $tmp->wayCost  = 0.4;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "400", 2 => "30", 3 => "40", 4 => "5", 5 => "12", 6 => "3");
  
  $tmp->unitProductionCost = array(27 => "1");
  
  $tmp->buildingDepList    = array(4 => "9", 6 => "0", 10 => "0", 12 => "0", 13 => "6", 11 => "0", 18 => "0", 19 => "7", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1", 11 => "0", 18 => "0", 19 => "-1", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(10 => "5", 28 => "9", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 18 => "1", 22 => "0", 24 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 28 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 18 => "-1", 22 => "0", 24 => "0");
  

  $unitTypeList[44] = $tmp;

  // Werwolf
  $tmp = new Unit(45,
                  "Werwolf",
                  "<p>FIXME</p>",
                  "unit_werewolf",
                  45,
                  20,
                  "88-([D0.ACT]+10*([B13.ACT]-6))",
                  0,
                  0,
                  25,
                  20,
                  20,
                  15,
                  array(),
                  1);

  $tmp->foodCost = 1;
  $tmp->wayCost  = 0.9;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "4", 1 => "125", 2 => "5", 3 => "45", 5 => "6", 6 => "3");
  
  $tmp->unitProductionCost = array(15 => "1");
  
  $tmp->buildingDepList    = array(2 => "20", 4 => "9", 6 => "0", 10 => "0", 12 => "0", 13 => "6", 11 => "0", 18 => "0", 19 => "0", 20 => "7", 23 => "0");
  $tmp->maxBuildingDepList = array(2 => "-1", 4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(10 => "5", 33 => "9", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 23 => "1", 19 => "0", 39 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 33 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 23 => "-1", 19 => "0", 39 => "0");
  

  $unitTypeList[45] = $tmp;

  // Schnellwucherndes Dornengestr&uuml;pp
  $tmp = new Unit(46,
                  "Schnellwucherndes Dornengestr&uuml;pp",
                  "<p>Urspr&uuml;nglich von Uga an einem sonnigen Tag direkt nach dem Rasenm&auml;hen erschaffen, war die herrlich duftende, rosa bl&uuml;hende \"Redonia\" eines der sch&ouml;nsten Gew&auml;chse in Ugas Garten.</p><p>Der Neid und der Ha&szlig; auf alle Sch&ouml;pfungen seines Bruders veranla&szlig;te Agga, sich der \"Redonia\" anzunehmen. Wie so vieles vorher, verwandelte Agga die Pflanze in ein Abbild seiner Selbst. Aus der wundersch&ouml;nen \"Redonia\" machte ER ein h&auml;&szlig;liches, muffiges, unglaublich schnell wucherndes, widerstandsf&auml;higes, alle anderen Gew&auml;chse abt&ouml;tendes, mit fiesen, langen Dornen &uuml;bers&auml;tes Gestr&uuml;pp.</p><p>In seiner Gro&szlig;herzigkeit erkannte Uga die Pein, die Agga dem einstmals wundersch&ouml;nen Gew&auml;chs durch dieses Dasein auferlegt hatte und nahm es unter seine Fittiche. Durch die Gro&szlig;z&uuml;gigkeit Ugas ber&uuml;hrt, ist das Dornengestr&uuml;pp heute gez&auml;hmt und eine gef&auml;hrliche Waffe, die ihre Kr&auml;fte im Kampf gegen Agga einsetzt.</p><p>Von seinen Anh&auml;ngern wird das schnellwuchernde Dornengestr&uuml;pp vor allem als Verteidigungs- und Belagerungswaffe verwendet. Dar&uuml;ber hinaus dient das Gestr&uuml;pp als Trainingsobjekt zur Abh&auml;rtung der K&ouml;rper der K&auml;mpfer. Auch die Verwendung der dornigen &Auml;ste als Dornenkn&uuml;ppel ist vereinzelt zu sehen, allerdings ist f&uuml;r das Schlagen eines solchen Kn&uuml;ppels viel Kraft und Ausdauer von N&ouml;ten, die lediglich die Eliteberserker im ausreichenden Ma&szlig;e besitzen. Deshalb gilt eine gro&szlig;e Dornenkeule als Statusobjekt unter den verschiedenen Kn&uuml;ppelkriegern.</p><p>Die weitl&auml;ufigen Wucherungen dienen dar&uuml;ber hinaus h&auml;ufig als Nistplatz des sehr seltenen b&ouml;sen Pestvogels.</p>",
                  "unit_wucheringScrub",
                  46,
                  42,
                  "70-([D0.ACT]+10*([B12.ACT]-6))",
                  0,
                  0,
                  25,
                  50,
                  50,
                  50,
                  array(),
                  1);

  $tmp->foodCost = 2.1;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "390", 2 => "100", 3 => "20", 5 => "8", 6 => "3");
  
  $tmp->unitProductionCost = array(29 => "1");
  
  $tmp->buildingDepList    = array(1 => "20", 4 => "9", 6 => "0", 10 => "0", 12 => "6", 13 => "0", 11 => "0", 18 => "0", 19 => "7", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(1 => "-1", 4 => "-1", 6 => "0", 10 => "0", 12 => "-1", 13 => "0", 11 => "0", 18 => "0", 19 => "-1", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(11 => "4", 36 => "9", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 19 => "1", 23 => "0", 39 => "0");
  $tmp->maxScienceDepList = array(11 => "-1", 36 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 19 => "-1", 23 => "0", 39 => "0");
  

  $unitTypeList[46] = $tmp;

  // Erzdruide
  $tmp = new Unit(47,
                  "Erzdruide",
                  "<p>Nachts versammeln sich an den Steinkreisen im Tal immer wieder in lange Gew&auml;ndern geh&uuml;llten Gestalten und laufen die ganze Nacht mit Fackeln in der Hand im Kreis murmelnd und singend drumherum. Seit Ihr angefangen habt, Euch f&uuml;r ihre Riten zu interessieren, sind ein paar sogar bereit, f&uuml;r einen ordentlichen Haufen Schwefelbrocken (an denen sie &uuml;brigens gleich mit rollenden Augen schn&uuml;ffeln), f&uuml;r Euch ein paar Dinge zu erledigen. Erstaunliche Leute, ihr ewiges im-Kreis-Laufen und ihr monotoner Singsang haben sie derart abgeh&auml;rtet, dass selbst eure Neandertaler neidisch sind. Essen wollen sie anscheinend auch kaum etwas und zu allem &Uuml;berfluss macht es den Anschein, dass die Natur selbst ihnen im Unterholz den Weg ebnet.</p><p>Allerdings hat diese N&auml;he zur Natur auch ihre T&uuml;cken. Die Erdw&auml;lle abzutragen und die Rattengruben zuzusch&uuml;tten konntet ihr ihnen ja noch austreiben. Dumm ist alleridngs, dass sie sich strikt weigern, nicht mehr auf Eure Arbeiter einzupr&uuml;geln. Das w&auml;re Raubbau an Mutter Natur. Solange ihr aber nur ihre Geschenke sammelt, haben sie keine Einw&auml;nde.</p><p>Hinweis: Diese Einheit kann sicherlich zu mehr dienen, als nur zum k&auml;mpfen. Die enge Beziehung des Druiden zur Natur k&ouml;nnte sich vielleicht irgendwann als sehr n&uuml;tzlich erweisen....</p>",
                  "unit_archdruid",
                  47,
                  18,
                  "70-(10*([B10.ACT]-6)+[D0.ACT])",
                  15,
                  0,
                  10,
                  15,
                  15,
                  10,
                  array(),
                  1);

  $tmp->foodCost = 0.9;
  $tmp->wayCost  = 0.9;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "150", 2 => "40", 3 => "30", 4 => "8", 5 => "1", 6 => "3");
  
  $tmp->unitProductionCost = array(30 => "1");
  
  $tmp->buildingDepList    = array(4 => "9", 7 => "23", 6 => "0", 10 => "6", 12 => "0", 13 => "0", 11 => "7", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 7 => "-1", 6 => "0", 10 => "-1", 12 => "0", 13 => "0", 11 => "-1", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(9 => "3", 37 => "9", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 39 => "1", 19 => "0", 23 => "0");
  $tmp->maxScienceDepList = array(9 => "-1", 37 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 39 => "-1", 19 => "0", 23 => "0");
  

  $unitTypeList[47] = $tmp;

  // Garstiger Schwefelwurm
  $tmp = new Unit(48,
                  "Garstiger Schwefelwurm",
                  "<p>Produziert beim Fressen Schwefelblasen (also er pupst), die kleine stinkende Erdexplosionen verursachen und damit aufwendig erstellte Verteidigungsanlagen in sich zusammenst&uuml;rzen lassen.</p>",
                  "unit_sulfurous_worm",
                  48,
                  5,
                  "34-(4*([B10.ACT]-6)+2*([B19.ACT]-7)+[D0.ACT])",
                  0,
                  30,
                  0,
                  5,
                  5,
                  3,
                  array(),
                  1);

  $tmp->foodCost = 0.25;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(5 => "2", 6 => "1");
  
  $tmp->unitProductionCost = array(10 => "3");
  
  $tmp->buildingDepList    = array(4 => "9", 6 => "0", 10 => "6", 12 => "0", 13 => "0", 11 => "0", 18 => "0", 19 => "7", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "-1", 12 => "0", 13 => "0", 11 => "0", 18 => "0", 19 => "-1", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(12 => "4", 27 => "9", 14 => "0", 15 => "0", 29 => "0", 31 => "1", 17 => "1", 25 => "0");
  $tmp->maxScienceDepList = array(12 => "-1", 27 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1", 17 => "-1", 25 => "0");
  

  $unitTypeList[48] = $tmp;

  // Poltergeist
  $tmp = new Unit(49,
                  "Poltergeist",
                  "<p>FIXME</p>",
                  "unit_poltergeist",
                  49,
                  19,
                  "80-([D0.ACT]+10*([B12.ACT]-6))",
                  8,
                  0,
                  15,
                  18,
                  18,
                  15,
                  array(),
                  1);

  $tmp->foodCost = 0.95;
  $tmp->wayCost  = 1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "5", 1 => "100", 2 => "30", 3 => "5", 4 => "10", 5 => "3", 6 => "3");
  
  $tmp->unitProductionCost = array(22 => "1");
  
  $tmp->buildingDepList    = array(4 => "9", 6 => "0", 10 => "0", 12 => "6", 13 => "0", 11 => "0", 18 => "7", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "-1", 13 => "0", 11 => "0", 18 => "-1", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(11 => "4", 38 => "9", 14 => "0", 15 => "0", 29 => "0", 31 => "1", 25 => "1", 17 => "0");
  $tmp->maxScienceDepList = array(11 => "-1", 38 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1", 25 => "-1", 17 => "0");
  

  $unitTypeList[49] = $tmp;

  // Brandpfeilsch&uuml;tze
  $tmp = new Unit(50,
                  "Brandpfeilsch&uuml;tze",
                  "<p>Ein Brandpfeilsch&uuml;tze feuert Pfeile mit brennenden Schwefelspitzen ab. Gerade gegen Konstruktionseinheiten sind die Brandpfeilsch&uuml;tzen eine sehr gute Waffe. Vor allem ihre Panzerung mit Schildkr&ouml;tenpanzern sind in Verbindung mit dem hohen Fernkampfschaden ausschlaggebend f&uuml;r ihre hohe Effektivit&auml;t im Kampf. Entgegen vieler anderer Agga-getreuer Krieger bleiben sie auf dem Schlachtfeld ganz ruhig und zielen mit t&ouml;dlicher Pr&auml;zision.</p><p>Die Ausbildung eines Brandpfeilsch&uuml;tzen ist sehr aufwendig und ben&ouml;tigt gut ausgebaute Trainingsgel&auml;nde. Die besten Bogensch&uuml;tzen werden weitergebildet. Aufgrund der Kostbarkeit des Schwefels d&uuml;rfen sie sich keine Fehlsch&uuml;sse leisten.</p><p>Die Treffsicherheit erflehen sich die Brandpfeilsch&uuml;tzen durch t&auml;gliche Anrufungen Aggas und durch den v&ouml;lligen Verzicht auf alkoholische Getr&auml;nke. In der Schlacht sind sie zumeist neben den Reitern der Brontosaurier die einzigen n&uuml;chternen Krieger. Dennoch sind sie auf Feiern gern gesehene G&auml;ste, was weniger an ihrer unterhaltsamen Art als an der Tatsache liegt, da&szlig; sie immer Schwefel bei sich haben und ihren &Auml;rger h&auml;ufig an Geb&auml;uden auslassen. Der Ausspruch \"Ich will das Bett in Flammen sehen!\" ist hier nicht &uuml;bertragen gemeint.</p>",
                  "unit_fireArcher",
                  50,
                  22,
                  "70-([D0.ACT]+10*([B12.ACT]-7))",
                  30,
                  3,
                  0,
                  12,
                  18,
                  15,
                  array(1 => "2", 2 => "2", 3 => "2"),
                  1);

  $tmp->foodCost = 1.1;
  $tmp->wayCost  = 0.9;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "25", 2 => "15", 3 => "10", 4 => "3", 5 => "10", 6 => "6");
  
  $tmp->unitProductionCost = array(23 => "1");
  
  $tmp->buildingDepList    = array(4 => "10", 6 => "0", 10 => "0", 12 => "7", 13 => "0", 11 => "7", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "-1", 13 => "0", 11 => "-1", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(3 => "10", 11 => "4", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 21 => "2", 20 => "0", 26 => "0");
  $tmp->maxScienceDepList = array(3 => "-1", 11 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 21 => "-1", 20 => "0", 26 => "0");
  

  $unitTypeList[50] = $tmp;

  // Rollender Panzerwagen
  $tmp = new Unit(51,
                  "Rollender Panzerwagen",
                  "<p>Der rollende Panzerwagen ist eine starke Nahkampfeinheit, ben&ouml;tigt daf&uuml;r aber auch ein gut ausgebautes Konstruktionszentrum und Trainingsgel&auml;nde. Der Panzerwagen ist die ultimative Allzweckwaffe auf dem Schlachtfeld, sowohl bei Angriffen aus der Entfernung als auch im Nahkampf, allerdings ist seine Produktion recht aufwendig und teuer.</p><p>Der rollende Panzerwagen kann eine Person und bis zu 40 Einheiten von jeder Ressource transportieren.</p>",
                  "unit_tank",
                  51,
                  45,
                  "160-([D0.ACT]+10*([B12.ACT]-7)+4*([B20.ACT]-7))",
                  0,
                  2,
                  40,
                  55,
                  55,
                  40,
                  array(1 => "40", 2 => "40", 3 => "40"),
                  1);

  $tmp->foodCost = 2.25;
  $tmp->wayCost  = 1.1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "3", 1 => "50", 2 => "120", 3 => "100", 4 => "30", 5 => "10", 6 => "4");
  
  $tmp->buildingDepList    = array(4 => "10", 6 => "0", 10 => "0", 12 => "7", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "7", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "-1", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(6 => "10", 11 => "4", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 20 => "2", 21 => "0", 26 => "0");
  $tmp->maxScienceDepList = array(6 => "-1", 11 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 20 => "-1", 21 => "0", 26 => "0");
  

  $unitTypeList[51] = $tmp;

  // Schattenamazone
  $tmp = new Unit(52,
                  "Schattenamazone",
                  "<p>unsichtbar</p><p>Die Schattenamazone ist eine Expertin der Tarnung, List und T&auml;uschung. Bei Nacht kann sie sich nahezu unbemerkt in praktisch jedem Gel&auml;nde bewegen, und oftmals durch ihr bet&ouml;rendes Auftreten den Kriegern des Gegners alle Arten von Informationen entlocken. Es soll sogar gelegentlich vorgekommen sein, da&szlig; sie auf diese Weise in den Besitz der geheimen Korrespondenz eines Stammesf&uuml;hrers gekommen ist.</p><p>Auch wenn sie f&uuml;r unerwartete \"Zwischenf&auml;lle\" stets mit einem handlichen Holzkn&uuml;ppel bewaffnet ist, so empfiehlt es sich doch, die Amazone aus Kampfhandlungen m&ouml;glichst herauszuhalten.</p>",
                  "unit_shadow_amazon",
                  52,
                  13,
                  "100-([D0.ACT]+10*([B6.ACT]-7)+4*([B23.ACT]-7))",
                  6,
                  0,
                  6,
                  15,
                  15,
                  10,
                  array(),
                  0);

  $tmp->foodCost = 0.65;
  $tmp->wayCost  = 0.3;$tmp->spyValue      = 8;
  $tmp->spyChance     = 0.8;
  $tmp->antiSpyChance = 0.4;
  $tmp->spyQuality    = 1.0;
  $tmp->resourceProductionCost = array(1 => "150", 2 => "45", 3 => "85", 4 => "16", 5 => "6", 6 => "6");
  
  $tmp->unitProductionCost = array(4 => "1");
  
  $tmp->buildingDepList    = array(4 => "10", 6 => "7", 10 => "0", 12 => "0", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "7");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "-1", 10 => "0", 12 => "0", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(5 => "9", 35 => "10", 14 => "0", 15 => "1", 29 => "0", 31 => "0", 26 => "2", 20 => "0", 21 => "0");
  $tmp->maxScienceDepList = array(5 => "-1", 35 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0", 26 => "-1", 20 => "0", 21 => "0");
  

  $unitTypeList[52] = $tmp;

  // Metallener Kolo&szlig;
  $tmp = new Unit(53,
                  "Metallener Kolo&szlig;",
                  "<p>Der Kolo&szlig; ist unglaublich gro&szlig; und unglaublich widerstandsf&auml;hig und demzufolge unglaublich langsam. Des Nachts schimmert sein Metall gr&uuml;nlich. Der charakteristische Klang zertrampelter Krieger brachte dem Kolo&szlig; auch den Spitznamen \"Hulk\" ein.</p><p>Der Kolo&szlig; wird aufgrund seiner Schwerf&auml;lligkeit haupts&auml;chlich zur Verteidigung genutzt, da der Marsch auf feindliche H&ouml;hlen mit dem Kolo&szlig; einfach zu lange dauert. In der Vergangenheit sind h&auml;ufig Nahkampfeinheiten auf dem Weg in den Kampf wieder nach Hause gegangen, weil sie die Kampfeslust verloren hatten.</p><p>Der Kolo&szlig; wird aus heiligem Metall unter Anbetung Ugas nur bei Neumond erschaffen. Versuche, Kolosse bei Vollmond oder anderen Mondphasen zu erschaffen, f&uuml;hrten zu bedauerlichen Fehlschl&auml;gen. So wurden sich selbst verst&uuml;mmelnde &Auml;xte, Metallklumpen mit Minderwertigkeitskomplexen und die umherwandernde Gie&szlig;kanne mit Loch erschaffen.</p><p>Inzwischen werden dem Kolo&szlig; eigens Bereiche in der H&ouml;hle zu gewiesen, um das Zertrampeln der eigenen H&ouml;hlenbewohner einzuschr&auml;nken.</p><p>Ein Kolo&szlig; kann nicht sprechen und sich auch sonst nicht verst&auml;ndlich machen. Dennoch versuchen die Weihpriester mit den Kolossen in Verbindung zu treten, da sie sich Hinweise auf den Willen Ugas erhoffen. Einzige erkennbare Reaktion war bisher das versehentliche Zertrampeln einiger Weihpriester.</p>",
                  "unit_metalColossus",
                  53,
                  63,
                  "80-([D0.ACT]+10*([B12.ACT]-7))",
                  3,
                  2,
                  35,
                  75,
                  75,
                  75,
                  array(1 => "12", 2 => "12", 3 => "12"),
                  1);

  $tmp->foodCost = 3.15;
  $tmp->wayCost  = 1.1;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(2 => "40", 4 => "50", 6 => "6");
  
  $tmp->unitProductionCost = array(37 => "1");
  
  $tmp->buildingDepList    = array(4 => "10", 6 => "0", 10 => "0", 12 => "7", 13 => "0", 11 => "7", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "-1", 13 => "0", 11 => "-1", 18 => "0", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(11 => "4", 32 => "10", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 22 => "2", 18 => "0", 24 => "0");
  $tmp->maxScienceDepList = array(11 => "-1", 32 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 22 => "-1", 18 => "0", 24 => "0");
  

  $unitTypeList[53] = $tmp;

  // Rollender Kornspeicher
  $tmp = new Unit(54,
                  "Rollender Kornspeicher",
                  "<p>Der rollende Kornspeicher ist die Entdeckung des Jahres! Moderne Stammesf&uuml;rsten von heute schicken nun ihre Nahrung nicht mehr mit den altmodischen Transporttragen herum, sondern nur noch mit dem Rollenden Kornspeicher! Greifen auch sie heute noch zu!</p><p>Gefertigt wird der rollende Kornspeicher aus einigen rollenden B.U.S.sen und etwas Metall, um diese zusammenzuflechten. Daf&uuml;r ist jedoch mindestens ein Sch&uuml;tzenturm erforderlich, denn wie k&ouml;nnte man sonst die erforderliche H&ouml;he erreichen?</p><p>Genauso ist ein Konstruktionszentrum erforderlich, mit einem Bestiarium ist man eher schlecht bedient. Selbstverst&auml;ndlich ist auch einiges Wissen in Bezug auf Metall erforderlich, ohne die eine Konstruktion leider entfallen m&uuml;&szlig;te. Von einer Mitnahme zu Raubz&uuml;gen ist dagegen eher abzuraten. Die rollenden Kornspeicher sind einfach zu langsam und viel zu anf&auml;llig gegen Brandpfeile...</p>",
                  "unit_rollingStorehouse",
                  54,
                  47,
                  "50-([D0.ACT]+10*([B12.ACT]-7))",
                  0,
                  0,
                  10,
                  30,
                  30,
                  100,
                  array(1 => "2000"),
                  1);

  $tmp->foodCost = 2.35;
  $tmp->wayCost  = 2;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(2 => "20", 3 => "4", 4 => "10", 6 => "6");
  
  $tmp->unitProductionCost = array(26 => "2");
  
  $tmp->buildingDepList    = array(4 => "10", 6 => "0", 10 => "0", 12 => "7", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "7", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "-1", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(11 => "3", 34 => "10", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 24 => "2", 18 => "0", 22 => "0");
  $tmp->maxScienceDepList = array(11 => "-1", 34 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 24 => "-1", 18 => "0", 22 => "0");
  

  $unitTypeList[54] = $tmp;

  // Drachensegler
  $tmp = new Unit(55,
                  "Drachensegler",
                  "<p>Der Drachensegler ist der Stolz aller fliegenden Einheiten. Bei richtigen Windverh&auml;ltnissen erreicht der Drachensegler etwa die 10fache Geschwindigkeit eines Kn&uuml;ppelkriegers.</p><p>Die Drachenkonstruktion ist ein Meisterwerk der Konstruktionskunst und geht auf eine Erfindung des ber&uuml;hmten Wissenschaftlers Leonard Tawintschi zur&uuml;ck. Ein Ger&uuml;st aus einem extrem leichten Metall wird mit Holz des Windbaumes &uuml;berzogen, das so leicht wie Tuch, aber wesentlich robuster, ist und nur in den geweihten G&auml;rten Ugas zu finden ist.</p><p>In fr&uuml;heren Zeiten machte ein gewisser Ickaruu&szlig; Experimente mit wachs&uuml;berzogenen T&uuml;chern, die aber nach seinem Unfalltod abgebrochen wurden.</p><p>Der Drachensegler kann sowohl aus der Luft Steine abwerfen, als auch im Tiefflug mit seinen Fl&uuml;geln gr&ouml;&szlig;ere Ansammlungen feindlicher Krieger niederm&auml;hen. Um diese F&auml;higkeiten zu erlangen durchlaufen die zuk&uuml;nftigen Drachensegler eine lange Ausbildung in den besten Trainingsgel&auml;nden der H&ouml;hlen.</p>",
                  "unit_dragonSailor",
                  55,
                  23,
                  "160-([D0.ACT]+10*([B12.ACT]-7)+4*([B18.ACT]-7))",
                  8,
                  0,
                  20,
                  20,
                  20,
                  20,
                  array(4 => "5", 5 => "5"),
                  1);

  $tmp->foodCost = 1.15;
  $tmp->wayCost  = 0.2;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "10", 2 => "220", 3 => "30", 4 => "20", 5 => "5", 6 => "6");
  
  $tmp->buildingDepList    = array(4 => "10", 6 => "0", 10 => "0", 12 => "7", 13 => "0", 11 => "0", 18 => "7", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "-1", 13 => "0", 11 => "0", 18 => "-1", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(11 => "4", 28 => "10", 14 => "1", 15 => "0", 29 => "0", 31 => "0", 18 => "2", 22 => "0", 24 => "0");
  $tmp->maxScienceDepList = array(11 => "-1", 28 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0", 18 => "-1", 22 => "0", 24 => "0");
  

  $unitTypeList[55] = $tmp;

  // Elite-Neandertaler
  $tmp = new Unit(56,
                  "Elite-Neandertaler",
                  "<p>FIXME</p>",
                  "unit_eliteNeandertaler",
                  56,
                  22,
                  "54-(10*([B23.ACT]-7)+[D0.ACT])",
                  0,
                  0,
                  20,
                  30,
                  30,
                  15,
                  array(1 => "3", 2 => "3", 3 => "3"),
                  1);

  $tmp->foodCost = 1.1;
  $tmp->wayCost  = 1.0;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "100", 2 => "40", 3 => "4", 4 => "10", 5 => "6", 6 => "3");
  
  $tmp->unitProductionCost = array(28 => "1");
  
  $tmp->buildingDepList    = array(4 => "10", 6 => "0", 10 => "7", 12 => "0", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "7");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "-1", 12 => "0", 13 => "0", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(5 => "9", 33 => "10", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 23 => "2", 19 => "0", 39 => "0");
  $tmp->maxScienceDepList = array(5 => "-1", 33 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 23 => "-1", 19 => "0", 39 => "0");
  

  $unitTypeList[56] = $tmp;

  // Eichenbaumhirte
  $tmp = new Unit(57,
                  "Eichenbaumhirte",
                  "<p>unsichtbar</p><p>Die Eichenbaumhirten sind die widerstandsf&auml;higsten unter den Baumhirten. Ihre Eichenborke ist von einer Art Pilz befallen, der sie gegen jegliche Art von Waffen nahezu unverwundbar macht. Anh&auml;nger Aggas haben versucht, sich diesen Pilz anzueignen, um ihre eigenen R&uuml;stungen zu verst&auml;rken. Allerdings mu&szlig;ten sie die schmerzhafte Erkenntnis hinnehmen, da&szlig; der Pilz in harmonischer Symbiose mit den Eichenbaumhirten lebt und f&uuml;r unbedachte Lebewesen &auml;u&szlig;erst t&ouml;dlich wirken kann.</p><p>Genau wie ihre Verwandten, die Baumhirten, sind die Eichenbaumhirten Ugas Ruf gefolgt und k&auml;mpfen gegen Agga, den Feind des Lebens. H&auml;ufig wandern die Eichenbaumhirten umher, um die Saat Ugas zu begutachten und die Sch&ouml;pfungen Aggas zu bek&auml;mpfen. Der Todfeind der Eichenbaumhirten ist das Schnellwuchernde Dornengestr&uuml;pp, mit dem sie sich seit dessen Erschaffung bitterb&ouml;se K&auml;mpfe liefern.</p><p>Der Legende von ThalRascha zufolge sind die Eichenbaumhirten im Besitz eines der m&auml;chtigsten Artefakte. Mit diesem Artefakt soll der Besitzer in der Lage sein, abgestorbene Lebewesen zu reanimieren und deren Pracht wieder vollst&auml;ndig herzustellen. Die Legende erz&auml;hlt weiter, da&szlig; bei Einsatz des Artefaktes ein Teil des Lebensfunken auf die wiederbelebten Lebewesen &uuml;bergeht und der Besitzer einen Teil seiner Lebensenergie verliert. Vermutlich ist dies der Grund, da&szlig; die Baumhirten das Artefakt nicht anwenden. Andererseits handelt es sich auch nur um eine Legende.</p><p>Die Eichenbaumhirten verstehen sich wie ihre Verwandten darauf, sich unsichtbar dem Feinde zu n&auml;hern.</p>",
                  "unit_oaktreeHerdsman",
                  57,
                  28,
                  "80-(10*([B13.ACT]-7)+[D0.ACT])",
                  0,
                  0,
                  8,
                  45,
                  45,
                  30,
                  array(),
                  0);

  $tmp->foodCost = 1.4;
  $tmp->wayCost  = 1.4;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->spyQuality    = 0;
  $tmp->resourceProductionCost = array(1 => "14", 2 => "25", 5 => "8", 6 => "8");
  
  $tmp->unitProductionCost = array(16 => "1", 34 => "1");
  
  $tmp->buildingDepList    = array(1 => "18", 4 => "10", 6 => "0", 10 => "0", 12 => "0", 13 => "7", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "7");
  $tmp->maxBuildingDepList = array(1 => "-1", 4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(6 => "8", 36 => "10", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 19 => "2", 23 => "0", 39 => "0");
  $tmp->maxScienceDepList = array(6 => "-1", 36 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 19 => "-1", 23 => "0", 39 => "0");
  

  $unitTypeList[57] = $tmp;

  // Frumioser Banderschnatz
  $tmp = new Unit(58,
                  "Frumioser Banderschnatz",
                  "<p>FIXME</p>",
                  "unit_eagle",
                  58,
                  17,
                  "60-(10*([B13.ACT]-7)+[D0.ACT])",
                  0,
                  0,
                  20,
                  20,
                  20,
                  10,
                  array(),
                  1);

  $tmp->foodCost = 0.85;
  $tmp->wayCost  = 0.3;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(0 => "1", 1 => "200", 2 => "50", 3 => "10", 4 => "3", 5 => "8", 6 => "3");
  
  $tmp->unitProductionCost = array(35 => "1");
  
  $tmp->buildingDepList    = array(4 => "10", 6 => "0", 10 => "0", 12 => "0", 13 => "7", 11 => "0", 18 => "7", 19 => "0", 20 => "0", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1", 11 => "0", 18 => "-1", 19 => "0", 20 => "0", 23 => "0");
  
  $tmp->scienceDepList    = array(10 => "4", 37 => "10", 14 => "0", 15 => "0", 29 => "1", 31 => "0", 39 => "2", 19 => "0", 23 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 37 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0", 39 => "-1", 19 => "0", 23 => "0");
  

  $unitTypeList[58] = $tmp;

  // Kampfmammut
  $tmp = new Unit(59,
                  "Kampfmammut",
                  "<p>Das Kampfmammut ist die widerstandsf&auml;higste Kampfeinheit, aber auch diejenige, die die l&auml;ngste und aufwendigste Ausbildung erfordert. Das riesige Mammut kann im Kampf gegnerische Krieger und Kampfmaschinen niedertrampeln. Um das Tier dabei vor Verletzungen zu sch&uuml;tzen, wird es mit einer leichten R&uuml;stung aus Metall ausgestattet.</p><p>Ein Mammut kann eine Person und jeweils 8 Einheiten von jeder Ressource tragen.</p>",
                  "unit_mammut",
                  59,
                  52,
                  "60-(10*([B13.ACT]-7)+[D0.ACT])",
                  0,
                  150,
                  20,
                  75,
                  75,
                  30,
                  array(1 => "12", 2 => "12", 3 => "12", 4 => "3", 5 => "3"),
                  1);

  $tmp->foodCost = 2.6;
  $tmp->wayCost  = 1.0;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "300", 2 => "20", 3 => "20", 4 => "30", 5 => "10", 6 => "6");
  
  $tmp->unitProductionCost = array(31 => "1");
  
  $tmp->buildingDepList    = array(4 => "10", 6 => "0", 10 => "0", 12 => "0", 13 => "7", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "7");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "0", 10 => "0", 12 => "0", 13 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "0", 23 => "-1");
  
  $tmp->scienceDepList    = array(10 => "4", 27 => "10", 14 => "0", 15 => "0", 29 => "0", 31 => "1", 17 => "2", 25 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 27 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1", 17 => "-1", 25 => "0");
  

  $unitTypeList[59] = $tmp;

  // Kn&uuml;ppelassassine
  $tmp = new Unit(60,
                  "Kn&uuml;ppelassassine",
                  "<p>unsichtbar</p><p>Die Kn&uuml;ppelassassine ist die einzige Kampfeinheit, zu der nur Frauen Zugang haben.</p><p>Ihre Kampftaktik ist einmalig. &Auml;u&szlig;erst leicht bekleidet, ausgestattet mit einem kleinen &auml;u&szlig;erst spitzen mit dem Gift des b&ouml;sen Pestvogels getr&auml;nkten Kn&uuml;ppel und einer handvoll schwefelgef&uuml;llter Tierm&auml;gen, begibt sie sich ohne gr&ouml;&szlig;ere Probleme v&ouml;llig unbemerkt tief hinter die feindlichen Linien.</p><p>Dabei nutzt sie schamlos den geringen Intellekt der vor Testosteron strotzenden feindlichen Krieger aus. W&auml;hrend sie Schwefelkugeln in verschiedene Richtungen auf die gr&ouml;&szlig;eren feindlichen Einheiten schleudert, flirtet sie ausgiebig mit den Nahkampfeinheiten. Diese sind nach kurzer Zeit v&ouml;llig verz&uuml;ckt von ihr, so da&szlig; keinem auff&auml;llt, da&szlig; jeder der versucht sie zu k&uuml;ssen mit einer klaffenden Wunde am Hals zusammenbricht. Dabei nutzt sie die Eigenart der M&auml;nner aus, nur das zu sehen was sie sehen wollen.</p><p>Die absolute Elite der Kn&uuml;ppelassassinen erreicht sogar, da&szlig; sich die gegnerischen Krieger gegenseitig die K&ouml;pfe einschlagen, um in ihre N&auml;he zu kommen.</p>",
                  "unit_clubAssassin",
                  60,
                  19,
                  "50-(10*([B6.ACT]-7)+[D0.ACT])",
                  0,
                  0,
                  30,
                  15,
                  15,
                  12,
                  array(),
                  1);

  $tmp->foodCost = 0.95;
  $tmp->wayCost  = 0.8;$tmp->spyValue      = 0;
  $tmp->spyChance     = 0;
  $tmp->antiSpyChance = 0;
  $tmp->resourceProductionCost = array(1 => "150", 2 => "50", 3 => "20", 5 => "10", 6 => "8");
  
  $tmp->unitProductionCost = array(32 => "1");
  
  $tmp->buildingDepList    = array(4 => "10", 6 => "7", 10 => "0", 12 => "0", 13 => "0", 15 => "5", 11 => "0", 18 => "0", 19 => "0", 20 => "7", 23 => "0");
  $tmp->maxBuildingDepList = array(4 => "-1", 6 => "-1", 10 => "0", 12 => "0", 13 => "0", 15 => "-1", 11 => "0", 18 => "0", 19 => "0", 20 => "-1", 23 => "0");
  
  $tmp->scienceDepList    = array(38 => "10", 14 => "0", 15 => "0", 29 => "0", 31 => "1", 25 => "2", 17 => "0");
  $tmp->maxScienceDepList = array(38 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1", 25 => "-1", 17 => "0");
  

  $unitTypeList[60] = $tmp;

}

	
/********************** Defense Systems *********************/
class DefenseSystem {
  var $defenseSystemID;
  var $name;
  var $description;
  var $dbFieldName;
  var $position;
  var $maxLevel;
  var $productionTimeFunction;

  var $attackRange;
  var $attackRate;
  var $defenseRate;
  var $hitPoints;

  

  var $resourceProductionCost  = array();
  var $unitProductionCost      = array();
  var $buildingProductionCost  = array();
  var $externalProductionCost  = array();
  var $buildingDepList         = array();
  var $maxBuildingDepList      = array();
  var $defenseSystemDepList    = array();
  var $maxDefenseSystemDepList = array();
  var $resourceDepList         = array();
  var $maxResourceDepList      = array();
  var $scienceDepList          = array();
  var $maxScienceDepList       = array();
  var $unitDepList             = array();
  var $maxUnitDepList          = array();

  var $antiSpyChance   = 0;
  var $nodocumentation = 0;

  function DefenseSystem($defenseSystemID, $name, $description, $dbFieldName, $position, $maxLevel,
                         $productionTimeFunction, $attackRange, $attackRate, $defenseRate, $hitPoints){

    $this->defenseSystemID        = $defenseSystemID;
    $this->name                   = $name;
    $this->description            = $description;
    $this->dbFieldName            = $dbFieldName;
    $this->position               = $position;
    $this->maxLevel               = $maxLevel;
    $this->productionTimeFunction = $productionTimeFunction;
    $this->attackRange            = $attackRange;
    $this->attackRate             = $attackRate;
    $this->defenseRate            = $defenseRate;
    $this->hitPoints              = $hitPoints;
  }
}

function init_defenseSystems(){

  global $defenseSystemTypeList;
 
  // Erweiterte Produktions- und Trainingsst&auml;tten
  // RankingWert 30
  $tmp = new DefenseSystem(0,
                           "Erweiterte Produktions- und Trainingsst&auml;tten",
                           "<p>Diese ausgelagerten Produktionsst&auml;tten beschleunigen die Produktion fast aller Einheiten. Wegen ihrer Gr&ouml;&szlig;e passen sie leider nicht mit in die H&ouml;hle und sind so Angriffen der Gegner ausgesetzt.</p>",
                           "extern_extendedProductionLocation",
                           0,
                           "7",
                           "720/([R0.ACT]/60+1)",
                           0,
                           0,
                           60,
                           30);



  $tmp->resourceProductionCost = array(1 => "200", 2 => "200", 3 => "200", 4 => "10*[D0.ACT]");
  
  $tmp->buildingDepList    = array(4 => "1");
  $tmp->maxBuildingDepList = array(4 => "-1");
  
  $defenseSystemTypeList[0] = $tmp;

  // Ugas Lustgarten
  // RankingWert 23
  $tmp = new DefenseSystem(1,
                           "Ugas Lustgarten",
                           "<p>Ein wundersch&ouml;n angelegter Garten von steinzeitlichen Gew&auml;chsen. Ein solcher Aufwand steigert die g&ouml;ttliche Gunst immens.</p>",
                           "extern_ugasGarden",
                           1,
                           "3",
                           "960/([R0.ACT]/60+1)",
                           0,
                           0,
                           40,
                           30);



  $tmp->resourceProductionCost = array(1 => "200*([D1.ACT]+1)", 2 => "150*([D1.ACT]+1)", 3 => "150*([D1.ACT]+1)", 4 => "10*([D1.ACT]+1)", 6 => "20");
  
  $tmp->buildingDepList    = array(16 => "1");
  $tmp->maxBuildingDepList = array(16 => "-1");
  
  $tmp->scienceDepList    = array(14 => "2", 15 => "0", 29 => "0", 31 => "0", 30 => "4");
  $tmp->maxScienceDepList = array(14 => "-1", 15 => "0", 29 => "0", 31 => "0", 30 => "-1");
  
  $defenseSystemTypeList[1] = $tmp;

  // Marterst&auml;tten
  // RankingWert 30
  $tmp = new DefenseSystem(2,
                           "Marterst&auml;tten",
                           "<p>Hier werden unschuldige Opfer v&ouml;llig sinnlos gepiesackt, gezwickt und anders gequ&auml;lt, was das Ansehen des Stammes in Aggas Augen betr&auml;chtlich steigert.</p>",
                           "extern_aggasTorture",
                           2,
                           "3",
                           "960/([R0.ACT]/60+1)",
                           0,
                           0,
                           60,
                           30);



  $tmp->resourceProductionCost = array(0 => "30*([D2.ACT]+1)", 1 => "200", 2 => "200", 3 => "200", 4 => "10*[D2.ACT]", 5 => "5*[D2.ACT]", 6 => "20");
  
  $tmp->buildingDepList    = array(17 => "1");
  $tmp->maxBuildingDepList = array(17 => "-1");
  
  $tmp->scienceDepList    = array(14 => "0", 15 => "2", 29 => "0", 31 => "0", 30 => "4");
  $tmp->maxScienceDepList = array(14 => "0", 15 => "-1", 29 => "0", 31 => "0", 30 => "-1");
  
  $defenseSystemTypeList[2] = $tmp;

  // Sch&ouml;pfungsgelehrter
  // RankingWert 30
  $tmp = new DefenseSystem(3,
                           "Sch&ouml;pfungsgelehrter",
                           "<p>FIXME</p>",
                           "extern_creationGelehrter",
                           3,
                           "3",
                           "960/([R0.ACT]/60+1)",
                           0,
                           0,
                           60,
                           30);



  $tmp->resourceProductionCost = array(1 => "200*([D3.ACT]+1)", 2 => "150*([D3.ACT]+1)", 3 => "150*([D3.ACT]+1)", 4 => "10*[D3.ACT]", 6 => "20");
  
  $tmp->buildingDepList    = array(24 => "1");
  $tmp->maxBuildingDepList = array(24 => "-1");
  
  $tmp->scienceDepList    = array(14 => "0", 15 => "0", 29 => "2", 31 => "0", 30 => "4");
  $tmp->maxScienceDepList = array(14 => "0", 15 => "0", 29 => "-1", 31 => "0", 30 => "-1");
  
  $defenseSystemTypeList[3] = $tmp;

  // Gr&auml;berfeld
  // RankingWert 30
  $tmp = new DefenseSystem(4,
                           "Gr&auml;berfeld",
                           "<p>FIXME</p>",
                           "extern_graveField",
                           4,
                           "3",
                           "240/([R0.ACT]/20+1)",
                           0,
                           0,
                           60,
                           30);



  $tmp->resourceProductionCost = array(0 => "50*([D4.ACT]+1)", 2 => "150", 3 => "150", 4 => "5*[D4.ACT]", 5 => "5*[D4.ACT]", 6 => "20");
  
  $tmp->scienceDepList    = array(14 => "0", 15 => "0", 29 => "0", 31 => "2", 30 => "4");
  $tmp->maxScienceDepList = array(14 => "0", 15 => "0", 29 => "0", 31 => "-1", 30 => "-1");
  
  $defenseSystemTypeList[4] = $tmp;

  // Erdwall
  // RankingWert 30
  $tmp = new DefenseSystem(5,
                           "Erdwall",
                           "<p>Der einfache, mit Ger&ouml;ll verst&auml;rkte Erdwall bietet Schutz vor feindlichen Angriffen, indem er eine gro&szlig;e Anzahl von angreifenden Kriegern bindet und sie damit am Anrichten von Schaden an den eigenen Einheiten hindert. Au&szlig;erdem k&ouml;nnen die Verteidiger dahinter vor Entfernungsangriffen in Deckung gehen.</p>",
                           "extern_earthBarrier",
                           5,
                           "GREATEST(20+2*[S27.ACT],0)",
                           "360/([R0.ACT]/20+1)",
                           0,
                           5,
                           50,
                           35);



  $tmp->resourceProductionCost = array(2 => "30", 3 => "30");
  
  $tmp->scienceDepList    = array(27 => "1");
  $tmp->maxScienceDepList = array(27 => "-1");
  
  $defenseSystemTypeList[5] = $tmp;

  // Fallgrube
  // RankingWert 15
  $tmp = new DefenseSystem(6,
                           "Fallgrube",
                           "<p>FIXME</p>",
                           "extern_dropPit",
                           6,
                           "GREATEST(20+2*[S37.ACT],0)",
                           "360/([R0.ACT]/20+1)",
                           0,
                           10,
                           15,
                           20);



  $tmp->resourceProductionCost = array(3 => "100", 3 => "50");
  
  $tmp->scienceDepList    = array(1 => "2", 37 => "1");
  $tmp->maxScienceDepList = array(1 => "-1", 37 => "-1");
  
  $defenseSystemTypeList[6] = $tmp;

  // Holzpiekser
  // RankingWert 7
  $tmp = new DefenseSystem(7,
                           "Holzpiekser",
                           "<p>FIXME</p>",
                           "extern_woodenPike",
                           7,
                           "GREATEST(50+5*[S8.ACT],0)",
                           "180/([R0.ACT]/20+1)",
                           0,
                           10,
                           4,
                           6);



  $tmp->resourceProductionCost = array(2 => "50");
  
  $tmp->scienceDepList    = array(2 => "2", 5 => "2");
  $tmp->maxScienceDepList = array(2 => "-1", 5 => "-1");
  
  $defenseSystemTypeList[7] = $tmp;

  // Kleine Blendsteine
  // RankingWert 7
  $tmp = new DefenseSystem(8,
                           "Kleine Blendsteine",
                           "<p>Diese Erfindung geht wohl auf den gro&szlig;artigen Schamanen M&uuml;h zur&uuml;ck. Seiner Idee folgend poliert man also nun mit einiger Ausdauer und Hingabe besonders glatte Steine. Eigentlich sollten sie dann dazu eingesetzt werden, angreifende W&uuml;stlinge zu blenden und damit von der eigenen H&ouml;hle fernzuhalten. Leider funktionierte der Plan nicht so richtig. Nachts scheint n&auml;mlich keine Sonne.. Nun ja, einen gewissen &auml;sthetischen Reiz kann man ihnen nicht absprechen, auch wenn sie mehr im Weg stehen.. was nat&uuml;rlich auch ein Vorteil sein kann. Vielleicht hat mal eines Tages ein findiger Schamane eine brauchbare Idee..</p>",
                           "extern_dazzleStone",
                           8,
                           "GREATEST(2*[S34.ACT],0)",
                           "240/([R0.ACT]/20+1)",
                           0,
                           0,
                           10,
                           10);



  $tmp->antiSpyChance  = 0.1;
  $tmp->resourceProductionCost = array(0 => "2", 1 => "50", 3 => "100");
  
  $tmp->scienceDepList    = array(34 => "2");
  $tmp->maxScienceDepList = array(34 => "-1");
  
  $defenseSystemTypeList[8] = $tmp;

  // L&ouml;chriger H&ouml;hlenvorhang
  // RankingWert 6
  $tmp = new DefenseSystem(9,
                           "L&ouml;chriger H&ouml;hlenvorhang",
                           "<p>Man m&ouml;chte nicht ausspioniert werden? L&auml;stige Geb&uuml;sche mit l&auml;stigen kleinen Augen darin st&ouml;ren Sie? Oder taucht sogar ab und zu ein neugieriger Pilzsammler an ihrem H&ouml;hleneingang auf?</p><p>Hier haben sie die L&ouml;sung! Installieren sie noch heute dieses HaiTeck-Gewebe! Sie brauchen nur noch ein halbes Dutzend Leute, die es festhalten..</p>",
                           "extern_holeyCurtain",
                           9,
                           "GREATEST(2*[S35.ACT],0)",
                           "240/([R0.ACT]/20+1)",
                           0,
                           0,
                           8,
                           10);



  $tmp->antiSpyChance  = 0.1;
  $tmp->resourceProductionCost = array(0 => "6", 1 => "200");
  
  $tmp->scienceDepList    = array(0 => "12");
  $tmp->maxScienceDepList = array(0 => "-1");
  $tmp->nodocumentation = 1;
  
  $defenseSystemTypeList[9] = $tmp;

  // Sch&uuml;tzenturm
  // RankingWert 33
  $tmp = new DefenseSystem(10,
                           "Sch&uuml;tzenturm",
                           "<p>Ein mit jeweils drei Steinschleuderern bemannter Wachturm. Der Sch&uuml;tzenturm bietet den Vorteil, da&szlig; die Steinschleuderer auch im Get&uuml;mmel ihre Waffen einsetzen k&ouml;nnen, da sie in einer sicheren Position &uuml;ber dem Kampfgeschehen stehen.</p>",
                           "extern_tower",
                           10,
                           "GREATEST(2*[S3.ACT],0)",
                           "240/([R0.ACT]/20+1)",
                           30,
                           10,
                           20,
                           30);



  $tmp->resourceProductionCost = array(0 => "10", 1 => "200", 2 => "60", 3 => "60");
  
  $tmp->buildingDepList    = array(4 => "2");
  $tmp->maxBuildingDepList = array(4 => "-1");
  
  $tmp->scienceDepList    = array(1 => "2", 2 => "2", 3 => "2");
  $tmp->maxScienceDepList = array(1 => "-1", 2 => "-1", 3 => "-1");
  
  $defenseSystemTypeList[10] = $tmp;

  // Wachturm
  // RankingWert 50
  $tmp = new DefenseSystem(11,
                           "Wachturm",
                           "<p>Die beste Verteidigung einer H&ouml;hle hilft einem nichts, wenn man nicht bemerkt, da&szlig; der Feind schon mitten in der H&ouml;hle steht. Was n&uuml;tzt einem ein massiver Zerkugeler, wenn man ihn nicht ausl&ouml;st? Zu diesem Zweck wurden schon bald Wacht&uuml;rme gebaut, um dem Angreifer das &Uuml;berraschungsmoment zu nehmen.</p><p>Der Wachturm ist eine stabile Konstruktion aus ein paar Holzst&auml;mmen und einem Riesenschildkr&ouml;tenschild, auf dem die Wachen ihren Dienst verrichten. Je mehr Wacht&uuml;rme man besitzt, desto weniger kann man von z.B. einer Horde siamesischer Kn&uuml;ppler &uuml;berrascht werden.</p>",
                           "extern_watchtower",
                           11,
                           "[S33.ACT]",
                           "420/([R0.ACT]/20+1)",
                           30,
                           10,
                           50,
                           50);



  $tmp->resourceProductionCost = array(0 => "2", 2 => "60*([D11.ACT]+1)", 4 => "10*[D11.ACT]");
  
  $tmp->externalProductionCost = array(10 => "1");
  
  $tmp->buildingDepList    = array(4 => "2");
  $tmp->maxBuildingDepList = array(4 => "-1");
  
  $tmp->scienceDepList    = array(1 => "2", 2 => "2", 3 => "2", 33 => "2");
  $tmp->maxScienceDepList = array(1 => "-1", 2 => "-1", 3 => "-1", 33 => "-1");
  
  $defenseSystemTypeList[11] = $tmp;

  // Ringf&ouml;rmige Mulde
  // RankingWert 90
  $tmp = new DefenseSystem(12,
                           "Ringf&ouml;rmige Mulde",
                           "<p>FIXME</p>",
                           "extern_moat",
                           12,
                           "2*[B12.ACT]+2*[S11.ACT]",
                           "1200/([R0.ACT]/20+GREATEST(1,[B12.ACT]))",
                           0,
                           20,
                           100,
                           150);



  $tmp->resourceProductionCost = array(1 => "400", 2 => "60", 3 => "90", 4 => "20");
  
  $tmp->externalProductionCost = array(5 => "2");
  
  $tmp->buildingDepList    = array(12 => "3");
  $tmp->maxBuildingDepList = array(12 => "-1");
  
  $tmp->scienceDepList    = array(11 => "2", 14 => "0", 15 => "1", 29 => "0", 31 => "0");
  $tmp->maxScienceDepList = array(11 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0");
  
  $defenseSystemTypeList[12] = $tmp;

  // Ameisenh&uuml;gel
  // RankingWert 73
  $tmp = new DefenseSystem(13,
                           "Ameisenh&uuml;gel",
                           "<p>FIXME</p>",
                           "extern_antHill",
                           13,
                           "2*[B13.ACT]+2*[S10.ACT]",
                           "1200/([R0.ACT]/20+GREATEST(1,[B13.ACT]))",
                           0,
                           100,
                           50,
                           70);



  $tmp->resourceProductionCost = array(1 => "1000", 2 => "80", 3 => "100");
  
  $tmp->externalProductionCost = array(5 => "1");
  
  $tmp->buildingDepList    = array(13 => "3");
  $tmp->maxBuildingDepList = array(13 => "-1");
  
  $tmp->scienceDepList    = array(10 => "2", 14 => "1", 15 => "0", 29 => "0", 31 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0");
  
  $defenseSystemTypeList[13] = $tmp;

  // Schildmauer
  // RankingWert 90
  $tmp = new DefenseSystem(14,
                           "Schildmauer",
                           "<p>FIXME</p>",
                           "extern_shieldWall",
                           14,
                           "2*[B10.ACT]+2*[S11.ACT]",
                           "1200/([R0.ACT]/20+GREATEST(1,[B10.ACT]))",
                           0,
                           20,
                           100,
                           150);



  $tmp->resourceProductionCost = array(1 => "300", 2 => "100", 3 => "120", 4 => "20");
  
  $tmp->externalProductionCost = array(5 => "1");
  
  $tmp->buildingDepList    = array(10 => "3");
  $tmp->maxBuildingDepList = array(10 => "-1");
  
  $tmp->scienceDepList    = array(11 => "2", 14 => "0", 15 => "0", 29 => "1", 31 => "0");
  $tmp->maxScienceDepList = array(11 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0");
  
  $defenseSystemTypeList[14] = $tmp;

  // Brennnesselhaufen
  // RankingWert 67
  $tmp = new DefenseSystem(15,
                           "Brennnesselhaufen",
                           "<p>FIXME</p>",
                           "extern_stingingNettleHeap",
                           15,
                           "2*[B12.ACT]+2*[S11.ACT]",
                           "1200/([R0.ACT]/20+GREATEST(1,[B12.ACT]))",
                           0,
                           80,
                           50,
                           70);



  $tmp->resourceProductionCost = array(1 => "750", 2 => "150", 3 => "100");
  
  $tmp->externalProductionCost = array(5 => "1");
  
  $tmp->buildingDepList    = array(12 => "3");
  $tmp->maxBuildingDepList = array(12 => "-1");
  
  $tmp->scienceDepList    = array(11 => "2", 14 => "0", 15 => "0", 29 => "1", 31 => "0");
  $tmp->maxScienceDepList = array(11 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0");
  
  $defenseSystemTypeList[15] = $tmp;

  // Knochenwall
  // RankingWert 70
  $tmp = new DefenseSystem(16,
                           "Knochenwall",
                           "<p>FIXME</p>",
                           "extern_boneBarrier",
                           16,
                           "2*[B12.ACT]+2*[S11.ACT]",
                           "1200/([R0.ACT]/20+GREATEST(1,[B12.ACT]))",
                           0,
                           20,
                           120,
                           70);



  $tmp->resourceProductionCost = array(0 => "100", 1 => "50", 2 => "80", 3 => "100", 4 => "20");
  
  $tmp->externalProductionCost = array(5 => "1");
  
  $tmp->buildingDepList    = array(12 => "3");
  $tmp->maxBuildingDepList = array(12 => "-1");
  
  $tmp->scienceDepList    = array(11 => "2", 14 => "0", 15 => "0", 29 => "0", 31 => "1");
  $tmp->maxScienceDepList = array(11 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1");
  
  $defenseSystemTypeList[16] = $tmp;

  // Berstende Riesenschildkr&ouml;tenschilde
  // RankingWert 98
  $tmp = new DefenseSystem(17,
                           "Berstende Riesenschildkr&ouml;tenschilde",
                           "<p>FIXME</p>",
                           "extern_burstingCarapace",
                           17,
                           "2*[B10.ACT]+2*[S6.ACT]",
                           "1200/([R0.ACT]/20+GREATEST(1,[B10.ACT]))",
                           50,
                           200,
                           10,
                           20);



  $tmp->resourceProductionCost = array(0 => "20", 3 => "300", 5 => "50");
  
  $tmp->externalProductionCost = array(6 => "1");
  
  $tmp->buildingDepList    = array(10 => "3");
  $tmp->maxBuildingDepList = array(10 => "-1");
  
  $tmp->scienceDepList    = array(6 => "4", 14 => "0", 15 => "1", 29 => "0", 31 => "0");
  $tmp->maxScienceDepList = array(6 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0");
  
  $defenseSystemTypeList[17] = $tmp;

  // Bei&szlig;ende Rattengrube
  // RankingWert 40
  $tmp = new DefenseSystem(18,
                           "Bei&szlig;ende Rattengrube",
                           "<p>FIXME</p>",
                           "extern_ratPit",
                           18,
                           "2*[B13.ACT]+2*[S10.ACT]",
                           "1200/([R0.ACT]/20+GREATEST(1,[B13.ACT]))",
                           0,
                           50,
                           30,
                           40);



  $tmp->resourceProductionCost = array(1 => "1000", 3 => "200");
  
  $tmp->externalProductionCost = array(6 => "1");
  
  $tmp->buildingDepList    = array(13 => "3");
  $tmp->maxBuildingDepList = array(13 => "-1");
  
  $tmp->scienceDepList    = array(10 => "2", 14 => "0", 15 => "1", 29 => "0", 31 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0");
  
  $defenseSystemTypeList[18] = $tmp;

  // Unglaublich tiefe Kuhle
  // RankingWert 77
  $tmp = new DefenseSystem(19,
                           "Unglaublich tiefe Kuhle",
                           "<p>FIXME</p>",
                           "extern_incredibleDeepHole",
                           19,
                           "2*[B12.ACT]+2*[S32.ACT]",
                           "1200/([R0.ACT]/20+GREATEST(1,[B12.ACT]))",
                           0,
                           30,
                           100,
                           100);



  $tmp->resourceProductionCost = array(3 => "200", 4 => "50");
  
  $tmp->externalProductionCost = array(6 => "2");
  
  $tmp->buildingDepList    = array(12 => "3");
  $tmp->maxBuildingDepList = array(12 => "-1");
  
  $tmp->scienceDepList    = array(32 => "4", 14 => "1", 15 => "0", 29 => "0", 31 => "0");
  $tmp->maxScienceDepList = array(32 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0");
  
  $defenseSystemTypeList[19] = $tmp;

  // Wespennest
  // RankingWert 59
  $tmp = new DefenseSystem(20,
                           "Wespennest",
                           "<p>FIXME</p>",
                           "extern_waspNest",
                           20,
                           "2*[B13.ACT]+2*[S10.ACT]",
                           "1200/([R0.ACT]/20+GREATEST(1,[B13.ACT]))",
                           20,
                           100,
                           20,
                           30);



  $tmp->resourceProductionCost = array(1 => "2500", 3 => "200", 5 => "30");
  
  $tmp->externalProductionCost = array(6 => "1");
  
  $tmp->buildingDepList    = array(13 => "3");
  $tmp->maxBuildingDepList = array(13 => "-1");
  
  $tmp->scienceDepList    = array(10 => "2", 14 => "0", 15 => "0", 29 => "1", 31 => "0");
  $tmp->maxScienceDepList = array(10 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0");
  
  $defenseSystemTypeList[20] = $tmp;

  // Pestgrube
  // RankingWert 57
  $tmp = new DefenseSystem(21,
                           "Pestgrube",
                           "<p>FIXME</p>",
                           "extern_plaguePit",
                           21,
                           "2*[B10.ACT]+2*[B19.ACT]",
                           "1200/([R0.ACT]/20+GREATEST(1,[B10.ACT]))",
                           0,
                           100,
                           30,
                           40);



  $tmp->resourceProductionCost = array(0 => "150", 3 => "250");
  
  $tmp->externalProductionCost = array(6 => "1");
  
  $tmp->buildingDepList    = array(10 => "3", 19 => "3");
  $tmp->maxBuildingDepList = array(10 => "-1", 19 => "-1");
  
  $tmp->scienceDepList    = array(14 => "0", 15 => "0", 29 => "0", 31 => "1");
  $tmp->maxScienceDepList = array(14 => "0", 15 => "0", 29 => "0", 31 => "-1");
  
  $defenseSystemTypeList[21] = $tmp;

  // Fieser Piekser
  // RankingWert 12
  $tmp = new DefenseSystem(22,
                           "Fieser Piekser",
                           "<p>FIXME</p>",
                           "extern_evilPike",
                           22,
                           "5*[S15.ACT]+5*[S35.ACT]",
                           "120/([R0.ACT]/20+1)",
                           0,
                           15,
                           10,
                           10);



  $tmp->resourceProductionCost = array(2 => "50", 4 => "5");
  
  $tmp->externalProductionCost = array(7 => "1");
  
  $tmp->scienceDepList    = array(8 => "2", 35 => "3", 14 => "0", 15 => "1", 29 => "0", 31 => "0");
  $tmp->maxScienceDepList = array(8 => "-1", 35 => "-1", 14 => "0", 15 => "-1", 29 => "0", 31 => "0");
  
  $defenseSystemTypeList[22] = $tmp;

  // Holzpflockiger Wirbelwind
  // RankingWert 12
  $tmp = new DefenseSystem(23,
                           "Holzpflockiger Wirbelwind",
                           "<p>FIXME</p>",
                           "extern_flyingStakes",
                           23,
                           "5*[S14.ACT]+5*[S28.ACT]",
                           "120/([R0.ACT]/20+1)",
                           10,
                           5,
                           8,
                           10);



  $tmp->resourceProductionCost = array(2 => "75");
  
  $tmp->externalProductionCost = array(7 => "1");
  
  $tmp->scienceDepList    = array(8 => "2", 28 => "3", 14 => "1", 15 => "0", 29 => "0", 31 => "0");
  $tmp->maxScienceDepList = array(8 => "-1", 28 => "-1", 14 => "-1", 15 => "0", 29 => "0", 31 => "0");
  
  $defenseSystemTypeList[23] = $tmp;

  // Pieksende Rosenstaude
  // RankingWert 12
  $tmp = new DefenseSystem(24,
                           "Pieksende Rosenstaude",
                           "<p>FIXME</p>",
                           "extern_stingingRoseBush",
                           24,
                           "5*[S29.ACT]+5*[S36.ACT]",
                           "120/([R0.ACT]/20+1)",
                           0,
                           10,
                           10,
                           15);



  $tmp->resourceProductionCost = array(2 => "60");
  
  $tmp->externalProductionCost = array(7 => "1");
  
  $tmp->scienceDepList    = array(8 => "2", 36 => "3", 14 => "0", 15 => "0", 29 => "1", 31 => "0");
  $tmp->maxScienceDepList = array(8 => "-1", 36 => "-1", 14 => "0", 15 => "0", 29 => "-1", 31 => "0");
  
  $defenseSystemTypeList[24] = $tmp;

  // Totempfahl
  // RankingWert 15
  $tmp = new DefenseSystem(25,
                           "Totempfahl",
                           "<p>FIXME</p>",
                           "extern_totem",
                           25,
                           "5*[S31.ACT]+5*[S38.ACT]",
                           "120/([R0.ACT]/20+1)",
                           10,
                           5,
                           12,
                           15);



  $tmp->resourceProductionCost = array(0 => "20", 2 => "50");
  
  $tmp->externalProductionCost = array(7 => "1");
  
  $tmp->scienceDepList    = array(8 => "2", 38 => "3", 14 => "0", 15 => "0", 29 => "0", 31 => "1");
  $tmp->maxScienceDepList = array(8 => "-1", 38 => "-1", 14 => "0", 15 => "0", 29 => "0", 31 => "-1");
  
  $defenseSystemTypeList[25] = $tmp;

}

	
/********************** Terrains *********************/
  define("MAX_TERRAINS", 5);
  
  // Ebene
  define("TERRAIN_PLAINS", 0);
  $terrainList[0]['terrainID']        = "TERRAIN_PLAINS";
  $terrainList[0]['name']             = "Ebene";
  $terrainList[0]['takeoverByCombat'] = 0;

  // Wald
  define("TERRAIN_FOREST", 1);
  $terrainList[1]['terrainID']        = "TERRAIN_FOREST";
  $terrainList[1]['name']             = "Wald";
  $terrainList[1]['takeoverByCombat'] = 0;

  // Gebirge
  define("TERRAIN_MOUNTAINS", 2);
  $terrainList[2]['terrainID']        = "TERRAIN_MOUNTAINS";
  $terrainList[2]['name']             = "Gebirge";
  $terrainList[2]['takeoverByCombat'] = 0;

  // Sumpf
  define("TERRAIN_SWAMP", 3);
  $terrainList[3]['terrainID']        = "TERRAIN_SWAMP";
  $terrainList[3]['name']             = "Sumpf";
  $terrainList[3]['takeoverByCombat'] = 0;

  // Nichts
  define("TERRAIN_VOID", 4);
  $terrainList[4]['terrainID']        = "TERRAIN_VOID";
  $terrainList[4]['name']             = "Nichts";
  $terrainList[4]['takeoverByCombat'] = 1;

?>
