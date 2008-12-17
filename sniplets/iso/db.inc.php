<?
class DbResult{
	var $result;
	var $row;

	function DbResult( $result )
	{
		$this->result = $result;
	}

	function isEmpty()
	{
		return mysql_num_rows( $this->result )==0;
	}

	function numRows()
	{
		return mysql_num_rows( $this->result );
	}

	function nextRow($result_type = MYSQL_BOTH)
	{
		$this->row = & mysql_fetch_array( $this->result, $result_type);
		return $this->row;
	}

	function nextField()
	{
		return mysql_fetch_field( $this->result );
	}

	function actRow()
	{
		return $this->row;
	}

	function field($row, $column)
	{                      // nur verwenden, wenn nextRow() nicht
		return mysql_result($this->result, $row, $column);      // mit dieser Instanz verwendet wird.
	}

	function free()
	{
		mysql_free_result($this->result);
	}
}


class Db
{
	var $con;
	var $num_queries  = 0;
	var $time_queries = 0;

	// Verbindung erzeugen

	function Db ( $host=0, $user=0, $pwd=0, $name=0 )
	{
		global $config;

		if( ! $host )
			$host = $config->DB_HOST;

		if( ! $user )
			$user = $config->DB_USER;

		if( ! $pwd )
			$pwd = $config->DB_PWD;

		if( ! $name  )
			$name = $config->DB_NAME;

		if (!( $this->con = mysql_connect(
				$host,
				$user,
				$pwd, 1, MYSQL_CLIENT_COMPRESS)))
		{
			return 0;
		}

		if (!( mysql_select_db( $name )))
		{
			return 0;
		}

		return $this;
	}

/* Query absetzen
 */
	function query( $query )
	{
		global $params, $user;
//echo $query."<br>\n";
    // get time
    list($usec, $sec) = explode(' ',microtime());
    $querytime_before = ((float)$usec + (float)$sec);
    
    // send query
    $rs = mysql_query($query, $this->con);
    
    // add needed time
    list($usec, $sec) = explode(' ',microtime());
    $this->time_queries += ((float)$usec + (float)$sec) - $querytime_before;

    // inc amount of queries
    $this->num_queries++;
    
		if (!$rs) return 0;
		return new DbResult( $rs );
	}

/* insertID holen
 */
	function insertID()
	{
		$id = mysql_insert_id( $this->con );

		return $id;
	}

	function affected_rows()
	{
		return mysql_affected_rows( $this->con );
	}
}
?>
