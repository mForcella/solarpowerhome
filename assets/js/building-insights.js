
var roofSegments;
var roofMarkers;
var segmentMarkers = [];

// building insights data returned from API call
function setBuildingInsightsData(data) {

	$("#roof_segments").css("display", "inline-block");
    if ($(".map-col").hasClass("col-md-12")) {
	    $(".map-col").toggleClass("col-md-12");
	    $(".map-col").toggleClass("col-md-8");
    }

	// hide/show inputs and elements
	$('.auto-control').show(500);
	$(".manual-control").hide(500);
	$("#area_inputs").show(500);

	// reset map heading to zero and enable map interaction
	map.setHeading(0);
	$("#map").css("pointer-events","auto");
	$("#shape_canvas").css("pointer-events","none");
	$(".shape-canvas").css("pointer-events","none");

	// display data from insights
	loadChartData(data.solarPotential.wholeRoofStats.sunshineQuantiles, "total_roof_chart");
	$("#roof_area").html(data.solarPotential.wholeRoofStats.areaMeters2.toFixed(1));

	// add pin to each roof segment - show stats on click
	roofSegments = data.solarPotential.roofSegmentStats;
	for (var i in roofMarkers) {
		roofMarkers[i].setMap(null);
	}
	$(".segment-control").html("");
	roofMarkers = [];
	for (var i in roofSegments) {
		// add index value for marker labeling
		roofSegments[i]['index'] = parseInt(i)+1;
		addMarker(roofSegments[i], new google.maps.LatLng(roofSegments[i].center.latitude, roofSegments[i].center.longitude));
		// create checkbox list
		let row = $('<div />', {
			'class': "segment-row"
		}).appendTo($("#segment_inputs"));
		let checkbox = $('<input />', {
			'type': "checkbox",
			'class': "segment-checkbox",
			'id': "checkbox_"+roofSegments[i]['index']
		}).appendTo(row);
		$('<label />', {
			'class': "control-label segment-label",
			'for': "checkbox_"+roofSegments[i]['index'],
			'html': "Segment "+roofSegments[i]['index']+" - Area: "+roofSegments[i].stats.areaMeters2.toFixed(1)+" m<sup>2</sup>"
		}).appendTo(row);

		// change markers to yellow on select - update roofSegments array, set 'checked' value to true
		checkbox.change(function() {
			checkboxChange(this.checked, this.id.split("checkbox_")[1], data.solarPotential.solarPanels);
		});
	}

}

// select/unselect function for roof segments
function checkboxChange(checked, roof_segment_id, solarPanels) {
	let marker = roofMarkers[parseInt(roof_segment_id)-1];
	// if marker icon is blue, don't change color
	if (marker.icon.url != "/image/map_marker_blue.png") {
		let icon = {
		    url: checked ? "/image/map_marker_yellow.png" : "/image/map_marker_red.png",
		    scaledSize: new google.maps.Size(26, 42)
		};
		marker.setIcon(icon);
	}
	roofSegments[parseInt(roof_segment_id)-1]['checked'] = checked;

	// grab solar panel configs - draw spots on map to show panel locations
	if (!checked) {
		// remove markers from map
		for (var j in segmentMarkers) {
			if (parseInt(segmentMarkers[j].data) == roof_segment_id) {
				segmentMarkers[j].setMap(null);
			}
		}
	} else {
		let northmost = null;
		let southmost = null;
		let segmentPanels = [];
		for (var j in solarPanels) {
			if (solarPanels[j].segmentIndex+1 == roof_segment_id) {
				segmentPanels.push(solarPanels[j]);
				let icon = {
				    url: "/image/red-dot.png",
				    scaledSize: new google.maps.Size(5, 5)
				};
		        let marker = new google.maps.Marker({
		            position: new google.maps.LatLng(solarPanels[j].center.latitude, solarPanels[j].center.longitude),
		            icon: icon,
		            map: map,
		            data: roof_segment_id
		        });
		        segmentMarkers.push(marker);
		        northmost = northmost == null || northmost.center.latitude < solarPanels[j].center.latitude ? solarPanels[j] : northmost;
		        southmost = southmost == null || southmost.center.latitude > solarPanels[j].center.latitude ? solarPanels[j] : southmost;
			}
		}
		// from northmost point, find points in line with heading
		// let azimuth = roofSegments[roof_segment_id-1].azimuthDegrees;
		// drawEdge(azimuth, northmost, segmentPanels);
		// drawEdge(azimuth+90, northmost, segmentPanels);
		// drawEdge(azimuth, southmost, segmentPanels);
		// drawEdge(azimuth+90, southmost, segmentPanels);

		// TODO on segment checked, add inputs to area_inputs form

		// TODO skip configurator page if we have building insights data, use solarPanelConfigs data instead?
		// then we can't choose the panel for the configuration...

	}
}

