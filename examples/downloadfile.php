<?php
require_once('APIs/google-api-php-client/GDrive.php');

$gdrive = new Google_Drive_Adapter(array(
    'client_id'=>'<client_email>',
    'client_email'=>'<client_email>',
    'key_file'=>'<P12 key_file>',
    'scope' => array('https://www.googleapis.com/auth/drive',
        'https://www.googleapis.com/auth/drive.appfolder',
        'https://www.googleapis.com/auth/drive.file',
        'https://www.googleapis.com/auth/drive.apps.readonly'),
));

/** Build service **/
$gdrive->auth()
    ->apiClient()
    ->buildDriveService();

/** Get file content from Google Drive **/
$file_content = $gdrive->downloadFile('0B4Isu853jI7pNVpOdTd5RHpaMHM');

/** Save content **/
file_put_contents('download_from_gdrive.rar', $file_content);