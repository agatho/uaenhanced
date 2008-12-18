<?
class CMicroTimer {
	var $t1 = array();
	var $t2 = array();
	var $t_time = -1;
	function CMicroTimer($strt=false) {
		if($strt) $this->start();
	}

	function start() {
		$this->t2 = -1;
		$this->t1 = explode(' ', microtime());
	}

	function stop() {
		$this->t2 = explode(' ', microtime());
		$this->t_time = ((float)$this->t2[0]+(float)$this->t2[1]) - ((float)$this->t1[0]+(float)$this->t1[1]);
		return $this->t_time;
	}
};
$microTimer =& new CMicroTimer();
?>