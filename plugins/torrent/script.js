function getHtml(doURL, doId) {
    var xmlReq = false;

    if (window.XMLHttpRequest) {
		try {    
			xmlReq = new XMLHttpRequest();
		} catch(e) {
			return true;
		}
	}
    else if (window.ActiveXObject) {
       	try {
        	xmlReq = new ActiveXObject("Msxml2.XMLHTTP");
      	} catch(e) {
        	try {
          		xmlReq = new ActiveXObject("Microsoft.XMLHTTP");
        	} catch(e) {
          		return true;
        	}
		}
    }    
    else 
    	return true;

	xmlReq.open('GET', doURL, true);
    xmlReq.onreadystatechange = function() {
    if (xmlReq.readyState == 4) {
	    refresh(xmlReq.responseText, doId);
    }
   	}
	xmlReq.send("");
    return false;
}

function refresh(doStr, doId){
	    document.getElementById(doId).innerHTML = doStr;
}

function doInterval() {
	getHtml("dyn_progress.php","runningtable");
	getHtml("dyn_stopped.php","stoppedtable");
}
