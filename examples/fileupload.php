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

/** Insert file **/
$gdrive->insertFileInFolder(
    'uploadfile.txt',               // file path
    'Uploadfile.txt',               // title
    'Uploadfile descr',             // description
    '0B4Isu853jI7edXEtNHBtQTNzNzA', // parent directory ID
    array('ocr'=>true)              // additional params; 
);