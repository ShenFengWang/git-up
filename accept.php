<?php
if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){
    define('CLASS_PATH','.');

    include rtrim(CLASS_PATH, '/') . '/' . 'gitup.class.php';

    $config = array(
        'mode' => 'accept',
        'server_root' => './test',
    );
    $gitup = new Gitup($config);
    if($gitup->checkAccessToken()){
        try{
            $gitup->init();
            $gitup->run();
        }catch(Exception $e){
            $callback = array('status' => 'error', 'errmsg' => $e->getMessage());
            echo json_encode($callback);
        }
    }else{
        header('HTTP/1.1 404 Not Found');
    }
}else{
    header('HTTP/1.1 404 Not Found');
}
?>
