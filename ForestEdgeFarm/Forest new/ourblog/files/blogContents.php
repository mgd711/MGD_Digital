<?php
	/*
	 *
	 * Variables that change from within plugin 
	 * Variables delimited with { - and - } will get replaced at runtime
	 */
	
	// This needs to be set for preview mode to function
	
	if ("{-mode-}" != "preview")
	         error_reporting(E_ERROR); // supress notices & warning
	
	
	require_once("localVars.php");
	if (!class_exists("PEAR"))
	require_once("LHPEAR.php");
	require_once("Socketrb.php");
	require_once("URLrb.php");
	require_once("xmlrb.php");
	require_once("Requestrb.php");
	require_once("rapidBlogHelpers.php");
	
	
	$time0=microtime_float(); // get a starting time... Thanks to Marco for getting the order right here
	
	
	
	
	// Now include the moo-tools used as part of comment system
	// <script src="{-filesfoldername-}/rapidblog.mtools.83.js" type="text/javascript" charset="utf-8"></script>
	?>



<?php
	
	// Figure out what I am
	
	
	
	$time1=microtime_float();
	$dtime=$time1-$time0;
	printn ("<!-- Time before capture is $dtime -->");
	
	
	if ($exportMode!="preview") {
		
		
		require_once("remoteGrab.php"); // Get the entries from remote server
		
		
	} else {
		//$myEntries={-previewEntries-}; // Holds the so-called 'preview' entries
	}
	
	$time2=microtime_float();
	$dtime=$time2-$time1;
	printn ("<!-- Time after capture is $dtime -->");
	
	if (count($entries)==0 && ($vals['feed']['openSearch:totalResults']!=0)) { // This should never happen, some sort of error
		print "<h1>Blogger Down?</h1>";
		print "<pre>";
		print_r($vals); 
		print "</pre>";
		// return; // if no entries then return
	} else for ($i=0;$i<count($entries);$i++) {
		$newEntry=Array();
		$newEntry['ID']=$entries[$i]['id'];
		$newEntry['PUBLISHED']=jdate($entries[$i]['published']);
		$newEntry['GPUBLISHED']=$entries[$i]['published'];
		$postDates[]=$entries[$i]['published'];
		$newEntry['UPDATED']=$entries[$i]['updated'];
		
		
		$newEntry['TITLE']=utf2html(getTitleFromXmlEntry($entries[$i]));
		
		
		
		$newEntry['CONTENT']=utf2html($entries[$i]['content']['_value']);
		
		
		$newEntry['AUTHORNAME']=utf2html($entries[$i]['author']['name']);
		$newEntry['AUTHORURI']=$entries[$i]['author']['uri'];
		/* Now pick off blog & post ID's */
		
		foreach ($entries[$i]['link'] as $link){
			if ($link['rel']=='edit') $paths=preg_split("/[~`!@#$%^&*()\/\\?]/",$link['href']);
			
			if (($link['rel']=='replies') && ($link['type']=='text/html')) {
				$newEntry['COMMENTPOSTURL']=str_replace("&","&amp;",$link['href']); /* Make this validate */
				$newEntry['NUMBERCOMMENTS']=numberCommentsFromTitle($link['title']);
			}
			if(($link['rel']=='replies') && ($link['type']=="application/atom+xml")) {
				$newEntry['COMMENTFEED']=$link['href'];
			}
			if(($link['rel']=='alternate') && ($link['type']=="text/html")) {
				$newEntry['BLOGGERPOST']=$link['href'];
			}
			
			
		}
		
		
		
		$blogID=$paths[4]; // 4th entry is blog ID
		$postID=$paths[7]; // 7th entry is the post id
		$newEntry['SELF']="$thisFile?id=".$postID;
		$newEntry['ENTRYID']=$postID;
		$newEntry['POSTID']=$postID;
		$newEntry['BLOGID']=$blogID;
		
		$categories=@$entries[$i]['category'];
		if ($categories['term']!="") {  // handle the singel categor case
			$newEntry['CATEGORIES'][]=htmlEscape($categories['term']);
		} else {
			for ($j=0;$j<count($categories);$j++) {
				$newEntry['CATEGORIES'][$j]=htmlEscape($categories[$j]['term']);
			}
		}
		
		$myEntries[]=$newEntry;
	}
	
	$time21=microtime_float();
	$dtime=$time21-$time2;
	printn ("<!-- Time to process UTF8 strings is $dtime -->");
	
	
	//print_r($entries);
	// Sort the entries by post date... Newest first
	if (count($postDates)>0) /* Check to make sure we have at least one post */
	array_multisort($postDates,SORT_DESC,$myEntries);
	
	
	$commentArray=Array(); // this will hold the comments 
	// Now push out the values
	$commentArrayIE=Array(); // grrr IE needs a special case....
	
	for($i=0;($i<count($myEntries)) && ($i<$maxNumberPostsToShow);$i++) {
		
		/* End of handy constants for end user */
		$entryNumber=$i+1;
		$myEntry=$myEntries[$i];
		/* Define some handy constants for end user inclusion */
		$blogPostPermaLink="$urlToThisPage?id=".$myEntry['POSTID'];
		$blogPostTitle=$myEntry['TITLE'];
		
		if (!$phpBlogOrder) { // no alternate blog order defined -- use the default
			
			
			printn ("<div id='unique-entry-id-$entryNumber' class='blog-entry'>");
			printn (renderTitle($myEntry));
			print "<div class='blog-entry-date'>";
			printn (renderDate($myEntry));
			printn (renderCategories($myEntry));
			printn(renderPermalink($myEntry));
			printn ("</div>");
			printn ("<div class='blog-entry-body'>");
			
			printn(renderBody($myEntry));
			printn(renderAuthor($myEntry));
			
			
			printn(renderComments($myEntry));
			
			printn  ("</div></div>"); // Final closing div
			
			print renderInterBlog($myEntry,$i);
		} else { // The user has provided a alternate blog order 
			printn ("<div id='unique-entry-id-$entryNumber' class='blog-entry'>");
			ob_start();
			eval(  $phpBlogOrder); //"
			$ret=ob_get_contents();
			ob_end_clean();
			print $ret;
			printn  ("</div>"); // Final closing div
		}
		
	}		
	
	/**************** Older Posts ************************/
	
	print renderOlderPosts($myEntry);
	
	/****************** Comments ************************/	
	

	postBloggerJavascriptComments($commentArray); // if necessary write out the javascript for blogger style comments
	
	$time3=microtime_float();
	$dtime=$time3-$time2;
	printn("<!-- Time after writing entries is $dtime -->");
	$dtime=$time3-$time0;
	printn("<!-- Total execution time is $dtime -->") ;
	
	
	?>



