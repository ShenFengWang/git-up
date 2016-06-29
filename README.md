# git-up
Using php and git to updating files to server when you have no permission for using ssh or ftp

##How to use
both local and server:
<p>include 'gitup.class.php';</p>
<p>$gitup = new Gitup();</p>
<p>$gitup->init();</p>
<p>$gitup->run();</p>

##Configuration
     ACCESSTOKEN_VALUE   => the value of accesstoken before encryption
     ACCESSTOKEN_KEY     => the key of post array
     MODE                => work mode : post / accept (local / server)
     REP_ROOT            => the root path of local repository
     SERVER_ROOT         => the root path of website on server
     PUSH_URL            => the target of posting data
     POSTDATA_PATH       => file path for saving data(local)
     POSTDATA_FILENAME   => file name
     ACCEPTDATA_PATH     => file path for saving data(server)
     ACCEPTDATA_FILENAME => file name
     UPDATE_TYPE         => git command type : AMD / ALL (only can use AMD type now)
     PRINTOUT            => used like BOOLEAN, print out messages directly
