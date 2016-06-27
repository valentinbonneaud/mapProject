<!DOCTYPE html>
<html>

	<head>
		<title>My hikes !</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />

		<style type="text/css">
			html { height: 100% }
			body { height: 100%; margin: 0; padding: 0 }
			#map-canvas { height: 100%; width: 100%; offset: -10 } 

		</style>
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=drawing&key=AIzaSyCxyaIp1mOe-MnCtZbtj2AyApHj6hoUJWM&sensor=true"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
	   
		<!-- Latest compiled and minified CSS -->
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css">

		<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-T8Gy5hrqNKT+hzMclPo118YTQO6cYprQmhrYwIiQ/3axmI1hQomh7Ud2hPOy8SP1" crossorigin="anonymous">

		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

		<script src="flot/jquery.flot.js"></script>
		<script src="flot/jquery.flot.tooltip.min.js"></script>
		<script src="flot/jquery.flot.resize.js"></script>
		<script src="flot/jquery.flot.pie.js"></script>
		<script src="flot/jquery.flot.time.js"></script>

		<script type="text/javascript">

			var labelSpeed = "Speed [km/h]"
			var labelAltitude = "Elevation [m]"

			<?php

				include_once 'TripTracker.php';
				include_once "GPS.php";

				// Extract the trips from the tracker (more more dots)


				$dir = "/var/www/bonneaud/randos/gpx";

				$dh  = opendir($dir);
				while (false !== ($filename = readdir($dh))) 
				{

					if(!is_file($dir.'/'.$filename) || !strpos($filename, "gpx")) continue;

					$trip = simplexml_load_file($dir.'/'.$filename);

					$data = array();
					$data['year'] = $trip->metadata->YEAR;
					$data['medium'] = $trip->metadata->MEDIUM;
					$data['country'] = $trip->metadata->COUNTRY_EN;
					$data['area'] = $trip->metadata->AREA;
					$data['name'] = $trip->metadata->NAME;
					$data['duration'] = $trip->metadata->DURATION;
					$data['title'] = $trip->metadata->TITLE;
					$data['description'] = $trip->metadata->DESCRIPTION;
	
					// We extract the differents gps locations

					$stops = array();
					$tracks = $trip->trk->trkseg;
					$i = 1; 

					foreach ($tracks as $track)
					{
						foreach($track->trkpt as $pos) 
						{
							$stops[]=floatval($pos['lat']);
							$stops[]=floatval($pos['lon']);
							$stops[]=floatval($pos->ele);
							if($pos->time != "")
								$stops[] = strtotime($pos->time);
							else
								$stops[]=0;
							$i++;
						}
					}

					$t = new TripTracker($stops);
					$t->setData($data);
					$trips[] = $t;

				} 

				for($i=0;$i < count($trips);$i++)
				{
					// we print all the arrays of the gps positions and names
					$trip = $trips[$i];
					$trip->printItineraire();
					$trip->getElevation();
					$trip->getSpeed();
					$trip->getAll();
				}

			?>


			function euroFormatter(v, axis) {
				return v.toFixed(axis.tickDecimals)+"km" ;
			}


			var trips = {};
			var currentTrip, currentEle, currentSpeed, currentMap;
			$( document ).ready(function() {

				<?php

					for($i=0;$i < count($trips);$i++)
					{
						// we print all the arrays of the gps positions and names
						$trip = $trips[$i];
						echo $trip->getObjectJS("trips");

					}

				?>
	
				$("#tripList").html("")

				$.each(trips, function(key, value) {

					var icons = ""

					if(value.hasElevation) icons = '<i class="fa fa-area-chart" aria-hidden="true"></i>'
					if(value.hasSpeed) icons += '<i class="fa fa-tachometer" style="padding-left: 10px;" aria-hidden="true"></i>'

					var line = '<div class="panel panel-default">';
					line += '    <div class="panel-heading">';
					line += '        <h3 class="panel-title"><a href="#'+value.idRando+'" class="linkRando" idArray="'+value.idRando+'">'+value.title+' <i class="fa fa-caret-down" aria-hidden="true"></i><span style="float: right;">'+icons+'</span></a></h3>';
					line += '    </div> ';
					line += '    <div id="'+value.idRando+'_div" class="panel-body panelRando hidden"> ';
					line += value.description+'<br/><br />';
					line += '        Country: '+value.country+'<br />';
					line += '        Length: '+value.length+'<br />';
					line += '        Duration: '+value.duration+'<br />';
					line += '        Medium: '+value.medium+'<br />';
					line += '    </div>';
					line += '</div>';
					$("#tripList").append(line)
				})

				$("#linkGeneral").click(function(e) {
					e.preventDefault();
					$(".panelRando").addClass("hidden")
					initialize()
				})			
    
				$(".linkRando").click(function(e) {
//					e.preventDefault();
					$(".panelRando").addClass("hidden")
					$("#"+$(this).attr("idArray")+"_div").removeClass("hidden")
					initializeRando(window[$(this).attr("idArray")], window["elevation_"+$(this).attr("idArray")], window["speed_"+$(this).attr("idArray")])
				})

				var url = $(location).attr('href');
				var lm = url.split('#');
				if(lm.length > 1) {
					console.log(lm[1])
					$(".panelRando").addClass("hidden")
					$("#"+lm[1]+"_div").removeClass("hidden")
					initializeRando(window[lm[1]], window["elevation_"+lm[1]], window["speed_"+lm[1]])
				} else {
					initialize()
				}
			})

