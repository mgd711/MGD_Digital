
<?php
	
	/******************* Rendering Helpers ******************/
	
	
	function renderTitle($myEntry) {
		global $permalinkType;
		global $urlToThisPage;
		
		$blogPostPermaLink=$myEntry['SELF'];
		$titleEntry=$myEntry['TITLE'];
		if ($permalinkType) {
			$titleEntry="<a href='$blogPostPermaLink' class='blog-permalink'>$titleEntry</a>";
		}
		$titleEntry="<h1 class='blog-entry-title'>$titleEntry</h1>";
		
		
		return $titleEntry;
	}
	
	function renderDate($myEntry) {
		return $myEntry['PUBLISHED'];
		
	}
	
	function renderPermalink($myEntry) {
		global $showPermalinks;
		global $permalinkType;
		global $permalinkTitle;
		global $urlToThisPage;
		
		if ($showPermalinks && !$permalinkType) {
			return ("<span class='blog-entry-permalink'> | <a href='$myEntry[SELF]'>".utf2html($permalinkTitle)."</a></span>");
		}
		return "";
		
	}
	
	
	function renderComments($myEntry) {
		global $commentsEnabled;
		global $showNumberOfComments;
		global $showComments;
		global $bloggerCommentsInPopup;
		global $commentArray;
		global $commentArrayIE; // grrrr IE...
		global $blogCommentService;
		global $commentIdentifier;
		global $commentString;
		global $commentHideString;
		global $commentShowString;
		global $showHideComments;
		global $haloscanUsername;
		global $urlToThisPage;
		global $isPermalink;
		global $disqusShortName;
			
		global $commentShowString;

		
		$linkToThisPage=$urlToThisPage."?id=".$myEntry['POSTID'];
		
		$ret="";
		if ($commentsEnabled) { // Only show comments if they are enabled
			
			$ret.= "<div class='blog-entry-comments' id='comment_$myEntry[POSTID]'>";
			if ($blogCommentService==0) { // Blogger comment system
				
				if ($bloggerCommentsInPopup) /* If the user has selected popup's in blogger then enable via onclick */  {
					$bloggerOnClick="onclick='javascript:window.open(this.href, \"bloggerPopup\", \"toolbar=0,location=0,statusbar=1,menubar=0,scrollbars=yes,width=400,height=450\"); return false;'";
					$popupTrue="true";
				} else {
					$popupTrue="false";
				}
				
				$ret .= "<a class='blog-comment-link' href='$myEntry[COMMENTPOSTURL]&amp;isPopup=$popupTrue' $bloggerOnClick>". utf2html($commentIdentifier). "$commentString</a>";
				if ($showNumberOfComments && ($myEntry["NUMBERCOMMENTS"]>0))
					$ret.= " ($myEntry[NUMBERCOMMENTS])";
				if ($showHideComments && ($myEntry["NUMBERCOMMENTS"]>0)) {
					$ret.=" <a class='blog-show-link' id='comment-$myEntry[POSTID]-body-link' href='#' onclick=\"javascript:toggleHide('comment-$myEntry[POSTID]-body');\">$commentShowString</a>\n";	
				}		
				if ($showNumberOfComments && ($myEntry["NUMBERCOMMENTS"]>0))
					$ret.= "<br/>";	
				if ($showComments) {
					$commentFeed=$myEntry["COMMENTFEED"];
					$commentArray[]="<script type='text/javascript' src='$commentFeed?alt=json-in-script&amp;callback=listComments&amp;max-results=1000'> </script>\n";
					$commentArrayIE[]="$commentFeed?alt=json-in-script&amp;callback=listComments&amp;max-results=1000";
				}
				
				
				
				if ($showHideComments && ($myEntry["NUMBERCOMMENTS"]>0) ) {
					// we used to send out a toggle hide script but that breaks IE so now it's handled in javascript
				}
			} else if ($blogCommentService==1) {  // Haloscan comment system
				if ($showComments) {
					$ret.="<script type='text/javascript' src=\"http://www.haloscan.com/comments/$haloscanUsername/$myEntry[POSTID]/?m=1\" type=\"text/javascript\"> </script>";
				} else {
					
					$ret.="<a  class='blog-comment-link' href='javascript:HaloScan(\"$myEntry[POSTID]\");' target='_self'>";
					$ret.="<script type='text/javascript'>postCount('$myEntry[POSTID]'); </script></a> | ";
					$ret.="<a href='javascript:HaloScanTB(\"$myEntry[POSTID]\");' target='_self'>";
					$ret.="<script type='text/javascript'>postCountTB('$myEntry[POSTID]'); </script></a>";
				}
			} else if ($blogCommentService==2) {
				$ret .="<div class='js-kit-comments' path='/$myEntry[POSTID]' ";
				$ret  .="permalink='".$linkToThisPage. "'>js-comments</div>";
				
			} else if ($blogCommentService==3) {
			// Disqus


				// permalink
				if ($isPermalink) {
					$ret .="<div id='disqus_thread'></div>";
					$ret .="<script type='text/javascript'>";
					$ret .= "var disqus_url = '$linkToThisPage';";
					$ret .= "var disqus_title = '". str_replace("'","\'",$myEntry[TITLE]) . "';";
					$ret .= " (function() {";
					$ret .=  " var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;";
					$ret .= "  dsq.src = 'http://$disqusShortName.disqus.com/embed.js';";
					$ret .= "  (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);";
					$ret .= " })();";
					$ret .="</script>";
					
				} else {
					// non permalink
					$ret .= "<a href='$linkToThisPage#disqus_thread'>View Comments</a>";
				}
			}
			$ret.="</div>";
		}
		return $ret;
		
	}
	
	function renderCategories($myEntry){
		global $thisFile;
		
		$ret="";
		
		if ($myEntry['CATEGORIES'] !="") {
			$ret.= "<span class='blog-entry-category'> | ";
			$myCategories=$myEntry['CATEGORIES'];
			for($j=0;$j<count($myCategories);$j++) {
				$thisCategory=utf2html($myCategories[$j]);
				$ret.= "<a href='$thisFile?categories=$myCategories[$j]'>".utf2html($thisCategory)."</a>";
				if ($j!=count($myCategories)-1) {
					$ret.= ", ";
				}
			}
			$ret .= "</span>";
		}
		
		return $ret;
	}
	
	function renderBody($myEntry) {
		global $readMoreText;
		global $bloggerGetType;
		global $bloggerGetType;
		
		
		if ($bloggerGetType == "full")
			$ret=$myEntry['CONTENT'];
		else
			$ret=extractSummary($myEntry['CONTENT']);
		
		
		if ($bloggerGetType=="summary") {
			$ret= "<div class='blog-entry-summary'>$ret</div>";
			$ret.= "<div class='blog-entry-readmore'><a href='$myEntry[SELF]'>".utf2html($readMoreText)."</a></div>";
		}
		return $ret;
	}
	function renderAuthor($myEntry) {
		
		
		global $showAuthor;
		global $gravatarArray;
		global $gravatarSize;
		
		$ret="";
		$authorName=$myEntry['AUTHORNAME'];
		$gravURL=NULL;
		if ($showAuthor) {
			
			
			
			// Now print the author of the post
			
			$ret.= "<div class='blog-entry-author'>$myEntry[AUTHORNAME]</div>";
			
			if ($gravatarArray[$authorName]) {
				$url=$gravatarArray[$authorName];
				$ret.= "<span class='blog-entry-gravatar'><img src='$url' /></span>";
			}
			
		}
		return $ret;
	}
	
	function renderInterBlog($myEntry,$i) {
		$ret="";
		global $interHTMLText;
		global $interTextRepeat;
		global $interTextAfterFirst;
		global $interEvaluatePHPCode;
		// Print a interHTMLText string (if it's appropriate).  If it's set to zero than don't do anything
		if( ($interTextAfterFirst==$i) || ( ($interTextRepeat!=0) && ($i>=$interTextAfterFirst) &&  ((($i-$interTextAfterFirst)%$interTextRepeat)==0)))
		{
			if ($interEvaluatePHPCode) /* Pass it through eval if the user requested it* */ 
			{ 
				ob_start();
			eval( "?>" . $interHTMLText . "<?" ); //"
$ret=ob_get_contents();
ob_end_clean();
}
else
$ret=$interHTMLText;
} 

return $ret;
}


