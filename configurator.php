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
	<!-- <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"> -->
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
		}
		#canvases {
			text-align: center;
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
				foreach ($_POST["polygons"] as $shape) {
					echo "<input class='polygon' type='hidden' value='$shape'></input>";
				}
			}

		?>
		
	</div>


	<div class="content">

		<!-- TODO set roof pitch -->

		<!-- select panel -->
		<div class="section">
			<label class="control-label">Please select your panel type</label>
			<select class="form-control" id="panel_select">
				<option value="REC Pure 400">REC Pure 400</option>
			</select>
			<!-- selected panel specifications -->
			<div id="panel_specs"></div>
		</div>

		<!-- select inverter -->
		<div class="section">
			<label class="control-label">Please select your inverter type</label>
			<select class="form-control">
				<option value="Enphase IQ8M-72-2-US">Enphase IQ8M-72-2-US</option>
			</select>
		</div>

		<!-- panel canvases -->
		<div id="canvases"></div>
		
		<!-- canvas for cloning -->
    	<canvas id="shape_canvas" class="shape-canvas"></canvas>

	</div>


	<!-- TODO place panels in polygon areas -->

	<!-- TODO option to 'fill shape to edges' -->

	<!-- TODO confirm and get quote -->


	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/all.min.js" async defer></script> -->
	<script src="/themes/solar-power-theme/js/process_shapes.js"></script>

	<script type="text/javascript">

		// panel name and dimensions in cm
		let panels = {
			"REC Pure 400" : [181.2,101.6]
		};

		$("#panel_select").on("change", function() {
			let dimensions = panels[$(this).val()];
			$("#panel_specs").html("");
			// show panel specs
			let label = $('<label />', {
				'class': "control-label"
			}).appendTo($("#panel_specs"));
			$('<p />', {
				'text': "Panel Specifications:"
			}).appendTo(label);
			$('<p />', {
				'text': "Length: "+dimensions[0]+"cm"
			}).appendTo(label);
			$('<p />', {
				'text': "Width: "+dimensions[1]+"cm"
			}).appendTo(label);

			// fill canvases with panel shapes
			var iterator = 1;
			$(".polygon").each(function() {
				let canvas = document.getElementById("canvas_"+(iterator++));
				let ctx = canvas.getContext("2d");
				ctx.strokeStyle = "red";
				ctx.lineWidth = 2;
				ctx.fillStyle = "darkgray";

				// get relative height and width of panels
				let area_width = parseFloat($(this).val().split(",")[0]);
				let canvas_width = canvas.offsetWidth - 4;
				let canvas_height = canvas.offsetHeight - 4;
				let pixels_per_cm = (canvas_width / (area_width * 100)).toFixed(3);
				let panel_width_pixels = (dimensions[1] * pixels_per_cm).toFixed(3);

				// set shapes onto canvas
				var startX = 0;
				var startY = 0;
				while (startY + dimensions[0] < canvas_height) {
			    	ctx.rect(startX, startY, dimensions[1] * pixels_per_cm, dimensions[0] * pixels_per_cm);
			    	ctx.fill();
			    	ctx.stroke();
			    	startX = startX + dimensions[1] * pixels_per_cm;
			    	if (startX + dimensions[1] > canvas_width) {
			    		startX = 0;
			    		startY = startY + dimensions[0] * pixels_per_cm;
			    	}
				}
			});

		});
		
		// get max width for shape scaling
		var maxWidth = 0;
		$(".polygon").each(function() {
			let width = parseFloat($(this).val().split(",")[0]);
			maxWidth = width > maxWidth ? width : maxWidth;
		});

		// create each panel array
		var iterator = 1;
		$(".polygon").each(function() {
			cloneCanvas(parseFloat($(this).val().split(",")[0]), parseFloat($(this).val().split(",")[1]));
			// TODO if no shapes are found, enable manually setting dimensions
		});

		// create a new shape drawing canvas
		function cloneCanvas(width, height) {
			// append array label
			$('<label />', {
				'class': "control-label",
				'text': "Array Area #" + (iterator) + " : " + width + "m x " + height + "m"
			}).appendTo($("#canvases"));

			// get canvas size
			let scale = width / maxWidth;
			let canvas_width = 800 * scale;
			let canvas_height = height / width * 800 * scale;
        	let new_canvas = $("#shape_canvas").clone();
        	$(new_canvas).attr("id", "canvas_"+(iterator++));
        	$(new_canvas).attr("width", canvas_width);
        	$(new_canvas).attr("height", canvas_height);
        	$("#canvases").append(new_canvas);
        	$(new_canvas).show();
		}

		$("#panel_select").trigger("change");

	</script>

</body>
</html>