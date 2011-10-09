
<script src="{-filesfoldername-}/rapidblog.js" type="text/javascript" charset="utf-8"></script>
<?php
	/*
	 *
	 * Variables that change from within plugin 
	 * Variables delimited with { - and - } will get replaced at runtime
	 */
	
	// This needs to be set for preview mode to function
	/* For PHP 5.1 (& Snow Leopard)  */
	if (function_exists('date_default_timezone_set'))
	@date_default_timezone_set(date_default_timezone_get());
	
	if ("{-mode-}"=="preview")
	set_include_path(get_include_path() . PATH_SEPARATOR . "{-rapidblog.localParams.path-}"  ); // for preview only
	set_include_path(get_include_path() . PATH_SEPARATOR . "{-filesfoldername-}");
	
	$thisFile=basename(__FILE__); 
	
	// If in preview mode prime the pump
	//{-previewEntries-}; // Holds the so-called 'preview' entries
	
	// include the ajaxy part 
	require_once("{-filesfoldername-}/blogContents.php");
	
?>



