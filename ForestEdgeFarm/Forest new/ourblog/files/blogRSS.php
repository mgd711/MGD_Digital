<?php
	
	error_reporting(E_ERROR);	
	
	// This file takes a feed from Blogger and reformats it to point to the RW version
	// It does it by replace links to blogger with links to the RW page
	// 
	
	header('Content-type: application/xml; charset=UTF-8');
	
	$myBlog="http://www.copydick.com/blog/index.php";
	$file="http://forestedgefarmextravirginoliveoil.blogspot.com/feeds/posts" . "/default?orderby=published" ;
	$filesFolder="files" ;
	$myTitle="Forest Edge Farm";
	
	
	
	/***************** Nothing changable below this line *******************/
	require_once('LHPEAR.php');
	require_once('URLrb.php'); // Was files/URLrb.php
	require_once('xmlrb.php');
	require_once('Requestrb.php');
	//require_once('rapidBlogHelpers.php');
	
	// Conver the string myBlog to point to the RSS feed
	$myBlogArray=explode("/",$myBlog);
	array_pop($myBlogArray);
	$myRss=implode("/",$myBlogArray);
	$myRss=$myRss . "/" .  $filesFolder ;
	$myRss=str_replace("http:/","http://",$myRss); // my Rss needs to have it's http reset after the pop/implode operation
	$myFile=basename(__FILE__); 
	$myRss=$myRss . "/" . $myFile; 
	//myRss not points to the proper location (whew) 
	
	
	// A few helper functions
	function google2TimeStamp($googleDate) {
		
		// 	$googleDate  has form='2006-12-09T15:27:00.000-08:00' or "2007-02-11T11:56:31.917Z";  
		$googleDate=str_replace("Z","+00:00",$googleDate); // if zulu time is returned, call it +00:00
		ereg('([0-9].*)-([0-9].*)-([0-9].*)T([0-9].*):([0-9].*):([0-9].*)([-+][0-9].*)',$googleDate,$regs);
		//print_r($regs);
		
		$thisTime=mktime($regs[4],$regs[5],$regs[6],$regs[2],$regs[3],$regs[1]);
		return $thisTime;
		
	}
	function getTitleFromXmlEntry($entry) {
		if (is_array($entry["title"])) // Blogger sometimes declares the type, sometimes no
			return $entry["title"]["_value"];
		else
			return $entry["title"];
		
	}
	
	
	function parseXMLURLandReturnArray($url,&$rData) {
		//print "About to fetch : $url\n\n";
		$req=new HTTP_Request();
		$req->_allowRedirects = true; /* Allow redirects as blogger tends to do that */
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
		$xml_parser = new xml(false, true, true);
		
		$req->setURL($url);
		$rData="";
		if($req->sendRequest()) {
			$rData=$req->getResponseBody();
		} else {
			print "********** Error in sendRequest *****";
		}
		
		//print($rData);
		$ret=$xml_parser->parse($rData);
		//print_r($ret);
		return $ret;
		
	}
	
	function timelineDate($googleDate){
		$thisDate=jDate($googleDate);
		print "thisDate=$thisDate";
		return jDate($googleDate);
		
	}
	
	// check for categories
	if (array_key_exists("categories",$_GET)) {
		
		$categories=$_GET["categories"];
		
		$categories=str_replace("\\'","'",$categories); // HTML escape this		
		$categories=rawurlencode($categories); // Make sure we treat UTF-8 properly, urlencode them
		$categories="/-/" . $categories ; /* max results set to a large number to catch everything */
		
	} 
	
	// If the user specified a categories string....
	if (strlen($categories)>0) {
		$file=str_replace("/default?", "/default$categories?", $file);
	}
	// Get the values
	
	
	// Get the values
	$vals=parseXMLURLandReturnArray($file,$rData);
	
	// Else just assume it's normal XML
	$buffer=$rData; // Buffer is the working copy;
	// Get the blog ID//
	$number=eregi("tag:blogger.com,1999:blog-([0-9]*)",$vals[id],$blogIDs);
	
	$blogID=$blogIDs[1]; // Blogid is important later.
	
	
	// First adjust the individual entries
	// one at a time run through and reformat the URL's to point to the right spot
	$entries=$vals['feed']['entry'];
	if ($entries[0]==NULL)
	$entries=Array(0=>$entries);
	
	//print_r($entries);
	
	for ($i=0;$i<count($entries);$i++) {
		$myEntry=$entries[$i];
		$number=eregi("post-([0-9]*)",$myEntry[id],$entryIDs);
		$entryID=$entryIDs[1];
		
		$myLinks=$myEntry['link'];
		for ($jj=0;$jj<count($myLinks);$jj++) {
			$buffer=str_replace($myLinks[$jj]['href'],"$myBlog?id=$entryID",$buffer);
		}
		
		$buffer=str_replace($myRss . "/" . $entryID,$myRss,$buffer); // clean up the entry id's
	}
	
	$oldName=getTitleFromXmlEntry($vals['feed']);
	if (strlen($myTitle)>0) /* Use a custom title if supplied */ {
		/* This will replace all occurances.  On the offchance that they have the title twice it will replace twice
		 Which is not ideal but probably low risk.
		 */
		$buffer=str_replace("<title type='text'>$oldName</title>", "<title type='text'>$myTitle</title>",$buffer);
	}
	
	
	
	
	
	// Finally update the top level links
	// Reset all 'top level'  links to point to the right spot (e.g. back to the rapidblog feed)
	$topLinks=$vals['feed']['link'];
	// Order is important.  because we are doing simple str_replace we need to fix them in the right order
	for ($kk=count($topLinks)-1;$kk>=0;$kk--) {
		if ($topLinks[$kk]['type']!="application/atom+xml")
			$buffer=str_replace($topLinks[$kk]['href'],$myBlog,$buffer); // everything else (html, txt) points to main blog
		else
			$buffer=str_replace($topLinks[$kk]['href'],$myRss,$buffer); // Have atom point back to this feed
	}
	
	// Finally remoce the google schema to put it out of it's misery once and for all since some
	// services (?) sniff the schema and then do bad, bad things based on that.
	
	$buffer=str_replace("schemas.google.com","schemas.loghound.com",$buffer);
	
	
	
	print $buffer; // Actually output it for the feed!
	?>