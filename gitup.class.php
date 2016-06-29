<?php

/**
 * post files to server
 * update/remove code files to server using php and git when you have no shell rights
 *
 * configuration: (all STRING)
 *      ACCESSTOKEN_VALUE   => the value of accesstoken before encryption
 *      ACCESSTOKEN_KEY     => the key of post array
 *      MODE                => work mode : post / accept (local / server)
 *      REP_ROOT            => the root path of local repository
 *      SERVER_ROOT         => the root path of website on server
 *      PUSH_URL            => the target of posting data
 *      POSTDATA_PATH       => file path for saving data(local)
 *      POSTDATA_FILENAME   => file name
 *      ACCEPTDATA_PATH     => file path for saving data(server)
 *      ACCEPTDATA_FILENAME => file name
 *      UPDATE_TYPE         => git command type : AMD / ALL (only can use AMD type now)
 *      PRINTOUT            => used like BOOLEAN, print out messages directly
 */

class Gitup{

    /**
     * full path with filename
     * @var string
     */
    private $gitupDataFile;

    /**
     * unserialize data
     * @var array
     */
    private $gitupData;

    /**
     * the result of verification
     * @var boolean
     */
    private $passChecking = false;

    /**
     * default setting
     * @var array
     */
    private $config = array(
        'ACCESSTOKEN_VALUE' => 'git-up',
        'ACCESSTOKEN_KEY' => 'accesstoken',
        'MODE' => 'POST',
        'REP_ROOT' => __DIR__,
        'SERVER_ROOT' => __DIR__,
        'PUSH_URL' => 'localhost',
        'POSTDATA_PATH' => __DIR__,
        'POSTDATA_FILENAME' => 'gitup-post.data',
        'ACCEPTDATA_PATH' => __DIR__,
        'ACCEPTDATA_FILENAME' => 'gitup-accept.data',
        'UPDATE_TYPE' => 'AMD',
        'PRINTOUT' => true,
    );

    /**
     * set configuration when instantiating the object
     *
     * @access public
     * @param array|null $config
     */
    public function __construct($config = null){
        if(is_array($config)){
            foreach($config as $key => $val){
                $upperKey = strtoupper($key);
                if(isset($this->config[$upperKey])){
                    $this->config[$upperKey] = $val;
                }
            }
        }
    }

    /**
     * set configuration or get configuration
     *
     * @access public
     * @param string $key
     * @param string $val  if $val === null return configuration else set configuration
     * @return string|boolean
     */
    public function config($key, $val = null){
        if(isset($this->config[$key])){
            if(is_null($val)){
                return $this->config[$key];
            }else{
                $this->config[$key] = $val;
                return true;
            }
        }else{
            return false;
        }
    }

    /**
     * check directory and initiate data
     * must run this function first
     *
     * @access public
     * @return array
     */
    public function init(){
        $this->formatDir();
        if(($this->config['MODE'] = strtoupper($this->config['MODE'])) == 'POST'){
            is_dir($this->config['REP_ROOT']) or $this->throwException('No Such Directory: ' . $this->config['REP_ROOT']);
            is_dir($this->config['POSTDATA_PATH']) or $this->throwException('No Such Directory: ' . $this->config['POSTDATA_PATH']);
        }else{
            $this->config['MODE'] = 'ACCEPT';
            is_dir($this->config['SERVER_ROOT']) or $this->throwException('No Such Directory: ' . $this->config['SERVER_ROOT']);
            is_dir($this->config['ACCEPTDATA_PATH']) or $this->throwException('No Such Directory: ' . $this->config['SERVER_ROOT']);
        }
        $this->gitupDataFile = $this->config[$this->config['MODE'] . 'DATA_PATH'] . $this->config[$this->config['MODE'] . 'DATA_FILENAME'];
        $this->gitupData = $this->readData() ?: $this->initData();
        return $this->gitupData;
    }