function setLinkSpeed() {
			$("#linkSpeed").click(function(e) {
				e.preventDefault()

				if($(this).html() == "Hide speed") {
					$(this).html("Show speed")

					$.plot($("#flot-line-chart-multi"), [{
							data: currentElevation,
							label: labelAltitude
						},], {
							xaxes: [ {}],
							yaxes: [{ }, {
							// align if we are to the right
							alignTicksWithAxis: 1,
							position: "right",
							tickFormatter: euroFormatter
						}],
						legend: {
							position: 'sw'
						},
						colors: ["#1ab394"],
						grid: {
							color: "#999999",
							hoverable: true,
							clickable: true,
							tickColor: "#D4D4D4",
							borderWidth:0,
							hoverable: true //IMPORTANT! this is needed for tooltip to work,
						},
						tooltip: true,
						tooltipOpts: {
							content: "Altitude : %y m @ %x km",

							onHover: function(flotItem, $tooltipEl) {
								if(markerOnMap != null) markerOnMap.setMap(null)
								markerOnMap = new google.maps.Marker({
									position: currentTrip[flotItem['dataIndex']],
									map: currentMap
								});
							}
						}
					});


				} else {
					$(this).html("Hide speed")

					$.plot($("#flot-line-chart-multi"), [{
							data: currentElevation,
							label: labelAltitude,
						}, {
							data: currentSpeed,
							yaxis: 2,
							label: labelSpeed,
						}], {
							xaxes: [ {}],
							yaxes: [{}, {
								// align if we are to the right
								alignTicksWithAxis: 1,
								position: "right"
							}],
							legend: {
								position: 'sw'
							},
							colors: ["#1ab394"],
							grid: {
								color: "#999999",
								hoverable: true,
								clickable: true,
								tickColor: "#D4D4D4",
								borderWidth:0,
								hoverable: true //IMPORTANT! this is needed for tooltip to work,
							},
							tooltip: true,
							tooltipOpts: {
								content: function(flotItem, $tooltipEl) {
									if(flotItem == labelAltitude)
										return "Altitude : %y m @ %x km"

									return "Speed : %y km/h @ %x km"
								},

								onHover: function(flotItem, $tooltipEl) {
									if(markerOnMap != null) markerOnMap.setMap(null)

									var indexArray = flotItem['dataIndex']

									if(flotItem['series']['label'] == labelSpeed) {
										indexArray = currentElevation.map(function(el){return el[0];}).indexOf(flotItem['datapoint'][0])
									}

									markerOnMap = new google.maps.Marker({
										position: currentTrip[indexArray],
										map: currentMap
									});
								}
							}
						});
					}
				})


}

