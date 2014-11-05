<?php
/**
 * Created by PhpStorm.
 * User: YORDAN NIKOLOV
 * email: monnydesign@hotmail.com
 *
 * Date: 14-10-31
 * Time: 11:59
 */

class Google_Drive_Adapter {
    protected $client_id;
    protected $client_email;
    protected $api_key;
    protected $p12_key_file;
    protected $scope;
    protected $privateKeyPassword = 'notasecret';
    protected $assertionType = 'http://oauth.net/grant_type/jwt/1.0/bearer';
    protected $sub = false;
    protected $useCache = true;

    protected $auth;
    protected $client;
    protected $driveService;

    /**
     * Class constructor
     *
     * @param Array $options with keys
     *      @key client_id
     *      @key client_email
     *      @key scope array List of scopes
     *      @key key_file privateKey file
     *
     */

    public function __construct(Array $options) {
        if( !$options['client_id']      ||
            !$options['client_email']   ||
            !$options['scope']          ||
            !$options['key_file'] )
        {
            die('Some keys are empty!');
        }

        if(! is_array($options['scope']) ) {
            $options['scope'] = array($options['scope']);
        }

        if( ! session_id()) {
            session_start();
        }

        $this->setClientId($options['client_id'])
            ->setClientEmail($options['client_email'])
            ->setScope($options['scope'])
            ->setKeyFile($options['key_file']);
    }

    public function auth() {
        if(!$this->auth)
            $this->auth = new Google_Auth_AssertionCredentials(
                $this->client_email,
                $this->scope,
                $this->getKeyFileData(),
                $this->privateKeyPassword,
                $this->assertionType,
                $this->sub,
                $this->useCache
            );

        return $this;
    }

    public function getAuth() {
        return $this->auth;
    }

    public function apiClient() {
        if(!$this->client)
            $this->client = new Google_Client();

        $this->client->setClientId($this->client_id);
        $this->client->setAccessType('service_account');
        $this->client->setAssertionCredentials($this->auth);

        if( $this->api_key )
            $this->client->setDeveloperKey($this->api_key);

        return $this;
    }

    public function getClient() {
        return $this->client;
    }

    public function buildDriveService() {
        if(!$this->driveService)
            $this->driveService = new Google_Service_Drive($this->client);

        return $this;
    }

    public function setClientId($clientId) {
        $this->client_id = $clientId;
        return $this;
    }

    public function setClientEmail($clientEmail) {
        $this->client_email = $clientEmail;
        return $this;
    }

    public function setApiKey($apiKey) {
        $this->api_key = $apiKey;
        return $this;
    }

    public function setPrivateKeyPassword($password) {
        $this->privateKeyPassword = $password;
        return $this;
    }

    public function setAssertionType($assertionType) {
        $this->assertionType = $assertionType;
        return $this;
    }

    public function setSub($sub) {
        $this->sub = $sub;
        return $this;
    }

    public function setaSub($useCache) {
        $this->useCache = $useCache;
        return $this;
    }

    public function setKeyFile($keyFile) {
        $this->p12_key_file = $keyFile;
        return $this;
    }

    public function setScope(Array $scope) {
        $this->scope = $scope;
        return $this;
    }

    /**
     * Set the parent folder for Drive File
     *
     * @param String $parentId id of the parent folder
     * @return Google_Service_Drive_ParentReference
     */
    public function setParentFolder($parentId)
    {
        $parent = new Google_Service_Drive_ParentReference();

        if ($parentId != null) {
            $parent->setId($parentId);
        }

        return $parent;
    }

    /**
     * Insert new file in the Application Data folder.
     *
     * @param string $filename Filename of the file to insert.
     * @param string $title Title of the file to insert, including the extension.
     * @param string $description Description of the file to insert.
     * @param string $parentId id of the parent folder
     * @return Google_DriveFile The file that was inserted. NULL is returned if an API error occurred.
     */
    public function insertFileInFolder($filename, $title, $description, $parentId=null, $param=array()) {
        $file = new Google_Service_Drive_DriveFile();
        $file->setTitle($title);
        $file->setDescription($description);
        $mimeType = $this->getFileMimeType($filename);
        $file->setMimeType($mimeType[0]);

        $parent = $this->setParentFolder($parentId);
        $file->setParents(array($parent));

        try {
            $data = file_get_contents(realpath(dirname(__FILE__) . '/' . $filename));

            $createdFile = $this->driveService->files->insert($file,
                array_merge(
                    array(
                        'data' => $data,
                        'mimeType' => $mimeType[0],
                        'uploadType' => 'multipart'
                    ),
                    $param
                )
            );

            return $createdFile;
        } catch (Exception $e) {
            print "An error occurred: " . $e->getMessage();
        }
    }

    /**
     * Get files belonging to a folder.
     *
     * @param String $folderId ID of the folder to print files from.
     * @return Array of files ID in folder
     */
    function getFilesIdInFolder($folderId) {
        $pageToken = NULL;
        $result = array();
        do {
            try {
                $parameters = array();
                if ($pageToken) {
                    $parameters['pageToken'] = $pageToken;
                }
                $children = $this->driveService->children->listChildren($folderId, $parameters);

                foreach ($children->getItems() as $child) {
                    array_push($result, $child->getId());
                }
                $pageToken = $children->getNextPageToken();
            } catch (Exception $e) {
                print "An error occurred: " . $e->getMessage();
                $pageToken = NULL;
            }
        } while ($pageToken);

        return $result;
    }