function renderOlderPosts($myEntry) {
	global $thisFile;
	global $olderPostsString;
	global $olderPostsEnabled;
	if ($olderPostsEnabled)
		print "<a id='blog-next-page' href='$thisFile?published-max=" . urlencode($myEntry[GPUBLISHED])."'>$olderPostsString</a>"; // need to url encode date for some time zones
	
}

function renderCategoriesInSidebar() {
	global $showCategories;
	global $categoriesArray;
	global $thisFile;	
	global $filesFolder;
	global $ajaxy;
	
	$ret="";
	$urlToGrab=$thisFile;
	ajaxySetup();
	if ($ajaxy) {
		printn("<script type='text/javascript'>");
		printn("function contentInsert(object1) { document.getElementById('content').innerHTML=object1;}");
		printn("</script>");
		$urlToGrab="$filesFolder/blogContents.php";		
	}		     
	
	if ($showCategories) { 
		
		printn ("<div id='blog-categories'>");
		
		for ($i=0;$i<count($categoriesArray);$i++) {
			$thisCatEscaped=htmlEscape($categoriesArray[$i]);
			$thisCategory=utf2html($thisCatEscaped); 
			if ($ajaxy) {
				$aElement="href='#' onclick=\"ajax('$urlToGrab?categories=$thisCatEscaped','',contentInsert);\"";
			} else {
				$aElement="href='$urlToGrab?categories=$thisCatEscaped'";
			}
			
			printn ("<a $aElement class='blog-category-link-enabled'>$thisCategory</a><br />");
		}
		
		print "</div>";
	}
	
	
	
}

