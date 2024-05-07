function getData1() {
	var fd = new FormData();
	var temp_folder = document.getElementsByName('temp_folder')[0].value;
	var dir = document.getElementsByName('dir')[0].value;
	var filename = document.getElementsByName('filename')[0].value;
	var PrototypeTickKey = document.getElementsByName('PrototypeTickKey')[0].value;
	var PrototypeTickChannel = document.getElementsByName('PrototypeTickChannel')[0].value;
	var PrototypeTickVelocity = document.getElementsByName('PrototypeTickVelocity')[0].value;
	var CsoundInstruments_filename = document.getElementsByName('CsoundInstruments_filename')[0].value;
	var maxsounds = document.getElementsByName('maxsounds')[0].value;
	var comment_on_file = document.getElementsByName('comment_on_file')[0].value;
	fd.append('temp_folder',temp_folder);
	fd.append('dir',dir);
	fd.append('filename',filename);
	fd.append('PrototypeTickKey',PrototypeTickKey);
	fd.append('PrototypeTickChannel',PrototypeTickChannel);
	fd.append('PrototypeTickVelocity',PrototypeTickVelocity);
	fd.append('CsoundInstruments_filename',CsoundInstruments_filename);
	fd.append('maxsounds',maxsounds);
	fd.append('comment_on_file',comment_on_file);
	return fd;
	}

function savePost() {
	try {
		var xhttp = new XMLHttpRequest();
		}
	catch(e) {
		console.log(e);
		}
	var data1 = getData1();
	xhttp.open('POST','autosaveObjects.php?save=1');
	xhttp.send(data1);
	xhttp.onreadystatechange = function() {
		if(this.status == 200 && this.readyState == 4) {
			document.getElementById('message1').innerHTML = this.responseText;
			console.log(this.responseText);
			}
		}
	}
var myVar = setInterval(savePost, 30000);
