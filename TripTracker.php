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

		for($i=0;$i < count($tab);$i=$i+4)
		{
			$this->_points[] = new Point($tab[$i],$tab[$i+1],"",$tab[$i+2], $tab[$i+3]);
			$this->_nb++;
		}
	}

	public function setData($data)
        {
                $this->_nom = $data['name'];
                $this->_year = $data['year'];
		$this->_data = $data;
        }

	public function hasElevation() {

		for($i=0;$i<$this->_nb;$i++)
                {
                        if($this->_points[$i]->getEle() != 0) return 1;
                }

		return 0;

	}

	public function hasSpeed() {
		if($this->_points[0]->getDate() != 0) return 1;
		return 0;
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

	public function getDistanceBetween($nb, $round = true) {


		if($nb <= 0) return 0;
		if($nb > $this->_nb) return 0;

		$p1 = $this->_points[$nb-1];
		$p2 = $this->_points[$nb];
		$total = GPS::getDistance($p1->getLat(),$p1->getLon(),$p2->getLat(),$p2->getLon());

		if($round)
	                return round($total,3);

		return $total;

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
			$total += $this->getDistanceBetween($i, false);
			$point = $this->_points[$i];
			echo "[".round($total,3).",".$point->getEle()."]";
			
			if($i != $this->_nb-1)
			{
				echo ",";
			}
		}

		echo "];\n\n";

	}


	private function calculate_average($arr) {

		$total = 0;
		$count = count($arr);

		foreach ($arr as $value) {
			$total += $value;
		}

		$average = ($total/$count);
		return $average;
	}


	public function getSpeed() {

		if($this->_points[0]->getDate() == 0) return;

		echo "var speed_tripTo".$this->_nom." = [ [0,0], ";
		$total = 0;
		$arraySpeeds = array();

		for($i=1;$i<$this->_nb;$i++) {
			$d = $this->getDistanceBetween($i, false);
			$total += $d;
			$t = $this->_points[$i]->getDate() - $this->_points[$i-1]->getDate();
			if($t > 0) {
				$s = round($d*1000.0/($t)*3.6, 2);

				// we compute the 4 items moving average to smooth the speed
				if(sizeof($arraySpeeds) > 4) array_shift($arraySpeeds);

				array_push($arraySpeeds, $s);

				echo "[".round($total,3).",".round($this->calculate_average($arraySpeeds), 2)."]";

				if($i != $this->_nb-1)
				{
					echo ",";
				}
			}
		}

		echo "];\n\n";
	}


	public function getAll() {

		echo "var all_".$this->getName()." = [ ";

		$currentTime = 0;

		$timeStart = $this->_points[0]->getDate();
		$total = 0;
                $arraySpeeds = array();

		for($i=0;$i<$this->_nb;$i++) {
			$d = $this->getDistanceBetween($i, false);

			echo "{'distance':".round($total,3).", 'latitude':".$this->_points[$i]->getLat().", 'longitude':".$this->_points[$i]->getLon().", 'elevation':".$this->_points[$i]->getEle();

			$total += $d;

			if($timeStart > 0) {

				if($i == 0) {

					echo ", 'time':0, 'speed':0";

				} else {

					$t = $this->_points[$i]->getDate() - $this->_points[$i-1]->getDate();
					$tTotal = $this->_points[$i]->getDate() - $timeStart;
					if($t > 0 && $tTotal >= $currentTime) {
						$s = round($d*1000.0/($t)*3.6, 2);
						$currentTime = $tTotal;
						// we compute the 4 items moving average to smooth the speed
						if(sizeof($arraySpeeds) > 4) array_shift($arraySpeeds);

						array_push($arraySpeeds, $s);

						echo ",'time':".$currentTime.", 'speed':".round($this->calculate_average($arraySpeeds), 2);
		                        }

				}

			}

			echo "}";

			if($i != $this->_nb-1) echo ",";

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
		$str .= $nameObj.".".$name.".hasElevation = ".$this->hasElevation().";";
		$str .= $nameObj.".".$name.".hasSpeed = ".$this->hasSpeed().";";

		return $str;
	}

	public function printTrace()
	{
		echo "traceTracker(tripTo".$this->_nom.", map);\n";
	}

}

?>
