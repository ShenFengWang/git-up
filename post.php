<?php

//class file
define('CLASS_PATH','');

include rtrim(CLASS_PATH, '/') . '/' . 'gitup.class.php';
$config = array(
    'rep_root' => '/home/wang/doc/linux_setting/',
);
$gitup = new Gitup();

try{

}catch(Exception $e){
    print $e->getMessage();
}

$execDir = REP_ROOT ? : './';
exec("git -C {$execDir} log",$execResult);
if(empty($execResult))exit("no log for git");

//handle result
foreach($execResult as $key => $val){
    if(strpos($val,'commit ') === 0){
        $gitCommitHash = explode(' ',$val)[1];
        preg_match('/^[a-z\d]{40}$/',$gitCommitHash) and $hashArray[] = $gitCommitHash;
    }
}
var_dump($hashArray);


?>