    /**
     * main function, checking and updating
     *
     * @access public
     * @return string ('lastest' / 'again' / 'finished')
     */
    public function run(){
        $this->gitupData or $this->throwException('gitup data error, must run Gitup::init() first.');
        if($this->config['MODE'] == 'POST'){
            $postData = array('action' => 'query');
            $postResult = $this->getResult($this->postToServer($postData)) or $this->throwException('server is not available.');

            if($postResult['status'] == 'ready'){
                if($postResult['version'] == $this->gitupData[0]){
                    if($this->getGitHash() == $this->gitupData){
                        //print 'The files on server have been updated to lastest.';
                        return 'lastest';
                    }else{
                        $this->writeData($this->getGitHash());
                        //print 'The local files have been updated, run again.';
                        return 'again';
                    }
                }else{
                    if($postResult['version'] == '0'){
                        $files = $this->getAllFiles();
                    }else{
                        in_array($postResult['version'], $this->gitupData) or $this->throwException('the version on server is not in the repository!');
                        $files = $this->getCommitFiles($postResult['version'],$this->gitupData[0]);
                    }
                    $listData = array('action' => 'sendlist', 'target' => $this->gitupData[0], 'files' => $this->getSendList($files));
                    $listResult = $this->getResult($this->postToServer($listData));

                    if($listResult['status'] == 'update'){
                        return $this->update($listResult['next']);
                    }else{
                        $this->throwException('unknown callback.');
                    }
                }
            }elseif($postResult['status'] == 'update'){
                $this->update($postResult['next']);
            }elseif($postResult['status'] == 'finish'){
                //print 'update finished';
                return 'finished';
            }

        }else{
            $this->passChecking or $this->throwException('The access token error or did not verify the access token.');
            switch($_POST['action']){
            case 'query':
                $callback = array('status' => $this->gitupData['status'], 'version' => $this->gitupData['version'], 'target' => $this->gitupData['target']);
                if($callback['status'] == 'update'){
                    $callback['next'] = $this->getNextFile();
                    $callback['next'] or $this->clear();
                }
                $this->sendResult($callback);
            break;

            case 'sendlist':
                if(!empty($_POST['target']) && !empty($_POST['files'])){
                    $this->gitupData['status'] = 'update';
                    $this->gitupData['target'] = $_POST['target'];
                    $this->gitupData['files'] = $_POST['files'];
                    $this->writeData($this->gitupData);
                    $callback = array('status' => 'update', 'next' => $this->getNextFile());
                    $callback['next'] or $this->clear();
                    $this->sendResult($callback);
                }else{
                    $this->throwException('send list error');
                }
            break;

            case 'sendfile':
                if(in_array(md5($_POST['file']), $this->gitupData['files']['AM'])){
                    if($_FILES['upload_file']['error'])$this->throwException('upload file error');
                    $this->update($_POST['file']);
                }else{
                    $this->throwException('file does not in list');
                }
            break;

            default:
                $this->throwException('undefined action!');
            break;
            }
        }
    }

    /**
     * post/receive files
     *
     * @access public
     * @param string $file  path and name
     * @param boolean $loop
     * @return string|boolean
     */
    public function update($file, $loop = true){
        $this->gitupData or $this->throwException('gitup data error, must run Gitup::init() first.');
        if($this->config['MODE'] == 'POST'){
            $fullPathFile = $this->config['REP_ROOT'] . $file;
            if(file_exists($fullPathFile)){
                $postData = array('action' => 'sendfile', 'file' => $file);
                $postResult = $this->getResult($this->postToServer($postData,$fullPathFile));

                if($postResult['result'] == '1'){
                    if($loop){
                        if(!empty($postResult['next'])){
                            return $this->update($postResult['next']);
                        }else{
                            return true;
                        }
                    }else{
                        return true;
                    }
                }elseif($postResult['result'] == '2'){
                    return 'finished';
                }else{
                    $this->throwException("update error: {$postResult['msg']}");
                }
            }else{
                $this->throwException("FILE: {$fullPathFile} missing!");
            }
        }else{
            $md5 = md5($file);
            $fileData = $this->gitupData['files'][$md5];
            if(!$this->moveFile($fileData['file'],$fileData['type']))$this->throwException('can not move file on server.');
            $nextFile = $this->getNextFile($md5);
            $nextFile or $this->clear();
            $callback = array('status' => 'update', 'result' => 1, 'next' => $nextFile);
            $this->sendResult($callback);
        }
    }

    /**
     * verify access token
     *
     * @access public
     * @param string|null $key
     * @param string|null $value
     * @return boolean
     */
    public function checkAccessToken($key = null, $value = null){
        if(is_null($key) || is_null($value)){
            $key = $this->config['ACCESSTOKEN_KEY'];
            $value = $this->config['ACCESSTOKEN_VALUE'];
        }
        if(isset($_POST[$key])){
            $this->passChecking = $_POST[$key] == sha1($value);
            return $this->passChecking;
        }else{
            return false;
        }
    }

    /**
     * print out message directly
     *
     * @access public
     * @param string $msg
     * @param string $wrap
     * @return void
     */
    public function printOut($msg, $wrap = '<br>'){
        print $msg . $wrap;
        ob_flush();
        flush();
    }

    /**
     * clear and update data
     *
     * @access protected
     * @return void
     */
    protected function clear(){
        if($this->config['MODE'] == 'ACCEPT'){
            if(isset($this->gitupData['files']['D'])){
                foreach($this->gitupData['files']['D'] as $val){
                    if(!unlink($this->config['SERVER_ROOT'] . $val))$this->throwException("delete file: {$val} failed.");
                }
            }
            $this->gitupData = array(
                'status' => 'ready', 
                'version' => $this->gitupData['target'],
                'result' => 2,
                'target' => '',
                'files' => array()
            );
            $this->writeData($this->gitupData);
            $this->sendResult($this->gitupData);
            exit;
        }else{
            $this->throwException('run Gitup::clear() error: wrong mode.');
        }
    }

