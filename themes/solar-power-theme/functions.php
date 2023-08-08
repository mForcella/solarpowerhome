<?php 
	add_action( 'wp_enqueue_scripts', 'solar_power_theme_enqueue_styles' );
	function solar_power_theme_enqueue_styles() {
		wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' ); 
	} 

	add_action( 'wp_head', 'get_array_polygons' );
	function get_array_polygons() {
		if (isset($_POST['polygons'])) {
			$shapes = $_POST['polygons'];
			foreach ($shapes as $shape) {
				echo "<input type='hidden' class='polygon' value='$shape'></input>";
			}
		}
	}

	add_action( 'wp_enqueue_scripts', 'process_shapes' );
	function process_shapes() {
		wp_enqueue_script( 'process_shapes', get_stylesheet_directory_uri() . '/js/process_shapes.js', array('jquery'), '1.0', false );
	}
	
?>