function ajaxySetup() {
	static $alreadyDisplayed=0;
	global $ajaxy;
	
	if ($ajaxy && !$alreadyDisplayed) {
		?>
		
		</script> <script type="text/javascript">
		// Provide the XMLHttpRequest class for IE 5.x-6.x:
		if( typeof XMLHttpRequest == "undefined" ) XMLHttpRequest = function() {
			try { return new ActiveXObject("Msxml2.XMLHTTP.6.0") } catch(e) {}
			try { return new ActiveXObject("Msxml2.XMLHTTP.3.0") } catch(e) {}
			try { return new ActiveXObject("Msxml2.XMLHTTP") } catch(e) {}
			try { return new ActiveXObject("Microsoft.XMLHTTP") } catch(e) {}
			throw new Error( "This browser does not support XMLHttpRequest." )
		};
		
		function ajax(url, vars, callbackFunction) {
			var request =  new XMLHttpRequest();
			request.open("POST", url, true);
			request.setRequestHeader("Content-Type",
									 "application/x-www-form-urlencoded");
			
			request.onreadystatechange = function() {
				var done = 4, ok = 200;
				if (request.readyState == done && request.status == ok) {
					if (request.responseText) {
						callbackFunction(request.responseText);
					}
				}
			};
			request.send(vars);
		}
		
		</script>
		
		<?php
		
	}
	
}
function renderArchives() {
	global $archivesDropdown;
	global $archivesDropdownTitle;
	global $thisFile;
	global $archiveFormat;
	global $dateLocalizeConvert;
	global $showArchives;
	global $filesFolder;
	global $ajaxy;
	
	
	
	ajaxySetup();
	if ($showArchives) {
		$urlToGrab=$thisFile;
		if ($ajaxy) {
			$urlToGrab="$filesFolder/blogContents.php";
		}
		
		print "<div id='blog-archives'>";
		if ($ajaxy) {
			printn("<script type='text/javascript'>");
			printn("function archiveChanged(this2)\n { if(this2.selectedIndex!=0) \najax(this2.value,'',contentInsert);\n }");
			printn("function contentInsert(object1) { document.getElementById('content').innerHTML=object1;}");
			printn("</script>");
		}
		
		if ($archivesDropdown) {
			if ($ajaxy) {		
				$onChangeJavascript="archiveChanged(this)";
			} else {
				$onChangeJavascript=" self.location=this.options[this.selectedIndex].value";
			}
			printn('<p></p><form><select onchange=\''.$onChangeJavascript.'\'> <option value=\'\' selected="selected">'.$archivesDropdownTitle.'</option>');
		}
		$inputMonth=archiveMonths(); // get the archiveMonths
		
		for ($ij=0;$ij<count($inputMonth);$ij++) {
			$thisMonth=google2TimeStamp($inputMonth[$ij]);
			$minPublish=date('Y-m-01',$thisMonth).'T'.date('00:00:00-00:00',$thisMonth);
			$maxPublish=date('Y-m-01\T00:00:00-00:00',incMonth(1,$thisMonth));
			
			// Account for user localization of month string
			$thisMonthString=str_replace(array_keys($dateLocalizeConvert),array_values($dateLocalizeConvert),strftime($archiveFormat,$thisMonth)); 
			if ($archivesDropdown /* && $ajaxy*/) {
				printn("<option value='$urlToGrab?published-min=$minPublish&amp;published-max=$maxPublish'> $thisMonthString </option>");
				
			} else  {
				
				if ($ajaxy) {
					$aElement="onclick='ajax(\"$urlToGrab?published-min=$minPublish&amp;published-max=$maxPublish\",\"\",contentInsert)' href='#' ";
				} else {
					$aElement=" href='$urlToGrab?published-min=$minPublish&amp;published-max=$maxPublish'";
					
				}
				
				printn ("<a class='blog-archive-link-enabled' $aElement >" . $thisMonthString ."</a><br />");
			}
		}
		
		if ($archivesDropdown){
			printn ("</select></form><br />");
		}
		print "</div>";
	}
	
}


