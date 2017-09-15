<?php
require __DIR__ . '/aws/aws-autoloader.php';
include __DIR__ . '/secret.php';

use Aws\S3\S3Client;

if(!$_POST['set_dir']) {
	die();
}

$dir = __DIR__ . $_POST['set_dir'];

foreach($_POST as $imgname => $desc) {
	if($imgname == "set_dir") {
		continue;
	}

	$outerInfoText = "[$imgname]\n$desc\n\n";
	file_put_contents($dir . 'info.txt', $outerInfoText, FILE_APPEND);
	file_put_contents($dir . $imgname . '/info.txt', $outerInfoText);
}

file_put_contents($dir . '.done', "");

header("Location: http://upload.bowdoinorient.co");
exit();
