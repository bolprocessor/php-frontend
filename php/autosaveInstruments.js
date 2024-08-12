function getData2() {
	var fd = new FormData();
	var temp_folder = document.getElementsByName('temp_folder')[0].value;
	var dir = document.getElementsByName('dir')[0].value;
	var filename = document.getElementsByName('filename')[0].value;
	var number_channels = document.getElementsByName('number_channels')[0].value;
	var ch;
	var whichCsoundInstrument = [];
	var arg;
	for(ch = 0; ch < number_channels; ch++) {
		arg = 'whichCsoundInstrument_' + ch;
		whichCsoundInstrument[ch] = document.getElementsByName(arg)[0].value;
		} 
	var CsoundOrchestraName = document.getElementsByName('CsoundOrchestraName')[0].value;
	var number_instruments = document.getElementsByName('number_instruments')[0].value;
	var begin_tables = document.getElementsByName('begin_tables')[0].value;
	var cstables = document.getElementsByName('the_tables')[0].value;
	var tonality_filename = document.getElementsByName('tonality')[0].value;
	fd.append('temp_folder',temp_folder);
	fd.append('dir',dir);
	fd.append('filename',filename);
	fd.append('number_channels',number_channels);
	for(ch = 0; ch < number_channels; ch++) {
		arg = 'whichCsoundInstrument_' + ch;
		fd.append(arg,whichCsoundInstrument[ch]);
		}
	fd.append('CsoundOrchestraName',CsoundOrchestraName);
	fd.append('number_instruments',number_instruments);
	fd.append('begin_tables',begin_tables);
	fd.append('cstables',cstables);
	fd.append('tonality_filename',tonality_filename);
	return fd;
	}

function savePost2() {
	try {
		var xhttp = new XMLHttpRequest();
		}
	catch(e) {
		console.log(e);
		}
	var data2 = getData2();
	xhttp.open('POST','autosaveInstruments.php?save=1');
	xhttp.send(data2);
	xhttp.onreadystatechange = function() {
		if(this.status == 200 && this.readyState == 4) {
			document.getElementById('message2').innerHTML = this.responseText;
			console.log(this.responseText);
			}
		}
	}
var myVar2 = setInterval(savePost2, 30000);