/* Extract Summary text  -- Extracts the summary (if no summary is specified return the first 400 characters) */
function extractSummary($inputText) {
	$numbMatchesOut=preg_match_all("%<\s*/span+[^>]*(>)%",$inputText,$matchesOut,PREG_OFFSET_CAPTURE|PREG_PATTERN_ORDER); // matchesOut[1] contains an array who's '1' value + 1 is the ending offset
	$numbMatchesIn=preg_match_all("%<\s*span(\s|)+([^>]*)>%",$inputText,$matchesIn,PREG_OFFSET_CAPTURE); // matchesIn[0] contains an array who's '1' value is the starting offset
	
	$startIndex=$matchesIn[0];
	
	$stopIndex=$matchesOut[1];
	$startIndexType=$matchesIn[2];
	
	$cnt=count($startIndex);
	$spanStart=-1;
	$spanStop=-1;	
	$ret="";
	if ($cnt!=count($stopIndex)) {
		
		// print "Uh-Oh -- start * stop don't match";
		
	} else {	
		
		$spanDepth=0;
		for ($i=0;$i<$cnt;$i++) {
			if ($spanStart==-1 && stristr($startIndexType[$i][0],"rapidblog-summary") ) {
				$spanStart=$startIndex[$i][1];
				$spanStop=$stopIndex[$i][1]+1;  // grab the end location  
				
			}
			if (stristr($startIndexType[$i][0],"rapidblog-summary") ) {
				
				while (isset($startIndex[$i+1]) && $stopIndex[$i][1] > $startIndex[$i+1][1]) {
					$i++;
					$spanStop=$stopIndex[$i][1]+1;  // grab the end location  
				}
				
			}
        	
		}
		$ret=substr($inputText,$spanStart,$spanStop-$spanStart);
	}
	
	if ($spanStart==-1 || $spanStop==-1) {
		
		$ret=strip_tags($inputText);
		if (strlen($ret)>400) {
			$srchLocation=400;
			while ($ret[$srchLocation]!=" " && $srchLocation < strlen($ret) && $srchLocation < 500)
				$srchLocation++;
			
			$ret=substr($ret,0,$srchLocation);
		}
	}
	
	return $ret;
}

/************************************************************************
 * Misc Helper Functions
 *************************************************************************/
/* Clean this up to reflect the proper date... */
function jDate($oddDate) {
	global $dateFormat,$timeFormat,$dateTimeSeperator,$dateLocalizeConvert;
	
	$thisTime=google2TimeStamp($oddDate);
	if ($thisTime==0) 
		return $oddDate; // if we can't parse it then just return it unparsed
	else
		return str_replace(array_keys($dateLocalizeConvert),array_values($dateLocalizeConvert),strftime("$dateFormat $timeFormat",$thisTime));
} 


