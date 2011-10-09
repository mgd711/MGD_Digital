<?php
	
	require_once "PEAR.php";
	require_once "Requestrb.php";
	
	
	/* Temporary test */
	//if (function_exists(memory_get_usage)) {
	
	//	print "<pre>Memory Usage (starting) =" . memory_get_usage() . "\n</pre>";
	//}
	@set_time_limit(60);
	
	/* end temporary test */
	
$editStyle= <<<EOT
	<style type="text/css">
	
	.gdoc-editor-offset-div {
	margin: 1px;
	}
	
	.gdoc-editor {
		border-style: dotted;
		border-width: 1px;
	}
	
	.edit-button {
	color: #414141;
		background-color: #fff7f7;
		text-decoration: none;
		border-style: solid;
		border-width: 1px;
		float: right;
	clear: both;	
		text-shadow: 0px 0px;
		-moz-border-radius-bottomright: 5px 5px;
		-moz-border-radius-bottomleft: 5px 5px;
		-webkit-border-bottom-right-radius: 5px 5px;
		-webkit-border-bottom-left-radius: 5px 5px;	
	}
	
	</style>
EOT;
	
	
	function getGcell($url,$skipWhitespace=1) {
		
		
		print "<!-- About to fetch : $url --> \n\n";
		$req=new HTTP_Request();
		
		$req->_allowRedirects = true; /* Allow redirects as blogger tends to do that */
		$req->setMethod(HTTP_REQUEST_METHOD_GET); 
		
		$req->setURL($url);
		
		
		$rData="";
		if($req->sendRequest()) {
			
			$rData=$req->getResponseBody();
			
		} else {
			print "********** Error in sendRequest *****";
			
		}
		
		$escape=Array("&lt;","&gt;","&#039;","&#39;","&amp;");
		$unEscape=Array("<",">","'","'","&");
		
		if (preg_match("/<content type=[^>].*?>([^<].*)<\/content>/uis",$rData,$joe)) {



return (str_replace($escape,$unEscape,$joe[1]));
}
else return str_replace($escape,$unEscape,$rData);

}

function getHTML($url) {

//print "About to fetch : $url\n\n";
$req=new HTTP_Request();

$req->_allowRedirects = true; /* Allow redirects as blogger tends to do that */
$req->setMethod(HTTP_REQUEST_METHOD_GET);




$req->setURL($url);

$rData="";
if($req->sendRequest()) {

$rData=$req->getResponseBody();



} else {
print "********** Error in sendRequest *****";

}



return $rData;

}
function getFeed($url) {

//print "About to fetch : $url\n\n";
$req=new HTTP_Request();

$req->_allowRedirects = true; /* Allow redirects as blogger tends to do that */
$req->setMethod(HTTP_REQUEST_METHOD_GET);




$req->setURL($url);

$rData="";
if($req->sendRequest()) {

$rData=$req->getResponseBody();



} else {
print "********** Error in sendRequest *****";

}



//print_r($ret);


if (strncasecmp("<?xml",$rData,5)!=0) {
print("<pre>$rData</pre>"); // Should be xml... flag if it's not

}
return $rData;

}


function gTable($data,$header=true,$id="",$theme="",$filesFolder=".",$docId="") {

global $editStyle; // the global stylings

if (!count($data)) return; 

if ($id!="") $myId="id='$id'";

if (
strtolower(
$_SERVER
['QUERY_STRING'])==

'edit' ) {
print $editStyle;
print "<div class=gdoc-editor-offset-div>";
print "<div class='gdoc-editor'>";
}

print "<table class='gtable $class' $myId>";

if ($header) {
print "<thead>";
print "<tr>";

if ($data
["_headers"]!= null) 
$headers=$data
["_headers"];
else
$headers=array_keys($data);
$i=0;
foreach ($headers as $header) {
if ($i==count(array_keys($data))-1) // handle the last column
break;
$i++;
print "<th>".$header."</th>";
}
print "</tr>";
print "</thead>";
}


$tmpBody=$data["_headers"];
unset($data["_headers"]); // hide headers, kind of hacky but it works 

print "<tbody>\n";

$max=count(reset($data));
for ($i=0;$i<$max;$i++) {
$even="even";
if ($i%2!=0)
$even="odd";
if ($i==$max-1) 
$even="$even lastrow";
print "<tr class='row$i $even'>";
$p=0;
foreach ($data as $head) {
$even="even";
if ($p%2!=0)
$even="odd";

print "<td class='column$p $even'>" . $head[$i] . "</td>\n";
$p++;
}
print "</tr>\n";
}

print "</tbody>\n";
print "</table>";

if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
print "</div><a class='edit-button'  href='http://spreadsheets.google.com/ccc?key=$docId'>Edit</a></div>";
}
$data["_headers"]=$tmpBody; // restore headers

