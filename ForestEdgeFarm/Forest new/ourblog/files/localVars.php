<?php

/*
 *
 * Variables that change from within plugin 
 * Variables delimited with { - and - } will get replaced at runtime
 */

$phpBlogOrder='
printn (renderTitle($myEntry));

print "<div class=\'blog-entry-date\'>";
printn(renderDate($myEntry));

printn(renderCategories($myEntry));
printn(renderPermalink($myEntry));
print \'</div>\';
print "<div class=\'blog-entry-body\'>";
printn(renderBody($myEntry));
printn(renderAuthor($myEntry));
printn(renderComments($myEntry));
print \'</div>\';

printn(renderInterblog($myEntry,$i));
';

$categories=""; // Categories start out empty

// What kind of get should we do for each kind of page (full, or summary )

$bloggerGetMainPage="full" ; // "/default";
$bloggerGetCategoryPage="full";
$bloggerGetArchivePage="full";
$bloggerGetType=$bloggerGetMainPage ; // default type is the main page type
$bloggerURL="http://forestedgefarmextravirginoliveoil.blogspot.com/feeds/posts"; 


// Text that is presented to the user (controlled via setup)

$readMoreText="Read More...."; // What to say when in summary view and  a 'read more' occurs
$commentIdentifier="Comments";
$commentIntroduction="Said....";
$commentHideString="Hide Comments";
$commentShowString="Show Comments";
$permalinkTitle="Permalink";
$rssLinkName="RSS Feed";
$blogCommentService=0; // 0=blogger, 1=Haloscan
$haloscanUsername="0";
$disqusShortName="0"; // disqus short name for disqus comments


// Controls how many items are displayed
$howFarBackToView="30";//
$howFarBackToViewEnabled=1?false:true;// if enabled then we include the date in the search
$maxNumberPostsToShow="5";
$filesFolder="files";

// Things to show/hide

$showPermalinks=1;
$permalinkType=0; // 0=seperate, 1= in title
$showRSS=1;
$rssFileName="blogRSS.php";
$showAuthor=1;
$showNumberOfComments=1;
$reverseCommentEntries=1;
$bloggerCommentsInPopup=1;
$commentsEnabled=1;
$expandCommentsInMainPage=1;
$expandCommentsInPermalinkPage=1;
$expandCommentsInCategoryPage=0;
$showArchives=1;
$archivesDropdown=1;
$archivesDropdownTitle="Select Archive";
$showCategories=1;

// Date formats

$dateFormat="%B %e, %Y";
$timeFormat="%I:%M";
$dateTimeSeperator=",";
$dateLocalizeConvert=Array();


// Misc 
$tempFilesDirectory="/private/var/folders/4x/4xP-v-GsGimEtumlD8hTd++++TI/-Tmp-/TemporaryItems/RapidWeaver/876/document-0x25f70720/page9/PluskitPlugin"; // used only for preview mode
$exportMode="export"; // should be 'preview' or 'export' or 'publish'
$categoriesArray=Array(0=>'2009 Extra Virgin Olive Oil ready for sale',1=>'Extra Virgin Olive Oil',2=>'News from Japan',3=>'Olive Oil',4=>'Pruning time at Forest Edge Farm',5=>'Spring in the Grove 2010');
$gravatarArray=Array();
$gravatarSize=40;
$ajaxy=1; // Determine if we should render some effects in a ajaxy fashion

				
$urlToThisPage="http://www.copydick.com/blog/index.php";
	
// $blogCreationDate="0";
$archiveFormat="%b %Y";
$archiveOrdering=1;
$pageCharSet="0";

// interComments


$interHTMLText='';
$interEvaluatePHPCode=0; // true if we should pass the inter text through eval
$interTextAfterFirst=0-1; // php is zero based but users start with one so offset it
$interTextRepeat=999;

// Older Posts
$olderPostsString="See Older Posts...";
$olderPostsEnabled=1;

//
$random="424238335"; // believe it or not, this is just to guarantee you never get 'nothing to upload'


?>

