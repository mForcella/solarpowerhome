<?php 

	// get map values
	$latitude = isset($_POST["latitude"]) ? $_POST["latitude"] : "";
	$longitude = isset($_POST["longitude"]) ? $_POST["longitude"] : "";
	$altitude = isset($_POST["altitude"]) ? $_POST["altitude"] : "";

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, height=device-height,  initial-scale=1.0, user-scalable=no, user-scalable=0"/>
	<meta name="robots" content="noindex">
	<title>Solar Power Home :: Configurator</title>
	<link rel="icon" type="image/png" href="/image/favicon.ico"/>

	<!-- Bootstrap -->
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<!-- Font Awesome -->
	<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
	<!-- Google Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;800&family=Alegreya:ital,wght@0,400;1,400;1,600&family=Merriweather:wght@300;700&display=swap" rel="stylesheet">

	<style>
		html, body {
		    max-width: 100%;
		    overflow-x: hidden;
		}
		.header {
			height: 150px;
			padding: 25px;
		}
		.header img {
			max-height: 100px;
		}
		.content {
			width: 800px;
			margin: 0 auto;
			max-width: 90%;
		}
		.section {
			margin: 30px 0;
		}
		.shape-canvas {
			background-color: lightgray;
			border: 2px solid black;
			display: none;
			margin-bottom: 30px;
		}
		#panel_specs {
			margin-top: 30px;
			margin-left: 30px;
		}
		#canvases p {
			font-weight: bold;
		}
		#canvases label {
			width: 150px;
			padding: 5px 0;
		}
		.panel-col label {
			width: auto !important;
			margin-right: 5px;
		}
		#canvases input.form-control {
			width: 100px;
			display: inline;
		}
		.compass {
			height: 100px;
			margin-left: 50px;
			margin-bottom: 30px;
		}
		.panel-col {
			margin-top: 15px;
			float: right;
			text-align: right;
			padding-right: 0;
		}
		.slider-col {
			margin-top: 15px;
		}
		.slider {
			-webkit-appearance: none;
			width: 100%;
			height: 15px;
			border-radius: 5px;
			background: #d3d3d3;
			outline: none;
			opacity: 0.7;
			-webkit-transition: .2s;
			transition: opacity .2s;
			margin-bottom: 30px;
		}
		.slider::-webkit-slider-thumb {
			-webkit-appearance: none;
			appearance: none;
			width: 25px;
			height: 25px;
			border-radius: 50%;
			background: #04AA6D;
			cursor: pointer;
		}
		.slider::-moz-range-thumb {
			width: 25px;
			height: 25px;
			border-radius: 50%;
			background: #04AA6D;
			cursor: pointer;
		}
		.slider:hover {
			opacity: 1;
		}
		.fa-arrows-up-down, .fa-arrows-left-right {
			margin-left: 15px;
		}
		.arrows {
			font-size: 1.3em;
			cursor: pointer;
			width: 100px;
			text-align: center;
			margin-left: 0;
		}
		.title {
			margin-top: 30px;
			font-size: 1.3em;
		}
		.center {
			text-align: center;
		}
		.margin-bottom {
			margin-bottom: 15px;
		}
		#conf_btn {
			text-transform: uppercase;
			margin-bottom: 50px;
		}
	</style>

</head>