// finally inject the table theme

if ($theme!="") {

$jsCss="
<script type='text/javascript' > 

var headID = document.getElementsByTagName('head')[0];         
var cssNode = document.createElement('link');
cssNode.type = 'text/css';
cssNode.rel = 'stylesheet';
cssNode.href = '$filesFolder/$id-$theme.css';
cssNode.media = 'screen';
headID.appendChild(cssNode);
</script>";

print $jsCss;
}

/* Temporary test */
//	if (function_exists(memory_get_usage)) {

//	print "<pre>Memory Usage (ending) =" . memory_get_usage() . "\n</pre>";
//	}

//	print "<pre>";
//	print_r(getrusage());
//	print "</pre>";

/* end temporary test */

}

function gunPrettify($str){
$invalid=Array(" ","\t","<",">","~","!","@","#","$","%","^","&","*","(",")","-","_","=","+","\\","|","[","]","{","}",";",":"); //"

$ret=str_replace($invalid,"",strtolower($str));
return $ret;
}



function gListTable($url,$ignoreColumns="",$htmlColumns="") {
	
	
	$ignoreColumns=replaceVariables($ignoreColumns);
	$htmlColumns=replaceVariables($htmlColumns);
	
	//	print "<pre>$url</pre>";
	
	$out=getFeed($url);
	
	
	
	
	
	//print $out;
	preg_match_all("/<gsx:([^>].*?)>(.*?)<\\/gsx/uis", $out, $title, PREG_SET_ORDER);
	
	
	foreach ($title as $entry) {
		
		
		$data[$entry[1]][]=$entry[2];
		
	}
	
	if (count($data)==0) return;
	
	// remove strange _clrrx entries  
	unset($data['_clrrx']);
	
	// pull out the headers
	
	preg_match_all("/<content type=['\"]text([^>].*?)>(.*?)<\\/content/uis", $out, $descr, PREG_SET_ORDER);
	
	
	
	$firstLine=$descr[0][2];
	
	//print $firstLine;
	$headers=explode(",",$firstLine);
	
	//print_r($headers);
	
	$tmp=array_keys($data);
	// First fill the headers with guaranteed default values...
	foreach ($tmp as $entries) {
		$data["_headers"][]=$entries;
	}
	foreach ($headers as $header) {
		
		$split=explode(":",$header);
		$notPretty=gunPrettify(trim($split[0]));
		foreach ($data["_headers"] as $key=>$value) {	
			if ($value==$notPretty) {
				$data["_headers"][$key]=trim($split[0]);
			}
		}
		
	}
	
	
	// Now process the ignoreColumns (if any)
	
	$myConvert=get_html_translation_table(HTML_ENTITIES); // get rid of any html escapes RW put in
	
	$columnsToIgnore=explode(";",str_replace(array_values($myConvert),array_keys($myConvert),$ignoreColumns));
	
	//print(str_replace(array_keys($myConvert),array_values($myConvert),$ignoreColumns));
	
	foreach ($columnsToIgnore as $columnsIgnored) {
		$unprettyName=gunPrettify($columnsIgnored);
		unset($data[$unprettyName]);
		$data["_headers"]=array_diff($data["_headers"],Array($columnsIgnored,gunPrettify($columnsIgnored)));
		
		
	}
	
	// Now handle option HTML columns
	
	
	$columnsToHTML=explode(";",str_replace(array_values($myConvert),array_keys($myConvert),$htmlColumns));
	
	
	foreach ($columnsToHTML as $columnsHTML) {
		
		$unprettyName=gunPrettify($columnsHTML);
		$escape=Array("&lt;","&gt;","&#039;","&#39;","&amp;");
		$unEscape=Array("<",">","'","'","&");
		if (count($data[$unprettyName])>0) // only fix up valid columns
			foreach ($data[$unprettyName] as $columnKey => $columnEntry) {
				$data[$unprettyName][$columnKey]=str_replace($escape,$unEscape,$columnEntry);
			}	
		
	}
	return $data;
	
}