    /**
     * move the temporary file
     *
     * @access protected
     * @param string $file  path and name
     * @param string $type  'A' or 'M'
     * @return boolean
     */
    protected function moveFile($file,$type){
        $fullPathFile = $this->config['SERVER_ROOT'] . $file;
        if($type == 'M'){
            if(!file_exists($fullPathFile))$this->throwException("modify FILE: {$file} error, missing on server.");
        }else{
            preg_match('/.+\//',$fullPathFile,$pregArr);
            $fullPath = $pregArr[0] or $this->throwException('get full path error!');
            if(!is_dir($fullPath)){
                @mkdir($fullPath,0777,true);
                if(!is_dir($fullPath))$this->throwException("create path: {$fullPath} failed");
            }
        }
        return move_uploaded_file($_FILES['upload_file']['tmp_name'],$fullPathFile);
    }

    /**
     * read unserialized data
     *
     * @access protected
     * @return array|boolean
     */
    protected function readData(){
        if(file_exists($this->gitupDataFile)){
            ($data = @file_get_contents($this->gitupDataFile)) or $this->throwException('can not read gitup file or the file is empty.');
            return unserialize($data);
        }else{
            return false;
        }
    }

    /**
     * write serialized data
     *
     * @access protected
     * @return integer
     */
    protected function writeData($data){
        $data = serialize($data);
        return @file_put_contents($this->gitupDataFile, $data);
    }

