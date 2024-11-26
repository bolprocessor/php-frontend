function getData3() {
	var fd = new FormData();
	var temp_folder = document.getElementsByName('temp_folder')[0].value;
	var dir = document.getElementsByName('dir')[0].value;
	var filename = document.getElementsByName('filename')[0].value;
	var begin_tables = document.getElementsByName('begin_tables')[0].value;
	fd.append('temp_folder',temp_folder);
	fd.append('dir',dir);
	fd.append('filename',filename);
	fd.append('begin_tables',begin_tables);
	return fd;
	}

function savePost3() {
	try {
		var xhttp = new XMLHttpRequest();
		}
	catch(e) {
		console.log(e);
		}
	var data3 = getData3();
	xhttp.open('POST','autosaveTonality.php?save=1');
	xhttp.send(data3);
	xhttp.onreadystatechange = function() {
		if(this.status == 200 && this.readyState == 4) {
			document.getElementById('message3').innerHTML = this.responseText;
			console.log(this.responseText);
			}
		}
	}
var myVar3 = setInterval(savePost3, 30000);
