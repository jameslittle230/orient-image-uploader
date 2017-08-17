<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$name = $_POST['name'];
$assignment = $_POST['assignment'];
$files = $_FILES['file'];

$safename = preg_replace("/[^A-Za-z0-9]/", '_', strtolower($name));
$safeassignment = preg_replace("/[^A-Za-z0-9]/", '_', strtolower($assignment));
$dirname = date('Ymd') . '_' . $safename . '_' . $safeassignment;
$relativeDirpath = '/tmp/' . $dirname . '/';
$dirpath = __DIR__ . $relativeDirpath;

$oldmask = umask(0);

if(!mkdir($dirpath)) {
	umask($oldmask);
	echo json_encode(["error" => "The directory $dirpath already exists."]);
	die();
}

umask($oldmask);

$infotext = "Name: $name\nAssignment: $assignment\n\n";
file_put_contents($dirpath . 'info.txt', $infotext);

$index = 0;
$output = [];
$output['info'] = $relativeDirpath;

foreach($files['tmp_name'] as $file) {
	$name = preg_replace("/[^A-Za-z0-9\.]/", "_", $files['name'][$index]);
	$type = $files['type'][$index];
	$error = $files['error'][$index];
	$size = $files['size'][$index];

	$name_sans_ext = substr($name, 0, strpos($name, '.')); // IMG_0752
	$immediateImagedir = substr(md5(rand()), 0, 5) . '-' . $name_sans_ext; // 48d0a-IMG_0752
	$imagedir = $dirpath . $immediateImagedir; // /opt/bitnami/apache2/htdocs/tmp/48d0a-IMG_0752

	$oldmask = umask(0);
	mkdir($imagedir, 0777);
	umask($oldmask);

	move_uploaded_file($file, $imagedir . '/' . $name);

	file_put_contents($imagedir . '/' . $name_sans_ext . '_info.txt', "[$name]\n\n");

	$cmd = "nohup ./convert-images.sh \"$imagedir\" \"$name\" \"$name_sans_ext\" &";

	$output['cmds'][] = $cmd;


	$output['images'][$immediateImagedir] = [
		'src' => $relativeDirpath . $immediateImagedir . '/' . $name,
		'filename' => $name
	];

	$index++;
}
echo json_encode($output);
foreach($output['cmds'] as $cmd) {
	shell_exec($cmd);
}
