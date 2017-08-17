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

$matches = [];
preg_match('/\/tmp\/([0-9A-Za-z_]+)/', $_POST['set_dir'], $matches);
$zipFileName = $matches[1];

$zip = new ZipArchive();
$zip->open("tmp/$zipFileName.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Create recursive directory iterator
/** @var SplFileInfo[] $files */
$rootPath = realpath($dir);
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file)
{
    // Skip directories (they would be added automatically)
    if (!$file->isDir())
    {
        // Get real and relative path for current file
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        // Add current file to archive
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();

$s3Client = S3Client::factory(array(
    'credentials' => array(
        'key'    => $clientprofilekey,
        'secret' => $clientprofilesecret,
    ),
	'region' => 'us-east-1',
	'version' => 'latest'
));

$result = $s3Client->putObject(array(
    'Bucket' => $bucket,
    'Key'    => $zipFileName . '.zip',
    'SourceFile' => "tmp/$zipFileName.zip"
));

$to = $email;
$subject = "Image Upload";
$txt = $result['ObjectURL'] . " is the URL of the downloadable bundle.";

$headers = array(
  'From: "Bowdoin Orient Photo" <noreply@upload.bowdoinorient.co>' ,
  'X-Mailer: PHP/' . phpversion() ,
  'MIME-Version: 1.0' ,
  'Content-type: text/html; charset=iso-8859-1' ,
);

if(isset($cc)) {
	$headers[] = 'CC: ' . implode(", ", $cc);
}

$headers = implode( "\r\n" , $headers );

mail($to,$subject,$txt,$headers);

header("Location: http://upload.bowdoinorient.co");
exit();