function gNoteBook($url) {
	
	
	$out=getFeed($url);
	
	//print $out;
	preg_match_all("/<content type=['\"]html([^>].*?)>(.*?)<\\/content/uis", $out, $title, PREG_SET_ORDER);
	
	$escape=Array("&lt;","&gt;","&#039;","&#39;","&amp;");
	$unEscape=Array("<",">","'","'","&");
	
	
	(str_replace($escape,$unEscape,$joe[1]));
	if (count($title)>0)
		foreach ($title as $item) {
			$ret[]=str_replace($escape,$unEscape,$item[2]); 
		}
	return $ret;
	
}

function gNote($url,$class="gnote") {
	$res=gNoteBook($url);
	
	$i=0;
	print "<div class='notebook-entries $class'>";
	if (count($res)>0)
		foreach ($res as $entry) {
			$even="even";
			if ($i%2!=0)
				$even="odd";
			
			print "<div class='notebook-entry notebook-entry-$i notebook-entry-$even'>\n$entry\n</div>";
			$i++;
		}  
	print "</div>";
	
	
}

function replaceVariables($inString) {
	preg_match_all("/%(.*?)%/uis", $inString, $possibleVars, PREG_SET_ORDER);
	
	$res=$inString;
	foreach ($possibleVars as $possibleVar) {
		$postGet=explode("|",$possibleVar[1]);
		
		if ($def=$_POST[$postGet[1]]) {
			$res=str_replace($possibleVar[0],$def,$res);
		} 
		elseif ($def=$_GET[$postGet[1]]) {
			$res=str_replace($possibleVar[0],$def,$res);
		} 
		
		else {
			$res=str_replace($possibleVar[0],$postGet[0],$res);
		}
	}
	return $res;
}

function fixSearch($inSearch) {
	
	$outSearch=urlencode(replaceVariables(trim($inSearch)));
	return $outSearch;
}

function editUrl($docId) {
	
	if (strlen($docId)<=34) {
		$ret="http://docs.google.com/Doc?id=$docId";
	} else {
		$ret="https://docs.google.com/document/pub?id=$docId";
	}
	
	return $ret;
}

// This used to be the superior solution but it's broken as of 11/21/08
// so I'm marking it 'unScrape' from it's former 'gDoc' vers
function gDocUnScrape($docId){
	global $editStyle;
	$resultString=getHtml("http://docs.google.com/feeds/download/documents/RawDocContents?action=fetch&justBody=true&revision=_latest&editMode=false&docID=$docId");
	
	$resultString=str_replace("File?","http://docs.google.com/File?",$resultString);
	
	$resultString=str_replace("View?docid=$docId","",$resultString);
	$resultString=str_replace("View?","http://docs.google.com/View?",$resultString);	
	// If the user passes in 'edit' then we go into edit mode
	
	// Deal with broken google behavior oct 3, 2008
	$brokenGoogle=strpos($resultString,"<!DOCTYPE HTML PUBLIC");
	if (!($brokenGoogle===false)) {
		$resultString=substr($resultString,0,$brokenGoogle);
		$errorLoc=super_conforming_strrpos($resultString,"Error:");
		if (!($errorLoc===false))
			$resultString=substr($resultString,0,$errorLoc);
	}
	
	
	
	if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
		print $editStyle;
		print "<div class='gdoc-editor-offset-div'>";
		print "<div class='gdoc-editor'>";
	}
	print $resultString;
	
	if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
		$urlToEdit=editUrl($docId);
		print "</div><a class='edit-button'  href='$urlToEdit'>Edit</a></div>";
	}
}