function loadChartData(solarData, chartID) {
	// console.log(solarData);

	$("#"+chartID).html("");

	let rowData = [];
	for (var i in solarData) {
		let row = [];
		row.push(i*10);
		row.push(solarData[i]);
		rowData.push(row);
	}

	let data = {
        header: ["%", "kWh/kW"],
        rows: rowData
    };

    var chart = anychart.column();
    chart.xAxis().title("%");
    chart.yAxis().title("kWh/kW");
	chart.yScale().softMaximum(2000);
    chart.container(chartID);
    chart.tooltip().titleFormat("");
    chart.tooltip().separator(false);
    chart.tooltip().format("{%value} kWh/kW");
    chart.data(data);
    chart.draw();

    // TODO load empty segment chart with total roof chart
    // can't initialize second chart while first chart is still being drawn? need callback or JS event?
    // if (chartID == "total_roof_chart") {
    // 	loadChartData([], "segment_chart");
    // }
}

// draw a line from the corner point through any along the same heading
function drawEdge(azimuth, corner, solarPanels) {
	// TODO expand edge by the length/width of the solar panels
	let coords = [];
    let coord = {
    	lat: corner.center.latitude,
    	lng: corner.center.longitude
    };
    coords.push(coord);
	for (var j in solarPanels) {
		// compute heading between corner and each point
		var point1 = new google.maps.LatLng(corner.center.latitude, corner.center.longitude);
		var point2 = new google.maps.LatLng(solarPanels[j].center.latitude, solarPanels[j].center.longitude);
		var heading = google.maps.geometry.spherical.computeHeading(point1,point2);
		heading = heading < 0 ? 360 + heading : heading;
		if ( (heading - azimuth < 3 && heading - azimuth > -3) 
			|| (heading - (azimuth+180) < 3 && heading - (azimuth+180) > -3)
			|| (heading - (azimuth-180) < 3 && heading - (azimuth-180) > -3) ) {
	        let coord = {
	        	lat: solarPanels[j].center.latitude,
	        	lng: solarPanels[j].center.longitude
	        };
	        coords.push(coord);
		}
	}

	// add line to map
	let polygon = new google.maps.Polygon({
		paths: coords,
	    strokeColor: "#FF0000",
	    strokeOpacity: 0.8,
	    strokeWeight: 2,
	});
	polygon.setMap(map);
}

function addMarker(roofData, location) {
	let icon = {
	    url: "/image/map_marker_red.png",
	    scaledSize: new google.maps.Size(26, 42)
	};
    let marker = new google.maps.Marker({
        position: location,
        icon: icon,
        map: map,
        label: { color: 'black', fontSize: '10px', fontWeight: '600', text: roofData['index']+"" }
    });
	// show info on click - pitchDegrees, azimuthDegrees, areaMeters2, sunshineQuantiles(max)
    google.maps.event.addListener(marker, 'click', function() {
    	// remove blue highlighting from other markers - if roof segment is checked, use yellow marker instead of red
    	for (var i in roofMarkers) {
			let icon = {
	    		url: roofSegments[i]['checked'] ? "/image/map_marker_yellow.png" : "/image/map_marker_red.png",
			    scaledSize: new google.maps.Size(26, 42)
			};
    		roofMarkers[i].setIcon(icon);
    	}
    	// highlight clicked marker in blue
		let icon = {
		    url: "/image/map_marker_blue.png",
		    scaledSize: new google.maps.Size(26, 42)
		};
    	marker.setIcon(icon);

    	// segment_area, segment_pitch, segment_chart
    	$("#segment_area").html(roofData.stats.areaMeters2.toFixed(1));
    	$("#segment_pitch").html(roofData.pitchDegrees.toFixed(1));
		loadChartData(roofData.stats.sunshineQuantiles, "segment_chart");
	});
    roofMarkers.push(marker);
}
