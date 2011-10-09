<?php
	
	
	
	/*************************************************************************/
	
	/* Assume that the default view */
	$bloggerGetType="$bloggerGetMainPage";
	$bloggerGet="/full";
	
	$orderBy="&orderby=published";
	
	
	$lastPublishedQuery=""; // by default don't limit the published date
	
	if ($howFarBackToViewEnabled) { /* If the user specified it we can optionally limit the search to a given time frame */
		$lastPublished=googleDaysAgo($howFarBackToView);
		$lastPublishedQuery="&published-min=$lastPublished";
	} 
	//      print "<pre>". __FILE__."</pre>";
	
	$showComments=$expandCommentsInMainPage? true: false;
	$showHideComments=$expandCommentsInMainPage==2;
	
	$file="";
	$isPermalink=FALSE;
	
	if (array_key_exists("categories",$_GET)) {
		$bloggerGetType="$bloggerGetCategoryPage";
		$bloggerGet="/full"; 
		$categories=$_GET["categories"];
		
		$categories=str_replace("\\'","'",$categories); // HTML escape this		
		$categories=rawurlencode($categories); // Make sure we treat UTF-8 properly, urlencode them
		$categories="/-/" . $categories ; /* max results set to a large number to catch everything */
		$lastPublishedQuery=""; // Don't date restrict category searches
		
		$showComments=$expandCommentsInCategoryPage? true: false;
		$showHideComments=$expandCommentsInCategoryPage==2;
		
		$maxNumberPostsToShow=999; // no limit if a date is set
		$file = buildUpUrl("$bloggerURL",$bloggerGet,$categories,$minPublished,$lastPublishedQuery,$orderBy,"&max-results=1000");
	} 
	else
	if(array_key_exists("id",$_GET)) {
		$isPermalink=TRUE;
		$id=$_GET["id"];
		$bloggerGetType="full";
		$bloggerGet="/full/$id"; // if an ID is asked for give everything...
		// If a specific id is asked for then ignore everything else
		$categories="";
		$lastPublishedQuery="";// no need to put a date restriction on if an ID is passed in
		/* If we received a specific ID then append the comments.. Long term make this configurable */
		$showComments=$expandCommentsInPermalinkPage? true: false;
		$showHideComments=$expandCommentsInPermalinkPage==2;
		
		$postID=$id;
		$file = buildUpUrl("$bloggerURL",$bloggerGet);// Works around blogger defect
	} 
	
	if(array_key_exists("published-min",$_GET)) {
		$bloggerGetType="$bloggerGetArchivePage";
		$bloggerGet="/full";
		$minPublished="&published-min=".urlencode($_GET["published-min"]);
		if ($_GET["published-max"]) {
			$minPublished=  $minPublished. "&published-max=".urlencode($_GET["published-max"]); // Make results large.  Should pick up all of the posts
		}
		$lastPublishedQuery=""; // IF we have a date query then ignore the default minimums for dates...
		$maxNumberPostsToShow=999; // no limit if a date is set
		$file = buildUpUrl("$bloggerURL",$bloggerGet,$categories,$minPublished,$lastPublishedQuery,$orderBy,"&max-results=1000");
	} else if(array_key_exists("published-max",$_GET)) { // A published-max without a published-min means they are looking at older posts (using 'older post' links)
		$bloggerGetType="$bloggerGetArchivePage";
		$bloggerGet="/full";
		if ($_GET["published-max"]) {
			$minPublished=  $minPublished. "&published-max=".urlencode($_GET["published-max"]); // Make results large.  Should pick up all of the posts
		}
		$lastPublishedQuery=""; // IF we have a date query then ignore the default minimums for dates...
		// $maxNumberPostsToShow=999; // no limit if a date is set
		$file = buildUpUrl("$bloggerURL",$bloggerGet,$categories,$minPublished,$lastPublishedQuery,$orderBy,"&max-results=1000");
	} 
	else
	if ($file=="") 
	$file = buildUpUrl("$bloggerURL",$bloggerGet,$categories,$minPublished,$lastPublishedQuery,$orderBy,"&max-results=1000");
	
	
	print "<!--Query URL= $file -->";
	
	
	$vals=parseXMLURLandReturnArray($file,0); // Don't skip whitespace otherwise important HTML context get's lost
	
	
	
	$j=0;
	
	$myEntries=Array();
	$postDates=Array();
	
	if ($vals['entry']!="") {
		$entries=$vals['entry'];
	} else {
		$entries=$vals['feed']['entry'];
	}
	
	//print_r($entries);
	/* If entries count is 0 then a single post is returned (most likely) so need to create an array */
	if ($entries[0]==NULL && $entries[id]!=NULL)
	$entries=Array(0=>$entries);
	
	if ($entries[0]==NULL && $entries[id]==NULL)
	return; // nothing useful returned so just exit
	// print "Count of entries is "  . count($entries) . "\n";
	
	
	?>