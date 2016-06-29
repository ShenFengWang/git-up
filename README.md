# git-up
Using php to update the program which created by git when you have no permission for ssh or ftp

##How to use
both local and server:
include 'gitup.class.php';
$gitup = new Gitup();
$gitup->init();
$gitup->run();

##Configuration
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
