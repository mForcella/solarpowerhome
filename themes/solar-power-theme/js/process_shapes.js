
jQuery(document).ready(function($) {

	// panel name and dimensions in cm
	let panels = {
		"REC Pure 400" : [181.2, 101.6]
	};

	let canvas_max_width = $("#canvases").width();

	var polygon_count = 0; // iterator for setting canvas ID values
	var polygon_vals = [];
	var heading_vals = [];

	$(".polygon").each(function(){
		// console.log($(this).val());
		polygon_vals.push($(this).val());
	});
	$(".heading").each(function(){
		// console.log($(this).val());
		heading_vals.push($(this).val());
	});

	var polygons = [];
	// parse shape values
	for (var i in polygon_vals) {
		let vals = {};
		let l_w = [];
		let s1 = parseFloat(polygon_vals[i].split(",")[0]);
		let s2 = parseFloat(polygon_vals[i].split(",")[1]);
		// make sure length (larger number) is first
		if (s1 >= s2) {
			l_w.push(s1);
			l_w.push(s2);
		} else {
			l_w.push(s2);
			l_w.push(s1);
			// adjust heading if shape is flipped
			heading_vals[i] = parseFloat(heading_vals[i]) + 90;
		}
		vals['length_width'] = l_w;
		vals['pitch'] = 40;
		vals['left_right'] = false;
		polygons.push(vals);
	}
	// adjust polygon values from pitch
	var adjustedPolygons = getAdjustedPolygonValues();
	setCanvases();

	// select function for panel dropdown
	$("#panel_select").on("change", function() {
		let panel_dimensions = panels[$(this).val()];
		$("#panel_specs").html("");
		// show panel specs
		let label = $('<label />', {
			'class': "control-label"
		}).appendTo($("#panel_specs"));
		$('<p />', {
			'text': "Panel Specifications:"
		}).appendTo(label);
		$('<p />', {
			'text': "Length: "+panel_dimensions[0]+"cm"
		}).appendTo(label);
		$('<p />', {
			'text': "Width: "+panel_dimensions[1]+"cm"
		}).appendTo(label);

		// fill canvases with panels
		for (var i = 0; i < polygon_count; i++) {
			let canvas = document.getElementById("canvas_"+i);

			// get relative length and width of panels
			let area_width = adjustedPolygons[i][0];
			let canvas_width = canvas.offsetWidth - 4;
			let canvas_height = canvas.offsetHeight - 4;
			let pixels_per_cm = (canvas_width / (area_width * 100)).toFixed(3);
			// let panel_width_pixels = (panel_dimensions[1] * pixels_per_cm).toFixed(3);

			let landscape = $("#dir_"+i).hasClass("fa-arrows-left-right");
			let panels_x = Math.floor((canvas_width)/(panel_dimensions[landscape ? 0 : 1]*pixels_per_cm));
			let panels_y = Math.floor((canvas_height)/(panel_dimensions[landscape ? 1 : 0]*pixels_per_cm));

			var startX = 0;
			var startY = 0;
			var panelsLaid = 0;
			while (panelsLaid < panels_x * panels_y) {
		    	panelsLaid++;
		    	startX = startX + panel_dimensions[landscape ? 0 : 1] * pixels_per_cm;
	    		if (panelsLaid % panels_x == 0) {
		    		startX = 0;
		    		startY = startY + panel_dimensions[landscape ? 1 : 0] * pixels_per_cm;
		    	}
			}
		    // set slider max value from panel count
		    $("#s_"+i).attr("max", panelsLaid);
		    $("#s_"+i).val(panelsLaid);
		    $("#pcount_"+i).val(panelsLaid);

			// set panels onto canvas
		    drawPanels(canvas, area_width, panel_dimensions, $("#s_"+i).val(), landscape);
		}

	});
	
	$("#panel_select").trigger("change");

	// TODO if no shapes are found, enable manually setting dimensions
	if (polygon_count == 0) {
		console.log("no shapes!");
	}

		// draw the selected solar panels onto a canvas
	function drawPanels(canvas, areaWidth, panelDimensions, panelSelected, landscape) {
		let ctx = canvas.getContext("2d");
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		ctx.beginPath();
		ctx.strokeStyle = "black";
		ctx.lineWidth = 2;
		ctx.fillStyle = "darkgray";

		// get relative length and width of panels
		let canvas_width = canvas.offsetWidth - 4;
		let canvas_height = canvas.offsetHeight - 4;
		let pixels_per_cm = (canvas_width / (areaWidth * 100)).toFixed(3);
		// let panel_width_pixels = (panelDimensions[1] * pixels_per_cm).toFixed(3);

		// swap panel dimensions if pitch direction has been changed (landscape)
		let panels_x = Math.floor((canvas_width)/(panelDimensions[landscape ? 0 : 1]*pixels_per_cm));
		let panels_y = Math.floor((canvas_height)/(panelDimensions[landscape ? 1 : 0]*pixels_per_cm));

		// set shapes onto canvas
		var startX = 0;
		var startY = 0;
		var panelsLaid = 0;
		while (panelsLaid < panels_x * panels_y) {
	    	panelsLaid++;
	    	if (panelsLaid > panelSelected) {
	    		ctx.beginPath();
				ctx.fillStyle = "lightgray";
	    	}
	    	ctx.rect(startX, startY, panelDimensions[landscape ? 0 : 1] * pixels_per_cm, panelDimensions[landscape ? 1 : 0] * pixels_per_cm);
	    	ctx.fill();
	    	ctx.stroke();
	    	startX = startX + panelDimensions[landscape ? 0 : 1] * pixels_per_cm;
	    	if (panelsLaid % panels_x == 0) {
	    		startX = 0;
	    		startY = startY + panelDimensions[landscape ? 1 : 0] * pixels_per_cm;
	    	}
		}
	}

	// create each panel array
	function setCanvases() {
		polygon_count = 0;

		// clear canvases
		$("#canvases").html("");

		for (var i in adjustedPolygons) {

			let length = parseFloat(adjustedPolygons[i][0]);
			let width = parseFloat(adjustedPolygons[i][1]);

			let new_canvas = cloneCanvas(length, width);
	    	$(new_canvas).attr("id", "canvas_"+polygon_count);

			// append array info
			let row1 = $('<div />').appendTo($("#canvases"));
			$('<p />', {
				'class': "control-label title center",
				'text': "Array Area #" + (polygon_count+1)
			}).appendTo($(row1));

			// COLUMN 1
			let row2 = $('<div />').appendTo($("#canvases"));
			let col = $('<div />', {
				'class': "col col-xs-6"
			}).appendTo($(row2));
			let row = $('<div />').appendTo($(col));
			let label = $('<label />', {
				'class': "control-label",
				'text': "Length (m):"
			}).appendTo($(col));
			$('<i />', {
				'class': "fa-solid fa-arrows-left-right"
			}).appendTo($(label));
			let length_input = $('<input />', {
				'class': "form-control",
				'type': "number",
				'min': 0,
				'value': length,
				'readOnly': true
			}).appendTo($(col));

			row = $('<div />').appendTo($(col));
			label = $('<label />', {
				'class': "control-label",
				'text': "Width (m):"
			}).appendTo($(row));
			$('<i />', {
				'class': "fa-solid fa-arrows-up-down"
			}).appendTo($(label));
			let width_input = $('<input />', {
				'class': "form-control",
				'type': "number",
				'min': 0,
				'value': width,
				'readOnly': true
			}).appendTo($(row));

			row = $('<div />').appendTo($(col));
			$('<label />', {
				'class': "control-label",
				'text': "Roof Pitch:"
			}).appendTo($(row));
			let pitch_val = $('<input />', {
				'class': "form-control",
				'id': "pitch_"+polygon_count,
				'type': "number",
				'min': 0,
				'max': 90,
				'value': polygons[polygon_count]['pitch']
			}).appendTo($(row));
			$(pitch_val).on("change", function() {
				let id = this.id.split("pitch_")[1];
				polygons[id]['pitch'] = $(this).val();
				// recalculate polygons and redraw shapes
				adjustedPolygons = getAdjustedPolygonValues();
				setCanvases();
				$("#panel_select").trigger("change");
			});

			row = $('<div />').appendTo($(col));
			$('<label />', {
				'class': "control-label",
				'text': "Direction of Pitch:"
			}).appendTo($(row));
			let pitch_dir = $('<i />', {
				'class': polygons[polygon_count]['left_right'] ? "fa-solid arrows fa-arrows-left-right" : "fa-solid arrows fa-arrows-up-down",
				'id': "dir_"+polygon_count
			}).appendTo($(row));
			$(pitch_dir).on("click", function() {
				$(this).toggleClass("fa-arrows-up-down");
				$(this).toggleClass("fa-arrows-left-right");
				let id = this.id.split("dir_")[1];
				polygons[id]['left_right'] = $(this).hasClass("fa-arrows-left-right");
				// recalculate polygons and redraw shapes
				adjustedPolygons = getAdjustedPolygonValues();
				setCanvases();
				$("#panel_select").trigger("change");
			});

			// COLUMN 2 - compass heading
			col = $('<div />', {
				'class': "col col-xs-6"
			}).appendTo($(row2));
			let compass = $('<img />', {
				'id': "compass_"+polygon_count,
				'class': "compass",
				'src': '/wp-content/uploads/2023/11/compass.svg'
			}).appendTo($(col));
			let heading = parseFloat(heading_vals[i]);
			rotateCompass(compass, heading);

			// COLUMN 1 - PANEL COUNT
			let row3 = $('<div />').appendTo($("#canvases"));
			col = $('<div />', {
				'class': "col-xs-4 panel-col"
			}).appendTo($(row3));
			$('<label />', {
				'class': "control-label",
				'text': "Panels Placed:"
			}).appendTo($(col));
			$('<input />', {
				'class': "form-control panel-count",
				'id': "pcount_"+polygon_count,
				'readOnly': true
			}).appendTo($(col));

			// COLUMN 2 - SLIDER
			col = $('<div />', {
				'class': "col-xs-8 slider-col"
			}).appendTo($(row3));
			let slider = $('<input />', {
				'id': "s_"+polygon_count,
				'class': "slider",
				'type': "range",
				'min': 0
			}).appendTo($(col));
			$(slider).on("input", function() {
				setSlider($(this).val(), this.id.split("_")[1]);
			});

			// TODO add ruler along canvas edge to show dimensions
	    	$("#canvases").append(new_canvas);
	    	$(new_canvas).show();
			polygon_count++;

		}
	}

	// set the compass icon rotation based on a heading
	function rotateCompass(compass, heading) {
	    $(compass).css({ WebkitTransform: 'rotate(' + heading + 'deg)'});
	    $(compass).css({ '-moz-transform': 'rotate(' + heading + 'deg)'});
	}

	// adjust polygon values based on pitch and direction
	function getAdjustedPolygonValues() {
		let adjusted = [];
		for (var i in polygons) {
			let l_w = JSON.parse(JSON.stringify(polygons[i]['length_width']));
			let left_right = $("#dir_"+i).hasClass("fa-arrows-left-right") ? true : false;
			let pitch = $("#pitch_"+i).val() == undefined ? 40 : parseFloat($("#pitch_"+i).val());
			if (left_right) {
				l_w[0] = lengthWithPitch(l_w[0], pitch)
			} else {
				l_w[1] = lengthWithPitch(l_w[1], pitch)
			}
			adjusted.push(l_w);
		}
		return adjusted;
	}

	// create a new shape drawing canvas
	function cloneCanvas(length, width) {
		let maxLength = getMaxLength();
		let scale = length / maxLength;
		let canvas_width = canvas_max_width * scale;
		let canvas_height = width / length * canvas_max_width * scale;
		let new_canvas = $("#shape_canvas").clone();
		$(new_canvas).attr("width", canvas_width);
		$(new_canvas).attr("height", canvas_height);
		return new_canvas;
	}

	// redraw the array panels based on the slider value
	function setSlider(val, id) {
		let canvas = document.getElementById("canvas_"+id);
		let areaWidth = adjustedPolygons[id][0];
		let panelDimensions = panels[$("#panel_select").val()];
		let panelSelected = val;
		drawPanels(canvas, areaWidth, panelDimensions, panelSelected, $("#dir_"+id).hasClass("fa-arrows-left-right"));
		$("#pcount_"+id).val(val);
		// TODO allow click to select/deselect specific panels - store panel selections in 2D arrays
	}

		// calculate a length taking pitch into account
	function lengthWithPitch(length, pitch) {
		let radians = pitch * (Math.PI / 180);
		return (length / Math.cos(radians)).toFixed(2);
	}

	// get the maximum length from the current polygon values
	function getMaxLength() {
		let maxLength = 0;
		for (var i in adjustedPolygons) {
			let length = parseFloat(adjustedPolygons[i][0]);
			maxLength = length > maxLength ? length : maxLength;
		}
		return maxLength;
	}

	function getQuote() {
		// get config information - panel model, inverter model, panel count
		let panel = $("#panel_select").val();
		let inverter = $("#inverter_select").val();
		var panel_count = 0;
		$(".panel-count").each(function() {
			panel_count += parseInt($(this).val());
		});
		// submit form values
		$("#panel_count").val(panel_count);
		$("#config_vals").submit();
	}

});