    /**
     * format directory path
     *
     * @access protected
     * @param string
     * @return boolean
     */
    protected function formatDir($key = null){
        $dirParameter = array(
        'REP_ROOT',
        'SERVER_ROOT',
        'POSTDATA_PATH',
        'ACCEPTDATA_PATH',
        );
        if(is_null($key)){
            foreach($dirParameter as $val){
                $this->config[$val] = rtrim($this->config[$val], '/') . '/';
            }
            return true;
        }else{
            $key = strtoupper($key);
            if(in_array($key,$dirParameter)){
                $this->config[$key] = rtrim($this->config[$key], '/') . '/';
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * initiate data
     *
     * @access protected
     * @return array
     */
    protected function initData(){
        if($this->config['MODE'] == 'POST'){
            $data = $this->getGitHash() or $this->throwException("there is no git commit log in the {$this->config['REP_ROOT']}.");
        }else{
            $data = array('status' => 'ready', 'version' => '0', 'target' => '', 'files' => array());
        }
        $this->writeData($data) or $this->throwException('can not write gitup file');
        return $data;
    }

    /**
     * get GIT committed hash
     *
     * @access protected
     * @return array
     */
    protected function getGitHash(){
        exec("git -C {$this->config['REP_ROOT']} log --format=%H",$execResult);
        return $execResult;
    }

    /**
     * get GIT committed files
     * from earlier version to later version
     *
     * @access protected
     * @param string $from  hash
     * @param string|null $to  hash
     * @return array
     */
    protected function getGitFiles($from,$to = null){
        if($this->config['UPDATE_TYPE'] == 'AMD'){
            $gitDiffFilter = array('A','M','D');
            if(is_null($to)){
                foreach($gitDiffFilter as $val){
                    exec("git -C {$this->config['REP_ROOT']} log --name-only --format=%H --diff-filter={$val} {$from}",$execResult);
                    array_shift($execResult);
                    array_shift($execResult);
                    $gitFiles[$val] = $execResult;
                }
            }else{
                foreach($gitDiffFilter as $val){
                    exec("git -C {$this->config['REP_ROOT']} diff --name-only --diff-filter={$val} {$from} {$to}",$execResult[$val]);
                    $gitFiles[$val] = $execResult[$val];
                }
            }
        }else{
            if(is_null($to)){
                exec("git -C {$this->config['REP_ROOT']} log --name-status --format=%H {$from}",$execResult);
                $execResult = array_shift(array_shift($execResult));
            }else{
                exec("git -C {$this->config['REP_ROOT']} diff --name-status {$from} {$to}",$execResult);
            }
            foreach($execResult as $val){
                $logStr = preg_replace('/( )+/',' ',$val);
                $logArr = explode(' ',$logStr);
                $gitFiles[$logArr[0]][] = $logArr[1];
            }
        }
        return $gitFiles;
    }

    /**
     * get all added files
     * called when $callback['version'] == '0'
     *
     * @access protected
     * @return array
     */
    protected function getAllFiles(){
        $firstCommitFiles = $this->getGitFiles(end($this->gitupData));
        $otherCommitFiles = $this->getGitFiles(end($this->gitupData),$this->gitupData[0]);
        $allCommitFiles = array_merge_recursive($firstCommitFiles,$otherCommitFiles);
        if(!empty($allCommitFiles['D'])){
            $assocFiles = array_intersect($allCommitFiles['A'],$allCommitFiles['D']);
            $files['A'] = array_diff($assocFiles,$allCommitFiles['A']);
        }else{
            $files['A'] = $allCommitFiles['A'];
        }
        return $files;
    }

    /**
     * get files' list which would be posted to server or deleted from server
     *
     * @access protected
     * @param array $files  path and filename
     * @return array
     */
    protected function getSendList(array $files){
        if($this->config['UPDATE_TYPE'] == 'AMD'){
            foreach($files as $key => $val){
                foreach($val as $filename){
                    if($key == 'D'){
                        $sendList['D'][] = $filename;
                    }else{
                        $md5 = md5($filename);
                        $sendList['AM'][] = $md5;
                        $sendList[$md5] = array('file' => $filename, 'type' => $key);
                    }
                }
            }
            return $sendList;
        }else{
            $this->throwException('only can handle add,modify,delete now.');
        }
    }

    /**
     * get next file which should be posted to server
     *
     * @access protected
     * @param string $deleteHash  posted file's hash
     * @return string|boolean
     */
    protected function getNextFile($deleteHash = null){
        $this->config['MODE'] == 'ACCEPT' or $this->throwException('the mode should be [accept]');
        if(!is_null($deleteHash)){
            ($key = array_search($deleteHash, $this->gitupData['files']['AM'])) !== false or $this->throwException('no Hash value: ' . $deleteHash);
            unset($this->gitupData['files']['AM'][$key]);
            $this->writeData($this->gitupData);
        }
        if(count($this->gitupData['files']['AM']) > 0){
            $nextFileHash = end($this->gitupData['files']['AM']);
            return $this->gitupData['files'][$nextFileHash]['file'];
        }else{
            return false;
        }
    }

    /**
     * post data/file to server
     *
     * @access protected
     * @param array $data
     * @param string|null $file
     * @return string|boolean
     */
    protected function postToServer(array $data,$file = null){

        if($this->config['PRINTOUT']){
            $printOutMsg = "POSTINFO | Action: {$data['action']}";
            if($data['action'] == 'sendlist'){
                $countAM = isset($data['files']['AM']) ? count($data['files']['AM']) : 0;
                $countD = isset($data['files']['D']) ? count($data['files']['D']) : 0;
                $printOutMsg .= " , Count: {$countAM}[AM],{$countD}[D]";
            }elseif($data['action'] == 'sendfile'){
                $printOutMsg .= " , Filename: {$data['file']}";
            }
            $this->printOut($printOutMsg);
        }

        $data[$this->config['ACCESSTOKEN_KEY']] = sha1($this->config['ACCESSTOKEN_VALUE']);
        if(is_null($file)){
            $data = http_build_query($data);
        }else{
            $data['upload_file'] = new CURLFile($file);//required PHP version >= 5.5
        }
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $this->config['PUSH_URL'],
            CURLOPT_SAFE_UPLOAD => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        );
        curl_setopt_array($ch, $options);
        $curlResult = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode == '200' ? $curlResult : false;
    }

    /**
     * get result from server
     *
     * @access protected
     * @param string $callback
     * @return array
     */
    protected function getResult($callback){
        $callback = $callback ? json_decode($callback,true) : false;

        if($this->config['PRINTOUT']){
            if($callback){
                $printOutMsg = "POSTRESULT | Status: {$callback['status']}";
                if(isset($callback['version']) && isset($callbakc['target'])){
                    $printOutMsg .= " , Version: {$callback['version']} , Target: {$callback['target']}";
                }
                if(isset($callback['next'])){
                    $printOutMsg .= " , Nextfile: {$callback['next']}";
                }
                if(isset($callback['result'])){
                    switch($callback['result']){
                    case '2':
                        $msg = 'finished';
                        break;

                    case '1':
                        $msg = 'success';
                        break;

                    case '0':
                        $msg = 'failed';
                        break;

                    default:
                        $msg = 'unknown';
                        break;
                    }
                    $printOutMsg .= " , File update result: {$msg}";
                }
                if(isset($callback['errmsg'])){
                    $printOutMsg .= " , ErrorMsg: {$callback['errmsg']}";
                }
            }else{
                $printOutMsg = 'Status: error.';
            }
            $this->printOut($printOutMsg);
        }

        return $callback;
    }

    /**
     * send result to local
     *
     * @access protected
     * @param array $array
     * @return void
     */
    protected function sendResult($array){
        print json_encode($array);
    }

    /**
     * throw an exception
     *
     * @access protected
     * @param string $msg
     * @return void
     */
    protected function throwException($msg){
        throw new Exception($msg);
    }

}
