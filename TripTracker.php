<?php

include_once "Point.php";
include_once "GPS.php";

class TripTracker
{

	private $_points;
	private $_nb=0;
	private $_nom;
	private $_year;
	private $_data;

	public function __construct($tab,$year = 0,$nom = '')
	{
		$this->_points = array();
		$this->_nom = $nom;
		$this->_year = $year;

		for($i=0;$i < count($tab);$i=$i+3)
		{
			$this->_points[] = new Point($tab[$i],$tab[$i+1],"",$tab[$i+2], $year);
			$this->_nb++;
		}
	}

	public function setData($data)
        {
                $this->_nom = $data['name'];
                $this->_year = $data['year'];
		$this->_data = $data;
        }

	public function getDistance()
	{
		$total = 0;

		for($i=0;$i<$this->_nb;$i++)
		{
			$total += $this->getDistanceBetween($i);
		}

		return round($total,1);
	}

	public function getDistanceBetween($nb) {


		if($nb <= 0) return 0;
		if($nb > $this->_nb) return 0;

		$p1 = $this->_points[$nb-1];
		$p2 = $this->_points[$nb];
		$total = GPS::getDistance($p1->getLat(),$p1->getLon(),$p2->getLat(),$p2->getLon());

                return round($total,3);

	}

	public function getName() {
		return "tripTo".$this->_nom;
	}

	public function printItineraire()
	{
		echo "var tripTo".$this->_nom." = [ ";

		for($i=0;$i<$this->_nb;$i++)
		{
			$point = $this->_points[$i];
			$gps = $point->getGPSString();
			echo "new google.maps.LatLng(".$gps.")";
			
			if($i != $this->_nb-1)
			{
				echo ",";
			}
		}

		echo "];\n\n";
	}

	public function getElevation() {

		echo "var elevation_tripTo".$this->_nom." = [ ";
		$total = 0;

		for($i=0;$i<$this->_nb;$i++)
		{
			$total += $this->getDistanceBetween($i);
			$point = $this->_points[$i];
			echo "[".$total.",".$point->getEle()."]";
			
			if($i != $this->_nb-1)
			{
				echo ",";
			}
		}

		echo "];\n\n";

	}

	public function getObjectJS($nameObj) {

		$name = $this->getName();
		$str = $nameObj.".".$name." = {};";
		$str .= $nameObj.".".$name.".name = '".$this->_nom."';";
		$str .= $nameObj.".".$name.".idRando = '".$name."';";
		$str .= $nameObj.".".$name.".title = '".str_replace("'", "\'",$this->_data['title'])."';";
		$str .= $nameObj.".".$name.".description = '".str_replace("'", "\'", $this->_data['description'])."';";
		$str .= $nameObj.".".$name.".country = '".$this->_data['country']."';";
		$str .= $nameObj.".".$name.".length = '" . $this->getDistance() ."km';";
		$str .= $nameObj.".".$name.".duration = '" .$this->_data['duration']. "';";
		$str .= $nameObj.".".$name.".medium = '".$this->_data['medium']."';";

		return $str;
	}

	public function printTrace()
	{
		echo "traceTracker(tripTo".$this->_nom.", map);\n";
	}

}

?>
