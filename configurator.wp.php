
<!-- BEGIN HTML CONTENT -->

<div id="shapes">
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

		<input type="hidden" id="panel_count" name="panel_count">

	</form>

	<!-- panel canvases -->
	<div id="canvases"></div>
	
	<!-- canvas for cloning -->
	<canvas id="shape_canvas" class="panel-canvas"></canvas>

</div>

<!-- TODO add manual controls if dimensions are known - no shapes found -->

<!-- TODO button to confirm and get quote -->
<div class="center">
	<button id="conf_btn" type="button" class="btn btn-primary" onclick="getQuote()">Confirm and get quote</button>
</div>


<!-- BEGIN JAVASCRIPT CONTENT -->

<!-- <script src="/wp-content/themes/solar-power-theme/js/process_shapes.js"></script> -->
