<?php

class Point
{
	private $_lat;
	private $_lon;
	private $_ele;
	private $_date;
	private $_desc;

	public function __construct($lat,$lon,$desc,$ele = 0,$date=null)
	{
		$this->_lat=$lat;
		$this->_lon=$lon;
		$this->_ele=$ele;
		$this->_date=$date;
		$this->_desc=$desc;
	}

	public function getGPSString()
	{
		return ($this->_lat).",".($this->_lon);
	}

	public function getLat()
	{
		return $this->_lat;
	}

	public function getLon()
	{
		return $this->_lon;
	}

	public function getEle()
	{
		return $this->_ele;
	}

	public function getDate()
	{
		return $this->_date;
	}

	public function getDescription() {
		return $this->_desc;
	}
}

?>
