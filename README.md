# Google APIs Client Library for PHP #

## Description ##
The Google API Client Library. GDrive.php content Google_Drive_Adapter which build some needed functions to work with Google Drive, like upload file, download file, list files in folder and etc.


## Requirements ##
* [PHP 5.2.1 or higher](http://www.php.net/)
* [PHP JSON extension](http://php.net/manual/en/book.json.php)

*Note*: some features (service accounts and id token verification) require PHP 5.3.0 and above due to cryptographic algorithm requirements. 

## Developer Documentation ##
http://developers.google.com/api-client-library/php

## Installation ##

To start work with Library just add GDrive.php file set your Auth. information build your service and .....

## Basic Example ##
See the examples/ directory for examples of the key client features.
```PHP
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


```