/* Blog Archives */

function google2TimeStamp($googleDate) {
	
	// 	$googleDate  has form='2006-12-09T15:27:00.000-08:00' or "2007-02-11T11:56:31.917Z";  
	$googleDate=str_replace("Z","+00:00",$googleDate); // if zulu time is returned, call it +00:00
	ereg('([0-9].*)-([0-9].*)-([0-9].*)T([0-9].*):([0-9].*):([0-9].*)([-+][0-9].*)',$googleDate,$regs);
	//print_r($regs);
	
	$thisTime=mktime($regs[4],$regs[5],$regs[6],$regs[2],$regs[3],$regs[1]);
	return $thisTime;
	
}


// This handy function returns a date formated in google speak 'n' days ago
function googleDaysAgo($daysAgo) {
	return  date('Y-m-d\TH:i:s\Z',time()-24*60*60*$daysAgo);
}

// returns the next month passed in a timestamp
function incmonth ($increment,$now=-1) {
	$now = ($now == -1)? $now = time() : $now;
	
	if (date('m',$now) <> date('m',$now+86400)) $now -= 86400;
	
	return strtotime ($increment . ' month',$now);
}


/**
 * Simple function to replicate PHP 5 behaviour
 */
function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

function parseXMLURLandReturnArray($url,$skipWhitespace=1) {
	
	
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
	$ret=$xml_parser->parse($rData,$skipWhitespace);
	
	//print_r($ret);
	return $ret;
	
}



function numberComments($postID) {      
	global $bloggerURL;
	
	//print("Here is the bloggerURL" . $bloggerURL);
	$commentFeed=str_replace("posts","$postID/comments/full",$bloggerURL);
	
	$vals=parseXMLURLandReturnArray($commentFeed);
	//print_r($vals);
	$totalCount=$vals[feed]["openSearch:totalResults"]; 
	return $totalCount; 
	
}


function getTitleFromXmlEntry($entry) {
	if (is_array($entry["title"])) // Blogger sometimes declares the type, sometimes no
		return $entry["title"]["_value"];
	else
		return $entry["title"];
	
}

function getSummaryFromXmlEntry($entry) {
	if (is_array($entry["summary"])) // Blogger sometimes declares the type, sometimes no
		return $entry["summary"]["_value"];
	else
		return $entry["summary"];
	
}

function getComments($postID) {
	
	global $bloggerURL;
	
	
	$commentFeed=str_replace("posts","$postID/comments/full",$bloggerURL);
	
	$vals=parseXMLURLandReturnArray($commentFeed);
	// print "<pre>";
	//      print_r($vals);
	//              print "</pre>";
	
	if ($vals[feed][entry][0]!=null)
		$comments=$vals[feed][entry];
	else if ($vals[feed][entry] != null)
		$comments=Array(0=>$vals[feed][entry]);
	else $comments=null;
	
	for ($i=0;$i<count($comments);$i++) {
		$title="";$content="";
		$published="";$name="";$uri="";$lret="";        
		$myComment=$comments[$i];
		
		$title=getTitleFromXmlEntry($myComment);
		$content=$myComment[content][_value];
		$name=$myComment[author][name];
		$uri=$myComment[author][uri];
		$published=jDate($myComment[published]);
		
		
		if ($uri!="") $lret.= "<a href='$uri'>";                                 
		$lret.= "<br /><div>$name said...</div>\n";
		if ($uri!="") $lret.= "</a>";
		/*                                                                      
		 $lret=$lret. "<div>$title</div>\n";                                    
		 */                                                                     
		$lret=$lret. "<div class='blog-entry-comments'>$content</div>\n";       
		$lret=$lret. "<div>$published</div>";                                   
		$retArray[]=$lret;                                                      
		
	}
	
	
	//      print_r($retArray);
	
	for ($i=count($retArray);$i>=0;$i--) {
		$ret=$ret.$retArray[$i];
	}
	
	return $ret;
	
}

// Return's an array of archive months in GoogleDate format