    /**
     * Get a file's metadata.
     *
     * @param string $fileId ID of the file to print metadata for.
     * @return Array of file meta (title, description, mimeType)
     */
    function getFileMeta($fileId) {
        try {
            $file = $this->driveService->files->get($fileId);

            return array(
                'title' => $file->getTitle(),
                'description' => $file->getDescription(),
                'mimeType' => $file->getMimeType()
            );
        } catch (Exception $e) {
            print "An error occurred: " . $e->getMessage();
        }
    }

    /**
     * Check file/folder exists
     *
     * @param string $folderName.
     * @param string $parentId ID of the parent directory.
     * @return mixed ID of folder/file, FALSE if folder/file doesn't exists
     */
    function fileExists($folderName, $parentId) {
        try {
            $parameters = array(
                'q'=>"title='{$folderName}' and mimeType='application/vnd.google-apps.folder' AND trashed=false",
                'fields'=>'items'
            );

            $childs = $this->driveService->children->listChildren($parentId, $parameters);
            $items = $childs->getItems();

            return isset($items[0]['id']) ? $items[0]['id']:false;
        } catch (Exception $e) {
            print "An error occurred: " . $e->getMessage();
        }
    }


    /**
     * List all files contained in the Application Data folder.
     *
     * @param String $folderId ID of the folder to print files from.
     * @return Array List of Google_DriveFile resources.
     */
    function listFilesInApplicationDataFolder($folderId) {
        $result = array();
        $pageToken = NULL;

        do {
            try {
                $parameters = array();
                if ($pageToken) {
                    $parameters['pageToken'] = $pageToken;
                } else {
                    $parameters['q'] = "'{$folderId}' in parents";
                }
                $files = $this->driveService->files->listFiles($parameters);

                $result = array_merge($result, $files->getItems());
                $pageToken = $files->getNextPageToken();
            } catch (Exception $e) {
                print "An error occurred: " . $e->getMessage();
                $pageToken = NULL;
            }
        } while ($pageToken);
        return $result;
    }

    /**
     * Search for filename in the Application Data folder.
     *
     * @param filename of search file
     * @return Array List of Google_DriveFile resources.
     */
    function searchFilesInApplicationDataFolder($filename) {
        $result = array();
        $pageToken = NULL;

        do {
            try {
                $parameters = array();
                if ($pageToken) {
                    $parameters['pageToken'] = $pageToken;
                } else {
                    $parameters['q'] = "title contains '$filename'";
                }
                $files = $this->driveService->files->listFiles($parameters);

                $result = array_merge($result, $files->getItems());
                $pageToken = $files->getNextPageToken();
            } catch (Exception $e) {
                print "An error occurred: " . $e->getMessage();
                $pageToken = NULL;
            }
        } while ($pageToken);
        return $result;
    }

    /**
     * Download a file's content.
     *
     * @param file ID to download
     * @return String The file's content if successful, null otherwise.
     */
    function downloadFile($fileId) {

        $file = $this->driveService->files->get($fileId);
        $fileVars = get_object_vars($file);

        $downloadUrl = $file->getDownloadUrl();

        if ($downloadUrl) {
            $request = new Google_Http_Request($downloadUrl, 'GET', null, null);

            $SignhttpRequest = $this->getClient()->getAuth()->sign($request);
            $httpRequest = $this->getClient()->getIo()->makeRequest($SignhttpRequest);
            if ($httpRequest->getResponseHttpCode() == 200) {
                return $httpRequest->getResponseBody();
            } else {
                // An error occurred.
                return null;
            }
        } else {
            // The file doesn't have any content stored on Drive.
            return null;
        }
    }

    /**
     * Create new folder.
     *
     * @param string $folderName Title of the folder to create, including the extension.
     * @param string $description Description of the folder to insert.
     * @param string $parentId id of the parent folder
     * @return Google_DriveFile The file that was inserted. NULL is returned if an API error occurred.
     */
    public function createFolder($folderName, $description, $parentId=null) {
        $file = new Google_Service_Drive_DriveFile();
        $file->setTitle($folderName);
        $file->setDescription($description);
        $mimeType = 'application/vnd.google-apps.folder';
        $file->setMimeType($mimeType);

        $parent = $this->setParentFolder($parentId);
        $file->setParents(array($parent));

        try {

            $createdFile = $this->driveService->files->insert($file, array(
                'data' => null,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart'
            ));

            return $createdFile;
        } catch (Exception $e) {
            print "An error occurred: " . $e->getMessage();
        }
    }

    /**
     * Create path to the file.
     *
     * @param string $filePath path to the file
     * @param string $parentId id of the parent folder
     * @return string the ID of the last created folder in the path
     */
    public function createFilePath($filePath, $parentId)
    {
        $path_array = explode("/", dirname($filePath));

        foreach($path_array as $folder)
        {
            $returnId = $this->itemExists($folder, $parentId);

            if($returnId!==false)
            {
                $parentId = $returnId;
                continue;
            }
            else
            {
                $createResult = $this->createFolder($folder, null, $parentId);
                $parentId = $createResult['id'];
            }
        }

        return $parentId;
    }

    private function getKeyFileData()
    {
        $file_path = realpath(dirname(__FILE__) . '/' . $this->p12_key_file);
        if(!file_exists($file_path))
            throw new Exception("P12 file doesn't exists!");

        return file_get_contents( $file_path );
    }

    private function getFileMimeType($file)
    {
        $fi = new finfo( FILEINFO_MIME );
        $filePath = realpath(dirname(__FILE__) . '/' . $file);
        $mimeType = explode( ';', $fi->buffer(file_get_contents($filePath)));

        return $mimeType;
    }
}

require_once realpath(dirname(__FILE__) . '/../autoload.php');