// this version is less reliable but has the advantage of always being able to 
// pick off the most recent published version (the standard gdoc requires you have automatic republishing set)
// Update 11/21/08 -- Google changed it again so only gdoc scrape works.  making this standard for now

function gDoc($docId){
	if (strlen($docId)>42) 
		return gDocNewEditor($docId);
	else
		return gDocOldEditor($docId);
}

function escapeCSS($html){
preg_match('/<style type="text\/css">(.*)<\/style>/uis',$html,$preg);

if (strlen($preg[1])) {
	$findWords=Array("body","h1","h2","h3","h4","h5","h6");
	$replaceWords=Array();
	foreach ($findWords as $word) {
		$replaceWords[]="\n.gdoc ".$word;
	}
	$sub=$preg[1];
	
	$sub=str_ireplace($findWords,$replaceWords,$sub);
	
	$html=str_replace($preg[1],$sub,$html);
}

$ret= "<span class='gdoc'>$html</span>";
return $ret;
}

function checkForHttpsData($result) { 
	if (strlen($result)==0) {
		if (function_exists(stream_get_wrappers)) {
			if (!in_array('https', stream_get_wrappers()))
				print "<h2>PHP Needs to be configured with --with-openssl to register https as a stream for pluskit google functions to work</h2>";
		}
	}
	
}


function gDocNewEditor($docID) {
	
	$a=getHtml("https://docs.google.com/document/pub?id=$docID");
	checkForHttpsData($a);
 	//print $a;
	preg_match('/<div[^>.]*id="contents">/uis',$a,$b);
	$start=strpos($a,$b[0]);
	
	preg_match('/<div[^>.]*id="footer">/uis',$a,$b);
	
	$end=strpos($a,$b[0]);		
	$resultString=substr($a,$start,$end-$start);
	$resultString=str_replace(" ","<br /> ",$resultString);	
	// fix up pictures
	$resultString=str_replace("pubimage?","https://docs.google.com/document/pubimage?",$resultString);
	
	// fix up css
	$resultString=escapeCSS($resultString);
	
	if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
		print $editStyle;
		print "<div class='gdoc-editor-offset-div'>";
		print "<div class='gdoc-editor'>";
	}
	print $resultString;
	
	if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
		$urlToEdit=editUrl($docID);
		print "</div><a class='edit-button'  href='$urlToEdit'>Edit</a></div>";
	}
	
	
	
	
}
function gDocOldEditor($docID) {
	global $editStyle;
	
	$a=getHtml("http://docs.google.com/View?docid=$docID");
	
	
	preg_match('/<div[^>.]*id="doc-contents">/uis',$a,$b);
	
	$start=strpos($a,$b[0]);
	
	preg_match('/<div[^>.]*id="google-view-footer">/uis',$a,$b);
	
	$end=strpos($a,$b[0]);
	
	
	
	$resultString=substr($a,$start,$end-$start);
	
	$resultString=str_replace("File?","http://docs.google.com/File?",$resultString);
	$resultString=str_replace("View?docid=$docId","",$resultString);
	$resultString=str_replace("View?","http://docs.google.com/View?",$resultString);	
	$resultString=str_replace("fileview?","http://docs.google.com/fileview?",$resultString);	
	
	$resultString=str_replace("[^/]drawings/image?","http://docs.google.com/drawings/image?",$resultString);
	
		// fix up css
	$resultString=escapeCSS($resultString);
	
	
	if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
		print $editStyle;
		print "<div class='gdoc-editor-offset-div'>";
		print "<div class='gdoc-editor'>";
	}
	print $resultString;
	
	if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
		$urlToEdit=editUrl($docID);
		print "</div><a class='edit-button'  href='$urlToEdit'>Edit</a></div>";
	}
}


