<?php
/******
 *  File Name 		= db_details.php 
 *  Purpose   		= Set Database credentials ENV specific
 *  APPLICATION_ENV     = Define in .htaccess 
 * 
*/

/* Condition for Staging Database Credentials */
DEFINE('DB_HOST','localhost');
DEFINE('DB_NAME','ussd');
DEFINE('DB_USER','root');
DEFINE('DB_PASS','admin');
?>