function setLinkElevation() {
			$("#linkEle").click(function(e) {
				e.preventDefault()

				if($(this).html() == "Hide elevation") {
					$(this).html("Show elevation")

					$.plot($("#flot-line-chart-multi"), [{
							data: currentSpeed,
							label: labelSpeed
						},], {
							xaxes: [ {}],
							yaxes: [{ }, {
							// align if we are to the right
							alignTicksWithAxis: 1,
							position: "right",
							tickFormatter: euroFormatter
						}],
						legend: {
							position: 'sw'
						},
						colors: ["#1ab394"],
						grid: {
							color: "#999999",
							hoverable: true,
							clickable: true,
							tickColor: "#D4D4D4",
							borderWidth:0,
							hoverable: true //IMPORTANT! this is needed for tooltip to work,
						},
						tooltip: true,
						tooltipOpts: {
							content: "Altitude : %y m @ %x km",

							onHover: function(flotItem, $tooltipEl) {
								if(markerOnMap != null) markerOnMap.setMap(null)

								var indexArray = flotItem['dataIndex']

								if(flotItem['series']['label'] == labelSpeed) {
									indexArray = currentElevation.map(function(el){return el[0];}).indexOf(flotItem['datapoint'][0])
								}

								markerOnMap = new google.maps.Marker({
									position: currentTrip[indexArray],
									map: currentMap
								});
							}
						}
					});


				} else {
					$(this).html("Hide elevation")

					$.plot($("#flot-line-chart-multi"), [{
							data: currentElevation,
							label: labelAltitude
						}, {
							data: currentSpeed,
							yaxis: 2,
							label: labelSpeed,
						}], {
							xaxes: [ {}],
							yaxes: [{}, {
								// align if we are to the right
								alignTicksWithAxis: 1,
								position: "right"
							}],
							legend: {
								position: 'sw'
							},
							colors: ["#1ab394"],
							grid: {
								color: "#999999",
								hoverable: true,
								clickable: true,
								tickColor: "#D4D4D4",
								borderWidth:0,
								hoverable: true //IMPORTANT! this is needed for tooltip to work,
							},
							tooltip: true,
							tooltipOpts: {
								content: function(flotItem, $tooltipEl) {
									if(flotItem == labelAltitude)
										return "Altitude : %y m @ %x km"

									return "Speed : %y km/h @ %x km"
								},

								onHover: function(flotItem, $tooltipEl) {
									if(markerOnMap != null) markerOnMap.setMap(null)

									var indexArray = flotItem['dataIndex']

									if(flotItem['series']['label'] == labelSpeed) {
										indexArray = currentElevation.map(function(el){return el[0];}).indexOf(flotItem['datapoint'][0])
									}

									markerOnMap = new google.maps.Marker({
										position: currentTrip[indexArray],
										map: currentMap
									});
								}
							}
						});
					}
				})


}

			function initializeRando(trip, elevation, speed) {

				$("#graphAltitude").removeClass("hidden")

				var mapOptions = {
					zoom: 12,
					mapTypeId: google.maps.MapTypeId.TERRAIN
				};
				var map = new google.maps.Map(document.getElementById("map-canvas"),mapOptions);

				traceTracker(trip, elevation, speed, map);
	
				var bounds = new google.maps.LatLngBounds();

				for(i = 0; i < trip.length; i++)
				{
					bounds.extend(trip[i]);
				}

				currentTrip = trip
				currentElevation = elevation
				currentSpeed = speed
				currentMap = map
	
				map.fitBounds(bounds);
				map.panToBounds(bounds);
			}

			function initialize() {

				$(".panelRando").addClass("hidden")
				$("#graphAltitude").addClass("hidden")

				var mapOptions = {
					zoom: 3,
					mapTypeId: google.maps.MapTypeId.TERRAIN
				};
				var map = new google.maps.Map(document.getElementById("map-canvas"),mapOptions);
				var bounds = new google.maps.LatLngBounds();


				$.each(trips, function(key, value) {
					var marker = new google.maps.Marker({
						position: window[value.idRando][0],
						title:value.title
					});

					google.maps.event.trigger(marker, 'click');

					google.maps.event.addListener( marker, 'click', function(e) {
						$(".panelRando").addClass("hidden")
	                                        $("#"+value.idRando+"_div").removeClass("hidden")
        	                                initializeRando(window[value.idRando], window["elevation_"+value.idRando], window["speed_"+value.idRando])
				        });
				   
					marker.setMap(map);
					bounds.extend(window[value.idRando][0]);

				})
	
				map.fitBounds(bounds);
				map.panToBounds(bounds);
			}

			var markerOnMap = null

			function traceTracker(flightPlanCoordinates, elevation, speed, map) 
			{
				for(i = 0; i < flightPlanCoordinates.length-1; i++)
				{
					var flightPath = new google.maps.Polyline({
					path: [flightPlanCoordinates[i],flightPlanCoordinates[i+1]],
					geodesic: true,
					strokeColor: '#FF0000',
					strokeOpacity: 1.0,
					strokeWeight: 2,
					});

					flightPath.setMap(map); 
				}

				position = "right"

				if(speed == undefined) {

					$("#linksGraph").css("height","10%")
					$("#linksGraph").html("<a style='padding-right: 6px;' id='linkTime' href=''>Time</a>")
					$("#container_graph").css("height","90%")

					$.plot($("#flot-line-chart-multi"), [{
						data: elevation,
						label: labelElevation
					},], {
						xaxes: [ {}],
						yaxes: [{ }, {
							// align if we are to the right
							alignTicksWithAxis: position == "right" ? 1 : null,
							position: position,
							tickFormatter: euroFormatter
						}],
						legend: {
							position: 'sw'
						},
						colors: ["#1ab394"],
						grid: {
							color: "#999999",
							hoverable: true,
							clickable: true,
							tickColor: "#D4D4D4",
							borderWidth:0,
							hoverable: true //IMPORTANT! this is needed for tooltip to work,
						},
						tooltip: true,
						tooltipOpts: {
							content: "Altitude : %y m @ %x km",
							//xDateFormat: "%y-%0m-%0d",
	
							onHover: function(flotItem, $tooltipEl) {
								if(markerOnMap != null) markerOnMap.setMap(null)
									markerOnMap = new google.maps.Marker({
										position: flightPlanCoordinates[flotItem['dataIndex']],
										map: map
									});
							}
						}
					});
				
				} else {

					$("#linksGraph").css("height","10%")
					$("#linksGraph").html("<a style='padding-right: 6px;' id='linkSpeed' href=''>Hide speed</a><a style='padding-right: 6px;' id='linkEle' href=''>Hide elevation</a><a style='padding-right: 6px;' id='linkTime' href=''>Time</a>")
					$("#container_graph").css("height","90%")
	setLinkSpeed()				
	setLinkElevation()
					$.plot($("#flot-line-chart-multi"), [{
						data: elevation,
						label: labelAltitude,
					},
					{
						data: speed,
						yaxis: 2,
						label: labelSpeed,
					}], {
						xaxes: [ {}],
						yaxes: [{}, {
							// align if we are to the right
							alignTicksWithAxis: position == "right" ? 1 : null,
							position: position,
						}],
						legend: {
							position: 'sw'
						},
						colors: ["#1ab394"],
						grid: {
							color: "#999999",
							hoverable: true,
							clickable: true,
							tickColor: "#D4D4D4",
							borderWidth:0,
							hoverable: true //IMPORTANT! this is needed for tooltip to work,
						},
						tooltip: true,
						tooltipOpts: {
							content: function(flotItem, $tooltipEl) {
									if(flotItem == labelAltitude)
										return "Altitude : %y m @ %x km"
	
									return "Speed : %y km/h @ %x km"
								},
							//xDateFormat: "%y-%0m-%0d",
	
							onHover: function(flotItem, $tooltipEl) {
								if(markerOnMap != null) markerOnMap.setMap(null)

								var indexArray = flotItem['dataIndex']

								if(flotItem['series']['label'] == labelSpeed) {
									indexArray = elevation.map(function(el){return el[0];}).indexOf(flotItem['datapoint'][0])
								}

								markerOnMap = new google.maps.Marker({
									position: flightPlanCoordinates[indexArray],
									map: map
								});
							}
						}
					});

				}

			}
		</script>
	</head>
	<body>
		<div class="row" style="height: 100%;">
			<div class="col-md-3" style="height: 100%; overflow-y: scroll; ">
				<button type="button" id="linkGeneral" class="btn btn-default" style="margin-left: 20px; margin-bottom: 15px; margin-top: 15px;">General view</button>
				<div style="margin-left: 20px;" id="tripList"></div>
	
			</div>
			<div class="col-md-9" style="height: 100%;">
				<div id="map-canvas" style="height: 80%;"></div>
				<div id="graphAltitude" style="height: 20%;">
					<div style='height:0%; text-align: right;' id='linksGraph'></div>
					<div class="flot-chart" id='container_graph'  style="height: 100%;" >
                                		<div class="flot-chart-content" id="flot-line-chart-multi" style="height: 100%;" ></div>
                            		</div>
				</div>
			</div>
		</div>
	</body>
</html>