function super_conforming_strrpos($haystack, $needle, $offset = 0)
{
    # Why does strpos() do this? Anyway...
    if(!is_string($needle)) $needle = ord(intval($needle));
    if(!is_string($haystack)) $haystack = strval($haystack);
    # Setup
    $offset = intval($offset);
    $hlen = strlen($haystack);
    $nlen = strlen($needle);
    # Intermezzo
    if($nlen == 0)
    {
        trigger_error(__FUNCTION__.'(): Empty delimiter.', E_USER_WARNING);
        return false;
    }
    if($offset < 0)
    {
        $haystack = substr($haystack, -$offset);
        $offset = 0;
    }
    elseif($offset >= $hlen)
    {
        trigger_error(__FUNCTION__.'(): Offset not contained in string.', E_USER_WARNING);
        return false;
    }
    # More setup
    $hrev = strrev($haystack);
    $nrev = strrev($needle);
    # Search
    $pos = strpos($hrev, $nrev, $offset);
    if($pos === false) return false;
    else return $hlen - $nlen - $pos;
}

function gFormReplace($text,$thanks,$docId) {
	
	preg_match('|<form.*</form>|uis',$text,$b);
	$ret=$b[0];
	
	if ($thanks!="")
		$ret=preg_replace('/action="[^"]*"/','',$ret);
	
	if ($ret!="") {
		print($ret); }
	else print($text);
	
	if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
		print "</div><a class='edit-button'  href='http://spreadsheets.google.com/ccc?key=$docId'>Edit</a></div>";
	}
	
}
function gForm($docId,$thanks=""){ //
	global $editStyle;
	
	$key="key";
	if (strlen($docId)==34)
		$key="formkey";
	
	
	if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
		print $editStyle;
		print "<div class=gdoc-editor-offset-div>";
		print "<div class='gdoc-editor'>";
	}
	
	if(count($_POST)==0) {
		$a=getHtml("https://spreadsheets.google.com/viewform?$key=$docId");
		checkForHttpsData($a);
		
		gFormReplace($a,$thanks,$docId);
		
		
		return;
	} else {
		$req=new HTTP_Request();
		
		$req->_allowRedirects = true; /* Allow redirects as blogger tends to do that */
		$req->setMethod(HTTP_REQUEST_METHOD_POST); 
		$url="http://spreadsheets.google.com/formResponse?$key=$docId";
		
		$req->setURL($url);
		
		$data = file_get_contents('php://input'); // done!	
		
		$req->addRawPostData($data);
		
		
		if($req->sendRequest()) {
			$result=$req->getResponseBody();
			if ((!(strpos($result,"errorheader")===false)) || ($req->getResponseCode()>=300) || ($thanks=="")) {
				print "		<style type='text/css'> .ss-powered-by,small {display:none;!important}.errorbox-bad {border-color:red;border-width:2px; border-style:solid;border-radius:3px;padding:1em;}</style>";
				gFormReplace($result,$thanks,$docId);
			}
			else print $thanks;				
		}
		
	}
}

function gTableNative($docId){
	global $editStyle;
	$a=getHtml("http://spreadsheets.google.com/pub?key=$docId&output=html");
	$resultString=str_replace("./client/css","http://spreadsheets.google.com/client/css",$a);
	//pub?
	
	preg_match("%client/css/[a-zA-Z0-9\.].*([\"'\"])%ui",$resultString,$b);
	$cssString="<link href='http://spreadsheets.google.com/$b[0] >";
	
	
	print $cssString;
	
	preg_match('/<div[^>.]*id="content">/uis',$a,$b);
	
	$start=strpos($a,$b[0]);
	
	preg_match('/<div[^>.]*id="footer">/uis',$a,$b);
	
	$end=strpos($a,$b[0]);
	
	
	
	$resultString=substr($a,$start,$end-$start);
	$resultString=str_replace("pub?","http://spreadsheets.google.com/pub?",$resultString);
	if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
		print $editStyle;
		print "<div class=gdoc-editor-offset-div>";
		print "<div class='gdoc-editor'>";
	}
	print $resultString;
	if (strtolower($_SERVER["QUERY_STRING"])=="edit" ) {
		print $editStyle;
		print "</div";
		print "</div>";
	}	
	
	return;
	
}


?>

