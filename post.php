<?php

//class file
define('CLASS_PATH','.');

include rtrim(CLASS_PATH, '/') . '/' . 'gitup.class.php';
$config = array(
    'rep_root' => '/home/wang/doc/linux_setting/',
    'push_path' => 'http://git-up/accept.php',
);
$gitup = new Gitup($config);

try{
    var_dump($gitup->checkServer());
}catch(Exception $e){
    print $e->getMessage();
}



?>
