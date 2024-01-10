<?php 

	// get the selection values from the configurator; panel, inverter, etc
	$data = array("module" => "some module");
	// build url for python api
	$url = "https://mforcella.pythonanywhere.com/";
	$url = sprintf("%s?%s", $url, http_build_query($data));
	// get response from api
	$response = file_get_contents($url);

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

	<div class="content">

		<?php 

			echo $response;

		?>

	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>

	<script type="text/javascript">

	</script>

</body>
</html>