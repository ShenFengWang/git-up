<?php
class Gitup{

    private $commitHash = array();

    private $config = array(
        'IDENTIFYCODE' => 'git-up',
        'MODE' => 'post',
        'REP_ROOT' => './',
        'PUSH_PATH' => 'localhost',
        'SERVER_ROOT' => __DIR__,
        'POSTDATA_PATH' => __DIR__,
        'ACCEPTDATA_PATH' => __DIR__,
    );

    function __construct($config = null){
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
        $this->config['IDENTIFYCODE'] = sha1($this->config['IDENTIFYCODE']);
        if($this->config['MODE'] == 'post'){
            if(file_exists($this->config['REP_ROOT'])){
                if(!$this->getGitLog())throw new Exception('there is no information using "git log",please check the repository path!');
            }else{
                throw new Exception('No Such Directory:' . $this->config['REP_ROOT']);
            }
        }else{
        
        }
    }

    protected function initDir($key = null){
        $dirParameter = array(
        'REP_ROOT',
        'SERVER_ROOT',
        'POSTDATA_PATH',
        'ACCEPTDATA_PATH',
        );
        if(is_null($key)){
            foreach($urlParameter as $val){
                $this->config[$val] = rtrim($this->config[$val], '/') . '/';
            }
            return true;
        }else{
            $key = strtoupper($key);
            if(in_array($key,$urlParameter)){
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
                preg_match('/^[a-z\d]{40}$/',$commitStr) and $this->commitHash[] = $commitStr;
            }
        }
        return count($this->commitHash) ? : false;
    }

    public function checkServer(){
        $postData = array('a' => 'a');
        return $this->curlToServer($postData);
    }

    private function curlToServer(array $data,$file = null){
        $ch = curl_init();
        is_null($file) or $data['upload_file'] = new CURLFile($file);//required PHP version >= 5.5
        $options = array(
            CURLOPT_URL => $this->config['PUSH_PATH'],
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

}
