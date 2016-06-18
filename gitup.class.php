<?php
class Gitup{

    private $config = array(
    'REP_ROOT' => './',
    );

    function __construct($config){
        if(is_array($config)){
            foreach($config as $key => $val){
                $upperKey = strtoupper($key);
                if(isset($this->config[$upperKey])){
                    $this->config[$upperKey] = $val;
                }
            }
        }
    }

}
