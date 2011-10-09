// Javsacript helper functions for RapidBlog

// javscript to list comments out to make it mo-faster
function listComments(root) {
	var feed = root.feed;
	
	var entries = feed.entry || [];
	var html = [];
	
	
	if (!feed.entry)
		return;
	//$t = "tag:blogger.com,1999:blog-4270442078467138119.post2447883094318407169..comments"
	
	var re=/post([0-9]+)/i
	var joe=re.exec(feed.id.$t);
	var myId=joe[1];
	var hideCommentCss="";
	
	//2007-01-01T22:58:00.000-08:00
	dateRe=/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):\d{2}.\d{3}/;
	
	
	
	if (showComments==1) {
		if (showHideComments) { // Comments always start hidden if the show/hide comment is selected
			hideCommentCss="display:none;visibility:hidden;height:0;";	
		}
		html.push('<div class="blog-entry-comments-body" id="comment-'+myId+'-body" style="'+hideCommentCss+'"> ');
		
		// reverse the comments if requested
		if (reverseCommentEntries==1)
			feed.entry.reverse();	
		
		for (var i = 0; i < feed.entry.length; ++i) {
			if ((i%2)==0)
				html.push('<br class="blog-entry-comments-break"/> <div class="blog-entry-comments-body-even">');
			else
				html.push('<br class="blog-entry-comments-break"/> <div class="blog-entry-comments-body-odd">');
			
			var entry = feed.entry[i];
			var title = entry.title.$t;
			var published=entry.published.$t;
			var dateArray=dateRe.exec(published);
			var thisDate=new Date(dateArray[1],dateArray[2]-1,dateArray[3],dateArray[4],dateArray[5],0)
			// var start = entry['gd$when'][0].startTime;
			var content = entry.content.$t;
			
			// Figure out the author & a web page if given one
			var URI=null;
			var author=null;
			
			author = entry.author[0].name.$t;
			
			if (entry.author[0].uri)
				var URI= entry.author[0].uri.$t;
			if (URI!=null)  {
				var httpRe=/http:\/\//;
				if (!httpRe.exec(URI)) { // if they didn't give a leading HTTP then we need to add one.
					URI="http://"+URI;
				}
				author="<a href='"+URI+"'>"+author+"</a>";
			}
			if (!commentIntroduction)
				commentIntroduction=' said ... '; // if it's supplied externally use th
			html.push(author, commentIntroduction,'<br />', '<div>', content, ' </div> ',thisDate.toLocaleString(),'</div>');
			
		}
		html.push('</div>'); // Final closing div that includes all commens
		
	}
	document.getElementById("comment_"+myId).innerHTML += html.join("");
}


// Will toggle showing/hiding of type 'element' -- Also will change the 
// inner HTML of id-link to either 'hide message' or 'show message'
// which are assumed to be globals - Used for comments 
function toggleHide(id) {
	ptr=document.getElementById(id);
	if (ptr.style.visibility=='hidden') {
		ptr.style.visibility='';
		ptr.style.height='';
		ptr.style.display='';
		document.getElementById(id+"-link").innerHTML=commentHideString;	
		
	}
	else {
		ptr.style.visibility='hidden';
		ptr.style.height='0'
		ptr.style.display='none';
		document.getElementById(id+"-link").innerHTML=commentShowString;
	}
}