function archiveMonths() {
	global $filesFolder;
	global $blogCreationDate;
	global $exportMode;
	global $tempFilesDirectory;
	global $archiveOrdering;
	
	$now=time();
	
	
	if ($exportMode=="preview") {
		$dirPrefix=str_replace("//","/",$tempFilesDirectory . "/"); // make sure it has a trailling slash (but only one!)
		
	} else {
		$dirPrefix="";
	}
	
	$inFileName="$dirPrefix$filesFolder/archiveMonths.xml";
	$archiveFile=fopen($inFileName,"r");
	$archiveContents=fread($archiveFile,filesize($inFileName));
	fclose($archiveFile);
	$monthXml=new xml(false,true,true);
	$res=$monthXml->parse($archiveContents);
	
	$pa=$res[plist];
	$pb=$pa["array"];
	
	$parsed=$pb["dict"];
	
	if ($parsed[0]==null) {
		$parsed=null;
		$parsed[0]=$pb["dict"]; /* Account for the single entry case */
	}
	
	$inputMonth=Array();
	$inputMonthCount=Array();
	for ($ij=0;$ij<count($parsed);$ij++) {
		$inputMonth[]=$parsed[$ij]["string"];
		$inputMonthCount[]=$parsed[$ij]["integer"];
		
	}
	if (count($inputMonth)>0)
		$currentMonth=incMonth(1,strtotime(date('F 1, Y',google2TimeStamp($inputMonth[0])))); // if month stored in archive folder
	else
		$currentMonth=strtotime(date('F 1, Y',google2TimeStamp($blogCreationDate))); // if no month stored in archive folder
	
	while ($currentMonth < $now) {
		
		$inputMonth[]=date('Y-m-01\T00:00:00.000+00:00',$currentMonth);
		$currentMonth=incMonth(1,$currentMonth);
	}
	
	
	sort($inputMonth);
	
	if ($archiveOrdering)
		$inputMonth=array_reverse($inputMonth);
	
	return $inputMonth;
}


// This function writes out the javascript code to trigger blogger style comments
// it has a unique behavior for IE since IE doesn't, in general, act sane
// When modifying the DOM (for IE we modify it in the onload function)
// Sadly, Safari doesn't act sane when you modify the onLoad so 
// we have to employ two techniques for injection.   There may be another
// way around this but it seems to work well enough

function postBloggerJavascriptComments($commentArray) {
	global $blogCommentService;
	global $showComments;
	global $commentsEnabled;
	global $showNumberOfComments;
	global $reverseCommentEntries;
	global $commentIntroduction;
	global $commentHideString;
	global $commentShowString;
	global $commentArrayIE;
	global $showHideComments;
	global $disqusShortName;
	if ($blogCommentService==3) { // disqus 
		print "<script type='text/javascript'>\n";
		print "var disqus_shortname = '$disqusShortName';\n";
		print "(function () {\n";
		print "  var s = document.createElement('script'); s.async = true;\n";
		print "  s.src = 'http://disqus.com/forums/$disqusShortName/count.js';\n";
		print "  (document.getElementsByTagName('HEAD')[0] || document.getElementsByTagName('BODY')[0]).appendChild(s);\n";
		print "}());\n";
		print "</script>\n";
		
		
	}
	if ($blogCommentService!=0 || !	$showComments) return ; // Only trigger this function for blogger style comments to show
	
	/*  Handle javascript comments */
	print "<script type='text/javascript'>";
	if ($showComments && $commentsEnabled)   
		print "var showComments=1;\n";
	else
		print "var showComments=0;\n";
	
	if ($reverseCommentEntries)
		print "var reverseCommentEntries=1;\n";
	else
		print "var reverseCommentEntries=0;\n";
	
	print "var commentIntroduction='$commentIntroduction';\n";
	print "var commentHideString='$commentHideString';\n";
	print "var commentShowString='$commentShowString';\n";
	print "var showHideComments= '$showHideComments';\n";
	print "</script>";	
	
	if (0) { // I know, we shouldn't browser sniff but this works around an IE bug
		
		/************************* Begin IE specific javascript ********/
		?>
		
		<script type="text/javascript">
		
		function dhtmlLoadScript(url)
		{
			var e = document.createElement("script");
			newUrl=url.replace(/&amp;/g,'&');
			e.src = newUrl;	
			e.type="text/javascript";
			document.getElementsByTagName("head")[0].appendChild(e);
		}
		
		onload = function()
		{
			<?php
			foreach ($commentArrayIE as $comment) 
			print "dhtmlLoadScript('$comment');\n";
			// print 'alert("' . $comment . '");';
			
			
			?>
			
		}
		
		</script>
		<?php
		/************ End IE Specific javsacript ******************/
		
		
	} else /* All other browsers get 'regular' callbacks since they can handle it cleanly */
		foreach ($commentArray as $comment) {
			print $comment;
			
		}	
}