<body>

	<div class="header">
		<img src="/image/logo.png">
	</div>

	<div id="shapes">
		<?php 
			if (isset($_POST["polygons"])) {
				$iterator = 0;
				foreach ($_POST["polygons"] as $shape) {
					echo "<input class='polygon' type='hidden' value='$shape' id='p_$iterator'></input>";
					$iterator++;
				}
				$iterator = 0;
				foreach ($_POST["headings"] as $heading) {
					echo "<input class='heading' type='hidden' value='$heading' id='h_$iterator'></input>";
					$iterator++;
				}
			}
		?>
	</div>

	<div class="content">

		<form id="config_vals" method="post" action="report.php">

			<!-- select panel -->
			<div class="section">
				<label class="control-label">Select your panel type</label>
				<select class="form-control" id="panel_select" name="panel">
					<option value="REC Pure 400">REC Pure 400</option>
				</select>
				<!-- selected panel specifications -->
				<div id="panel_specs"></div>
			</div>

			<!-- select inverter -->
			<div class="section">
				<label class="control-label">Select your inverter type</label>
				<select class="form-control" id="inverter_select" name="inverter">
					<option value="Enphase IQ8M-72-2-US">Enphase IQ8M-72-2-US</option>
				</select>
			</div>

			<input type="hidden" id="segment_count" name="segment_count" value="<?php echo count($_POST["polygons"]) ?>">
			<input type="hidden" name="submit_url" id="submit_url">

			<?php 
				echo "<input type='hidden' value='$latitude' name='latitude'></input>";
				echo "<input type='hidden' value='$longitude' name='longitude'></input>";
				echo "<input type='hidden' value='$altitude' name='altitude'></input>";
			?>

		</form>

		<!-- panel canvases -->
		<div id="canvases"></div>
		
		<!-- canvas for cloning -->
    	<canvas id="shape_canvas" class="shape-canvas"></canvas>

	</div>

	<!-- TODO add manual controls if dimensions are known - no shapes found -->

	<div class="center">
	<button id="conf_btn" type="button" class="btn btn-primary" onclick="getQuote()">Confirm and get quote</button>
	</div>


	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<!-- <script src="/themes/solar-power-theme/js/process_shapes.js"></script> -->

	<script type="text/javascript">

		// panel name and dimensions in cm
		let panels = {
			"REC Pure 400" : [181.2,101.6]
		};

		let canvas_max_width = $("#canvases").width();
		var polygon_count = 0; // iterator for setting canvas ID values
		var polygon_vals = <?php echo json_encode(isset($_POST["polygons"]) ? $_POST["polygons"] : []); ?>;
		var heading_vals = <?php echo json_encode(isset($_POST["headings"]) ? $_POST["headings"] : []); ?>;
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
				// heading_vals[i] = parseFloat(heading_vals[i]) + 90;
			}
			vals['length_width'] = l_w;
			vals['heading'] = heading_vals[i];
			vals['pitch'] = 30;
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
					'class': "col-xs-6"
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
					'text': "Roof Heading:"
				}).appendTo($(row));
				$('<input />', {
					'class': "form-control",
					'id': "heading_"+polygon_count,
					'type': "number",
					'value': polygons[polygon_count]['heading'],
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

				// row = $('<div />').appendTo($(col));
				// $('<label />', {
				// 	'class': "control-label",
				// 	'text': "Direction of Pitch:"
				// }).appendTo($(row));
				// let pitch_dir = $('<i />', {
				// 	'class': polygons[polygon_count]['left_right'] ? "fa-solid arrows fa-arrows-left-right" : "fa-solid arrows fa-arrows-up-down",
				// 	'id': "dir_"+polygon_count
				// }).appendTo($(row));
				// $(pitch_dir).on("click", function() {
				// 	$(this).toggleClass("fa-arrows-up-down");
				// 	$(this).toggleClass("fa-arrows-left-right");
				// 	let id = this.id.split("dir_")[1];
				// 	polygons[id]['left_right'] = $(this).hasClass("fa-arrows-left-right");
				// 	// recalculate polygons and redraw shapes
				// 	// TODO on rotate pitch direction, maintain canvas max height?
				// 	adjustedPolygons = getAdjustedPolygonValues();
				// 	setCanvases();
				// 	$("#panel_select").trigger("change");
				// });

				// COLUMN 2 - compass heading
				col = $('<div />', {
					'class': "col-xs-6"
				}).appendTo($(row2));
				let compass = $('<img />', {
					'id': "compass_"+polygon_count,
					'class': "compass",
					'src': '/image/compass.svg'
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

			// set form values
			for (var i in polygons) {

				$('<input />', {
					'name': "roof_length"+i,
					'type': "hidden",
					'value': polygons[i]['length_width'][0]
				}).appendTo($("#config_vals"));

				$('<input />', {
					'name': "roof_width"+i,
					'type': "hidden",
					'value': polygons[i]['length_width'][1]
				}).appendTo($("#config_vals"));

				$('<input />', {
					'name': "roof_pitch"+i,
					'type': "hidden",
					'value': polygons[i]['pitch']
				}).appendTo($("#config_vals"));

				$('<input />', {
					'name': "roof_direction"+i,
					'type': "hidden",
					'value': polygons[i]['heading']
				}).appendTo($("#config_vals"));

				$('<input />', {
					'name': "strings_per_inverter"+i,
					'type': "hidden",
					'value': 1
				}).appendTo($("#config_vals"));

				$('<input />', {
					'name': "modules_per_string"+i,
					'type': "hidden",
					'value': $("#pcount_"+i).val()
				}).appendTo($("#config_vals"));

			}

			// make api call
			// console.log("http://mforcella.pythonanywhere.com/?"+$("#config_vals").serialize());
			$("#submit_url").val("http://mforcella.pythonanywhere.com/?"+$("#config_vals").serialize());
			$("#config_vals").submit();


		}

		// TODO if no shapes are found, enable manually setting dimensions
		if (polygon_count == 0) {
			console.log("no shapes!");
		}

		$("#panel_select").trigger("change");

	</script>

</body>
</html>