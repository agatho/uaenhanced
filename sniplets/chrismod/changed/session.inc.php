<?

function session_start_session(){

  global $cfg;

  session_save_path($cfg['SESSION_SAVE_PATH']);
  session_name("UAPORTAL");
  session_start();
}

function session_generate_code(){

  global $seccode;
  
  $chars = "02345689";
  $seccode = "";
  srand((double)microtime() * 1000000);
  for ($i = 0; $i < 4; ++$i) $seccode .= $chars{rand(0, strlen($chars) - 1)};
  $_SESSION['seccode'] = $seccode;
}


function session_destroy_session(){

  unset($_SESSION);
  session_unset();
  setcookie (session_name(), '', (time () - 2592000), '/', '', 0);

  $HTTP_COOKIE_VARS = array();
  $_COOKIE          = array();
  $_REQUEST         = array();

  session_destroy();
}
?>