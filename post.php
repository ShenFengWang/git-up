<?php

//class file path
define('CLASS_PATH','.');

include rtrim(CLASS_PATH, '/') . '/' . 'gitup.class.php';

$config = array(
    'rep_root' => '/home/wang/doc/linux_setting/',
    'push_url' => 'http://git-up/accept.php',
);
$gitup = new Gitup($config);

try{
    $gitup->init();
    $gitup->run();
}catch(Exception $e){
    print $e->getMessage();
}



?>
