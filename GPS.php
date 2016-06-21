<?php

class GPS
{
	public static function getDistance($lat1,$lon1,$lat2,$lon2)
	{
		$R = 6371; // km
		$dLat = deg2rad($lat2-$lat1);
		$dLon = deg2rad($lon2-$lon1);
		$lat1 = deg2rad($lat1);
		$lat2 = deg2rad($lat2);

		$a = sin($dLat/2) * sin($dLat/2) + 
			sin($dLon/2) * sin($dLon/2) * cos($lat1) * cos($lat2); 
		$c = 2 * atan2(sqrt($a), sqrt(1-$a)); 
	
		return $R * $c;
	}
}

?>
