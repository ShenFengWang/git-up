<?php
class Gitup{

    private $commitHash = array();
    private $dataFile;
    private $data;

    private $config = array(
        'ACCESSTOKEN_VALUE' => 'git-up',
        'ACCESSTOKEN_KEY' => 'accesstoken',
        'MODE' => 'POST',
        'REP_ROOT' => './',
        'PUSH_URL' => 'localhost',
        'SERVER_ROOT' => __DIR__,
        'POSTDATA_PATH' => __DIR__,
        'POSTDATA_FILENAME' => 'gitup-post.data',
        'ACCEPTDATA_PATH' => __DIR__,
        'ACCEPTDATA_FILENAME' => 'gitup-accept.data',
    );

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

    public function init(){
        $this->initDir();
        if(($this->config['MODE'] = strtoupper($this->config['MODE'])) == 'POST'){
            file_exists($this->config['REP_ROOT']) or $this->throwException('No Such Directory: ' . $this->config['REP_ROOT']);
            file_exists($this->config['POSTDATA_PATH']) or $this->throwException('No Such Directory: ' . $this->config['POSTDATA_PATH']);
            if(!$this->readData()){
                $this->getGitLog() or $this->throwException('there is no information using "git log",please check the repository path!');
            }
        }else{
            $this->config['MODE'] = 'ACCEPT';
            file_exists($this->config['SERVER_ROOT']) or $this->throwException('No Such Directory: ' . $this->config['SERVER_ROOT']);
            file_exists($this->config['ACCEPTDATA_PATH']) or $this->throwException('No Such Directory: ' . $this->config['SERVER_ROOT']);
        }
        $this->dataFile = $this->config[$this->config['MODE'] . 'DATA_PATH'] . $this->config[$this->config['MODE'] . 'DATA_FILENAME'];
    }

    public function checkAccessToken(){
        if(isset($_POST[$this->config['ACCESSTOKEN_KEY']])){
            return $_POST[$this->config['ACCESSTOKEN_KEY']] == sha1($this->config['ACCESSTOKEN_VALUE']);
        }else{
            return false;
        }
    }

    protected function readData(){
        if(file_exists($this->dataFile) && ($data = @file_get_contents($this->dataFile))){
            return $this->data = unserialize($data);
        }else{
            return false;
        }
    }

    protected function writeData($data){
        $data = serialize($data);
        return @file_put_contents($this->dataFile, $data);
    }

    protected function initDir($key = null){
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

    protected function getGitLog(){
        exec("git -C {$this->config['REP_ROOT']} log",$execResult);
        if(empty($execResult))return false;
        foreach($execResult as $key => $val){
            if(strpos($val,'commit ') === 0){
                $commitStr = explode(' ',$val)[1];
                preg_match('/^[a-fA-F\d]{40}$/',$commitStr) and $this->commitHash[] = $commitStr;
            }
        }
        return count($this->commitHash) ? : false;
    }

    protected function checkServer(){
        $postData = array('a' => 'a');
        return $this->postToServer($postData);
    }

    protected function postToServer(array $data,$file = null){
        is_null($file) or $data['upload_file'] = new CURLFile($file);//required PHP version >= 5.5
        $data[$this->config['ACCESSTOKEN_KEY']] = sha1($this->config['ACCESSTOKEN_VALUE']);
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

    protected function throwException($msg){
        throw new Exception($msg);
    }

}