// These little functions help deal with quoted characters
function qq($text) {return str_replace('`','"',$text); }
function printq($text) { print qq($text); }
function printqn($text) { print qq($text)."\n"; }
function printn($text) {print $text . "\n"; }



// escape (quotes) the string

function htmlEscape($str) {
	$in=Array("\"","'");
	$out=Array("&quot;","&apos;");
	return str_replace($in,$out,$str);
	
}

function str_split_php4($str)
{
	
	return preg_split('#(?<=.)(?=.)#s', $str);
}


/********************************************************
 utf2html.  Take a UTF formatted string (which is all blogger speaks)
 and convert it to decimal:  e.g. N√§ =>  N&#228;
 Original Source: http://us3.php.net/manual/en/function.utf8-decode.php#45561
 ***********************************************************/
function utf2html ($str) {
	global $pageCharSet;
	
	if ($pageCharSet=="utf-8") {
		return $str; /* IF they used UTF8 return it unmodified */
	}
	else {
		return utf8_to_html($str); 
	}
} 



// Function to convert multibyte utf-8 to html encoded
// Taken from http://us3.php.net/manual/en/function.utf8-decode.php#75941
// e.g. a b √© ¬Æ ƒç ƒá ≈æ „Åì „Å´ „Å° „Çè  dd    d    d d d  d  ‚Äî ()[]{}!#$? ==> a b &#233; &#174; &#269; &#263; &#382; &#12371; &#12395; &#12385; &#12431;  dd    d    d d d  d  &#8212; ()[]{}!#$?*


function utf8_to_html ($data)
{
    return preg_replace("/([\\xC0-\\xF7]{1,1}[\\x80-\\xBF]+)/e", '_utf8_to_html("\\1")', $data);
}

function _utf8_to_html ($data)
{
    $ret = 0;
    foreach((str_split_php4(strrev(chr((ord($data{0}) % 252 % 248 % 240 % 224 % 192) + 128) . substr($data, 1)))) as $k => $v)
	$ret += (ord($v) % 128) * pow(64, $k);
    return "&#$ret;";
}

/* This little function simply takes in a list of arguments
 and creates a valid url.  The first argument is converted from a &
 to a ?
 */
function buildUpUrl()
{
    $numargs = func_num_args();
	
    $arg_list = func_get_args();
    $foundFirstArgument=false;
    $ret="";
    for ($i = 0; $i < $numargs; $i++) {
		$arg=$arg_list[$i];
		if ($arg[0]=="&" && !$foundFirstArgument) {
			$arg[0]="?";
			$foundFirstArgument=true;
		}
		$ret=$ret.$arg;
    }
    return $ret;
}

// The title can be in the form of '4 comments'
// or in some languages the number is at the end
// this function returns the higher of the two.
function numberCommentsFromTitle($title) {
	
	preg_match('{(\d+)}', $title, $m); 
	$number = $m[1];
	return $number;
	
}

// Fakes out a'namespace' type argument -- code is safely evaled.
function rapidblogSafeEval($code){
	$fn="rb_".md5($code);
	if (!function_exists($fn)) {
		eval("function $fn() { $code } ");
	}
	$fn();
}

// coneience function to set up javascript variables
function rapidblogJavascriptVars() {
	global $blogPostPermaLink;
	global $blogPostTitle;	
	print "<script type='text/javascript'>\nblogPostPermaLink='$blogPostPermaLink';\nblogPostTitle='$blogPostTitle';\n</script>";	
}			

?>
