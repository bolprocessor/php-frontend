<?php
// 2026-11-20 Upload of the files and folders associated with a Csound resource (samples, wave tables...) to the folder of that
// resource, from the button UPLOAD ASSOCIATED FILES/FOLDERS of csound.php - by Claude
// The page sends one file per request, since PHP accepts only a few files per request (max_file_uploads), with its path in the folder:
// "file.aiff" for a sound file, "Folder/sub/file.flac" for a file of an uploaded folder.
// A single file must be a sound file (csound_sound_extensions()). A folder may also contain documentation (csound_upload_document_extensions()).
// The Csound code using these files is not checked. Returns JSON: 'ok' and 'message'.
ob_start(); // _basic_tasks.php must not add anything to the JSON answer
require_once("_basic_tasks.php");
ob_end_clean();
session_write_close();
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

function upload_reply($ok,$message) {
	echo json_encode(["ok" => $ok,"message" => $message]);
	exit;
	}

$cs_file = basename(trim((string)($_POST['cs_file'] ?? '')));
if($cs_file == '' OR !is_file(csound_resource_path($cs_file))) upload_reply(FALSE,"Unknown Csound resource ‘".$cs_file."’");
$upload_dir = csound_resource_dir($cs_file);
if(read_only_resource_folder($upload_dir)) upload_reply(FALSE,"‘".$cs_file."’ is a fixed file of the distribution");
if(!isset($_FILES['file']) OR $_FILES['file']['error'] <> UPLOAD_ERR_OK)
	upload_reply(FALSE,"The upload failed (error ".($_FILES['file']['error'] ?? '?').")");

// The path of the file in the folder
$relative = str_replace("\\",'/',trim((string)($_POST['path'] ?? '')));
$parts = explode('/',$relative);
foreach($parts as $part) {
	if($part == '' OR $part[0] == '.') upload_reply(FALSE,"Invalid name ‘".$relative."’");
	}
$name = end($parts);
$extension = strtolower(pathinfo($name,PATHINFO_EXTENSION));
if(count($parts) == 1) {
	if(!in_array($extension,csound_sound_extensions())) upload_reply(FALSE,"‘".$name."’ is not a sound file");
	}
else {
	if(!in_array($extension,csound_sound_extensions()) AND !in_array($extension,csound_upload_document_extensions()))
		upload_reply(FALSE,"‘".$relative."’ was skipped: only sound files and documentation are accepted in folders");
	if(resource_kind_of_type(type_of_file($parts[0])['type']) <> '') upload_reply(FALSE,"A folder may not be named like a resource: ‘".$parts[0]."’");
	}

$target = $upload_dir.implode(SLASH,$parts);
if(is_dir($target)) upload_reply(FALSE,"‘".$relative."’ is a folder");
$folder = dirname($target);
if(!is_dir($folder)) {
	if(!@mkdir($folder,$permissions,TRUE)) upload_reply(FALSE,"Folder ‘".dirname($relative)."’ could not be created");
	// mkdir() is limited by umask: the permissions are set on each new folder
	for($sub = $folder; strlen($sub) > strlen($upload_dir) AND is_dir($sub); $sub = dirname($sub)) @chmod($sub,$permissions);
	}
$replaced = file_exists($target);
if(!move_uploaded_file($_FILES['file']['tmp_name'],$target)) upload_reply(FALSE,"‘".$relative."’ could not be saved");
@chmod($target,$permissions);
upload_reply(TRUE,($replaced ? "replaced" : "added"));
?>
