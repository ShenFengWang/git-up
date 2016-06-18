<?php
//where to run 'git log'
define('GIT_DIR','/home/wang/doc/linux_setting');
//post target, absolute address
define('POST_LOCATION','');

$execDir = GIT_DIR ? : './';
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
