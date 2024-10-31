<?php
/*
  Plugin Name: RackForms Express
  Plugin URI: https://www.rackforms.com/rackforms-express-for-wordpress.php
  Description: Install and Manage RackForms Express, A Powerful and Totally Free Form Builder For WordPress!
  Version: 1.5
  Author: nicSoft
  Author URI: https://www.rackforms.com
 */

// Note we can never have open/close PHP with space, as this 
wp_deregister_script('jquery');

function rackforms_enqueue($hook) {
    if( 'toplevel_page_rackforms_express_options' != $hook )
        return;
    wp_enqueue_script( 'resize_iframe', plugins_url( '/js/resize-editor-iframe.js', __FILE__ ) );
    
    wp_register_style( 'rackforms_admin_style', plugins_url( '/css/rackforms-style.css', __FILE__ ), false, '1.0.0' );
    wp_enqueue_style( 'rackforms_admin_style', plugins_url( '/css/rackforms-style.css', __FILE__ ) );
    
}

function rackforms_admin_theme($hook){
	if( 'toplevel_page_rackforms_express_options' != $hook )
		return;
	// http://codex.wordpress.org/Creating_Admin_Themes
	wp_register_style( 'rackforms_admin_style', plugins_url( '/css/rackforms-stye.css', __FILE__ ), false, '1.0.0' );
	wp_enqueue_style( 'rackforms_admin_style', plugins_url( '/css/rackforms-stye.css', __FILE__ ) );
}


define('_RACKFORMS_INSTALL_NAME_', 'express-for-wordpress'); // do not include .zip in name.

class SaneDb
{
    private $_oDb;

    public function __construct(wpdb $oDb)
    {
        $this->_oDb = $oDb;
    }

    public function __get($sField)
    {
        if($sField != '_oDb')
            return $this->_oDb->$sField;
    }

    public function __set($sField, $mValue)
    {
        if($sField != '_oDb')
            $this->_oDb->$sField = $mValue;
    }

    public function __call($sMethod, array $aArgs)
    {
        return call_user_func_array(array($this->_oDb, $sMethod), $aArgs);
    }

    public function getDbName() { return $this->_oDb->dbname;     }
    public function getDbUser() { return $this->_oDb->dbuser;     }
    public function getDbPass() { return $this->_oDb->dbpassword; }
    public function getDbHost() { return $this->_oDb->dbhost;     }
}

global $pw_password_salt;

function randString($length, $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
{
	$str = '';
	$count = strlen($charset);
	while ($length--) {
		$str .= $charset[mt_rand(0, $count-1)];
	}
	return $str;
}

$pw_password_salt = randString(40);



//*********** for install/uninstall actions (optional) ********************//
/* register_activation_hook(__FILE__,'super_plugin_keithics_install');
  register_deactivation_hook(__FILE__, 'super_plugin_keithics_uninstall');
  function super_plugin_keithics_install(){
  super_plugin_keithics_uninstall();//force to uninstall option
  add_option("super_plugin_keithics_secret", generateRandom(10));
  }

  function super_plugin_keithics_uninstall(){
  if(get_option('super_plugin_keithics_secret')){
  delete_option("super_plugin_keithics_secret");
  }
  } */
//*********** end of install/uninstall actions (optional) ********************//

/**
 * https://codex.wordpress.org/Writing_a_Plugin
 * http://wordpress.org/plugins/super-plugin-skeleton/
 * https://codex.wordpress.org/Creating_Tables_with_Plugins
 * http://stackoverflow.com/questions/5144893/getting-wordpress-database-name-username-password-with-php
 */


add_action('admin_menu', 'rackforms_express_menu');


function rackforms_express_menu() {
    $pending = '<span class="update-plugins"><span class="pending-count">7</span></span>';
    $pending = "";
    add_menu_page('RackForms', 'RackForms' . $pending, 'manage_options', 'rackforms_express_options', 'rackforms_express_options');
    //add_submenu_page( 'super_plugin_keithics', 'Super Plugin', 'Sub Menu', 'manage_options', 'super_plugin_unique_url', 'super_plugin_unique_url');
    //add_submenu_page( 'super_plugin_keithics', 'Super Plugin', 'Another Menu', 'manage_options', 'super_plugin_unique_url2', 'super_plugin_unique_url2');
}

function super_plugin_unique_url() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    echo '<div class="wrap">';
    echo '<h2>This is the Sub Menu</h2>';
    echo '<p>Include PHP file for better readability of your code.</p>';
    echo '</div>';
}

function super_plugin_unique_url2() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    echo '<div class="wrap">';
    echo '<h2>This is the Second Sub Menu</h2>';
    echo '<p>Include PHP file for better readability of your code.</p>';
    echo '</div>';
}



/**
 * Core Database Logic.
 * 
 * @param unknown $db_vendor
 * @param unknown $db_mysql_socket
 * @param unknown $db_hostname
 * @param unknown $db_catalog
 * @param unknown $db_username
 * @param unknown $db_password
 * @param unknown $sql
 * @param unknown $db_mysql_port
 * @param string $multi
 * @return void|string
 */
function execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port, $multi = false){
	$e_message = '';

	if($db_vendor == 'mysql'){
		$dsn = $db_vendor . ":unix_socket=" . $db_mysql_socket . ";host=" . $db_hostname . ";dbname=" . $db_catalog . ";port={$db_mysql_port};";

		try{
			$conn = new PDO ( $dsn, $db_username, $db_password );
		} catch(PDOException $e){
			$e_message = "Error 244: " .  $e->getMessage () . "\n";
			return;
		}

		$e_message = '';

		try {
			$sth = $conn->prepare ( $sql );

			if ($conn->errorCode () != '00000') {
				if (DEBUG) {
					$e_message = "Database Error 255: " . implode ( ': ', $conn->errorInfo () ) . "\n";
					//die ( "Database Error 181: " . implode ( ': ', $conn->errorInfo () ) . "\n" );
				}
			}
			$sth->execute ();
		} catch ( PDOException $e ) {
			echo $e;
		}

		if ($conn->errorCode () != '00000') {
			if (DEBUG) {
				$e_message = "Error 266: " . implode ( ': ', $conn->errorInfo () ) . "\n";
				//die ( "Error 191: " . implode ( ': ', $conn->errorInfo () ) . "\n" );
			}
		}
		// statement errors
		if ($sth->errorCode () != '00000') {
			if (DEBUG) {
				$e_message = "Error 273: " . implode ( ': ', $sth->errorInfo () ) . "\n";
				//die ( "Error 197: " . implode ( ': ', $sth->errorInfo () ) . "\n" );
			}
		}
	}

	if($db_vendor == 'mysqli'){

		// connect
		$dbh = new mysqli($db_hostname, $db_username, $db_password, $db_catalog);
		if ($dbh->connect_error) {
			$e_message = 'Error 284: (' . $dbh->connect_errno . ') ' . $dbh->connect_error;
		}

		// MySQLi cannot have multiple queries in one statement unless we use multi_query()
		if($multi){
				
			$result = $dbh->multi_query($sql);
				
			// check for errors
			if($dbh->errno){
				$e_message = 'Error 303: ' . $dbh->errno . ": " . $dbh->error;
			}
				
			$dbh->close();
				
		} else {
				
			// prepare statement
			$stmt = $dbh->prepare($sql);
				
			// check for statement errors
			if($dbh->errno <> 0){
				die('SQL ERROR 315: ' . $dbh->errno . ": " . $dbh->error);
			}
				
			$stmt->execute();
				
			// check for errors
			if($dbh->errno){
				$e_message = 'Error 322: ' . $dbh->errno . ": " . $dbh->error;
			}

			$stmt->close();
			$dbh->close();
		}

	}

	return $e_message;
}


/**
 * Uninstall Process
 */

register_uninstall_hook(__FILE__, '_uninstall_rackforms');

function recurseRmdir($dir) {
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? recurseRmdir("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}

function _uninstall_rackforms(){

	
	recurseRmdir(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . _RACKFORMS_INSTALL_NAME_);
	
	
	// database

	global $sanedb;
	global $wpdb;
	$sanedb = new SaneDb($wpdb);
	
	$dbname = $sanedb->getDbName();
	$dbuser = $sanedb->getDbUser();
	$dbhost = $sanedb->getDbHost();
	$dbpassword = $sanedb->getDbPass();
	
	$db_vendor = 'mysqli';
	$db_mysql_socket = '';
	
	$db_hostname = $dbhost;
	$db_catalog = $dbname;
	$db_username = $dbuser;
	$db_password = $dbpassword;
	$db_mysql_port = '3306';
	
	
	$error = 0;
	$installed_error = 0;
	
	$e_message = "";
	
	$sql = <<<EOT
DROP TABLE IF EXISTS fb_admin, fb_job_entries, fb_jobs, fb_demo, fb_images, fb_auth, fb_files, fb_comments;
EOT;
	$val = execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port);
	if($val != ''){
		$error = 1;
		$e_message .= $val . '<br />';
	}
	
	
}


// TEST
//_uninstall_rackforms();


/**
 * Install Process
 */

function _install_create_app_config($dbhost, $dbname, $dbpassword, $dbuser){
    
    $error = 0;
    
    global $pw_password_salt;
    
    $ac_timezone = get_option('timezone_string');
    
    if($ac_timezone == ""){
        $ac_timezone = 'America/Chicago';
    }
    
    // main config file
	$config_data = <<<EOT
<?php
\$app_version = '1.0';

// db
define('DB_TYPE', 'mysqli');
define('DB_HOST', '{$dbhost}');
define('MYSQL_SOCKET', ''); // E.G. /tmp/mysql.sock or /var/run/mysqld/mysqld.sock
define('MYSQL_PORT', '3306'); // E.G. 3306 || This can be blank in most cases
define('DB_USER', '{$dbuser}');
define('DB_PASS', '{$dbpassword}');
define('DB_CATALOG', '{$dbname}');

// Build 612 - Lets us define if our MySQL server allows for ESCAPE CHARACTERS
// Leaving as 0 means your job code has all back slashes escaped, this the default for MySQL.
// Setting to 1 means your MySQL server has an sql_mode of NO_BACKSLASH_ESCAPES, or you are running MSSQL.
define('NO_BACKSLASH_ESCAPES', '0');

// Procedures
define('EXECUTE_STRING', 'CALL'); // 'EXECUTE ' for MSSQL :: 'CALL ' for MySQL
define('USE_PROCEDURES', '0'); // 0 or 1

define('SALT', '{$pw_password_salt}');

// app
define('DEBUG', '0');
define('DEBUG_DETAIL', '0');

define('USER_MODE', 'DATABASE'); // DATABASE or FLATFILE <- DEPRICIATED DO NOT CHANGE FROM DATABASE

// need to change your timezone? http://us2.php.net/timezones
define('TIMEZONE', '{$ac_timezone}');

// Build 640 - Compatibility Change
if(function_exists('date_default_timezone_set')) { 
	date_default_timezone_set(TIMEZONE); 
} else {
	ini_set('date.timezone', TIMEZONE);
}

// Build 624 - Set Directory Write Permission Level - Not Needed On Windows
define('DIRECTORY_MOD', 0755); // Octal based UNIX permission level e.g. 0755, 0777

// Build 624 - Set File Write Permission Level - Not Needed On Windows
define('FILE_MOD', 0644); // Octal based UNIX permission level e.g. 0644, 0664

// Build 633
define('ENCODE_PW', 1);

// UPDATES
// 638 :: By Default
?>
EOT;

    if (!file_put_contents ( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . _RACKFORMS_INSTALL_NAME_ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config.php', $config_data )) {
        $error = 1;
        $e_message = 'Could not write to app/config.php. Check permissions and try again!';
    }
    
    if($error != 0){
        return array(false, $e_message);
    } else {
        return array(true, '');
    }

}


function _install_create_movefiles_config($dbhost, $dbname, $dbpassword, $dbuser){
    
    $error = 0;

    $ac_timezone = get_option('timezone_string');
    
    if($ac_timezone == ""){
        $ac_timezone = 'America/Chicago';
    }
    
    // move files config	
	$config_move_data = <<<EOT
<?php
// constants
\$app_version = '1.0';

// Change to 1 to see debug info if you run into problems executing your query 
// (may need to look at html page source to see error).
if(!isset(\$debug)) { \$debug = 0; }

\$db_type = 'mysqli';
\$db_host = '{$dbhost}';
\$mysql_socket = ''; // E.G. /tmp/mysql.sock or /var/run/mysqld/mysqld.sock
\$mysql_port = '3306'; // E.G. 3306 || This can be blank in most cases
\$use_procedures = '0'; // 0 or 1
\$db_user = '{$dbuser}';
\$db_pass = '{$dbpassword}';
\$db_catalog = '{$dbname}';

// need to change your timezone? http://us2.php.net/timezones
if(!defined('TIMEZONE')) { define('TIMEZONE', '{$ac_timezone}'); }

// Build 640 - Compatibility Change
if(function_exists('date_default_timezone_set')) { 
	date_default_timezone_set(TIMEZONE); 
} else {
	ini_set('date.timezone', TIMEZONE);
}

// Build 624 - Set Directory Write Permission Level - Not Needed On Windows
if(!defined('DIRECTORY_MOD')) { define('DIRECTORY_MOD', 0755); } // Octal based UNIX permission level e.g. 0755, 0777

// Build 624 - Set File Write Permission Level - Not Needed On Windows
if(!defined('FILE_MOD')) { define('FILE_MOD', 0644); } // Octal based UNIX permission level e.g. 0644, 0664
?>
EOT;

    if (!file_put_contents ( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . _RACKFORMS_INSTALL_NAME_ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'movefiles' . DIRECTORY_SEPARATOR . 'config.php', $config_move_data )) {
        $error = 1;
        $e_message = 'Could not write to app/movefiles/config.php. Check permissions and try again!';
    }
    
    if($error != 0){
        return array(false, $e_message);
    } else {
        return array(true, '');
    }

}







function _install_create_database($dbhost, $dbname, $dbpassword, $dbuser){
    
    $db_vendor = 'mysqli';
    $db_mysql_socket = '';
    
    $db_hostname = $dbhost;
    $db_catalog = $dbname;
    $db_username = $dbuser;
    $db_password = $dbpassword;
    $db_mysql_port = '3306';
            
    
    $error = 0;
    $installed_error = 0;
    
    $e_message = "";
            
    $sql = <<<EOT

CREATE TABLE `fb_admin` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(20) DEFAULT NULL,
  `user_pass` VARCHAR(50) DEFAULT NULL,
  `pwd` VARCHAR(100) DEFAULT NULL,
  `user_email` VARCHAR(50) DEFAULT NULL,
  `user_privilege` INT(11) DEFAULT '0',
  PRIMARY KEY  (`user_id`)
) ENGINE=INNODB CHARSET=utf8 COLLATE utf8_general_ci;
EOT;
$val = execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port);
if($val != ''){
	$error = 1;
	$e_message .= $val . '<br />';
}

// Build 643 Authorization table for license
$sql = <<<EOT
CREATE TABLE `fb_auth`(
  id INT (11) NOT NULL AUTO_INCREMENT,
  auth VARCHAR (255) DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;
EOT;
$val = execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port);
if($val != ''){
	$error = 1;
	$e_message .= $val . '<br />';
}

// Build 638 Added Real timestemp values
$sql = <<<EOT
CREATE TABLE `fb_jobs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_name` VARCHAR(50) DEFAULT NULL,
  `created` VARCHAR(12) DEFAULT NULL,
  `created_ts` DATETIME DEFAULT NULL,
  `last_edit` VARCHAR(12) DEFAULT NULL,
  `last_edit_ts` DATETIME DEFAULT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `job_xml` LONGTEXT,
  PRIMARY KEY  (`id`),
  INDEX IDX_job_name (`job_name`),
  INDEX IDX_user_id (`user_id`)
) ENGINE=INNODB CHARSET=utf8 COLLATE utf8_general_ci;
EOT;
$val = execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port);
if($val != ''){
	$error = 1;
	$e_message .= $val . '<br />';
}

$sql = <<<EOT
CREATE TABLE `fb_job_entries` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_id` INT(11) NOT NULL,
  `ts` INT(11) DEFAULT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `label` VARCHAR (255) DEFAULT NULL,
  `entry_value` TEXT,
  `file_data` LONGBLOB,
  `file_mime` VARCHAR(50) DEFAULT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `entry_key` VARCHAR(50) DEFAULT NULL,
  `entry_type` VARCHAR(20) DEFAULT NULL,
  `remote_ip` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY  (`id`),
  INDEX index1 USING BTREE (`job_id`),
  INDEX IDX_ts USING BTREE (`ts`),
  INDEX IDX_entry_key USING BTREE (`entry_key`),
  CONSTRAINT `fb_job_entries_FK1` FOREIGN KEY (`job_id`) REFERENCES `fb_jobs` (`id`) ON DELETE CASCADE
) ENGINE=INNODB CHARSET=utf8 COLLATE utf8_general_ci;

EOT;

$val = execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port);
if($val != ''){
	$error = 1;
	$e_message .= $val . '<br />';
}


// Build 631 - Makes it easier to have a consistant demo table for jobs
// Build 646 - Added ts field
$sql = <<<EOT
CREATE TABLE fb_demo(
  id INT (11) NOT NULL AUTO_INCREMENT,
  name VARCHAR (50) DEFAULT NULL,
  age INT (11) DEFAULT NULL,
  email VARCHAR (50) DEFAULT NULL,
  pwd BLOB DEFAULT NULL,
  image LONGBLOB DEFAULT NULL,
  ts DATETIME DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;
EOT;
$val = execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port);
if($val != ''){
	$error = 1;
	$e_message .= $val . '<br />';
}

// Build 640 - Makes it easier to have a consistant demo table for image uploads
$sql = <<<EOT
CREATE TABLE fb_images(
  image_id INT (11) NOT NULL AUTO_INCREMENT,
  entry_id INT (11) DEFAULT NULL,
  image_caption LONGTEXT DEFAULT NULL,
  image_name VARCHAR (100) DEFAULT NULL,
  image_mime VARCHAR (40) DEFAULT NULL,
  image_size INT (11) DEFAULT NULL,
  image_data LONGBLOB DEFAULT NULL,
  image_thumb LONGBLOB DEFAULT NULL,
  PRIMARY KEY (image_id)
)
ENGINE = INNODB
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;
EOT;
$val = execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port);
if($val != ''){
	$error = 1;
	$e_message .= $val . '<br />';
}

// Build 646 - Makes it easier to have a consistant demo table for file uploads
$sql = <<<EOT
CREATE TABLE fb_files(
  file_id INT (11) NOT NULL AUTO_INCREMENT,
  entry_id INT (11) DEFAULT NULL,
  file_caption LONGTEXT DEFAULT NULL,
  file_name VARCHAR (100) DEFAULT NULL,
  file_mime VARCHAR (40) DEFAULT NULL,
  file_size INT (11) DEFAULT NULL,
  file_data LONGBLOB DEFAULT NULL,
  file_thumb LONGBLOB DEFAULT NULL,
  PRIMARY KEY (file_id)
)
ENGINE = INNODB
AUTO_INCREMENT = 1
AVG_ROW_LENGTH = 30720
CHARACTER SET utf8
COLLATE utf8_general_ci;
EOT;
$val = execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port);
if($val != ''){
	$error = 1;
	$e_message .= $val . '<br />';
}


// Build 647 - Show off app capabilities
$sql = <<<EOT
CREATE TABLE fb_comments(
  id INT (11) NOT NULL AUTO_INCREMENT,
  ts DATETIME DEFAULT NULL,
  name VARCHAR (50) DEFAULT NULL,
  email VARCHAR (100) DEFAULT NULL,
  website VARCHAR (100) DEFAULT NULL,
  `comment` LONGTEXT DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;
EOT;
$val = execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port);
if($val != ''){
	$error = 1;
	$e_message .= $val . '<br />';
}

    if($error != 0){
        return array(false, '<strong>Database Install Messages:</strong><br/>' . $e_message);
    } else {
        return array(true, '');
    }

}


function _install_create_user_account($dbhost, $dbname, $dbpassword, $dbuser){
    
	
    $db_vendor = 'mysqli';
    $db_mysql_socket = '';
    
    $db_hostname = $dbhost;
    $db_catalog = $dbname;
    $db_username = $dbuser;
    $db_password = $dbpassword;
    $db_mysql_port = '3306';
            
    global $pw_password_salt;
    
    $ac_username = 'test';
    $password = 'test';
    
    $user_password = strip_tags ( md5 ( $pw_password_salt . $password ) );
    
    $ac_password_encrypted = "";
                    
    
    $ac_email = get_option('admin_email');
                    
    $sql = <<<EOT
INSERT INTO fb_admin(user_name, user_pass, pwd, user_email, user_privilege) VALUES ('{$ac_username}', '{$user_password}', '{$ac_password_encrypted}', '{$ac_email}', 9);
EOT;
    $val = execute_mysql_sql($db_vendor, $db_mysql_socket, $db_hostname, $db_catalog, $db_username, $db_password, $sql, $db_mysql_port);
    if($val != ''){
        $error = 1;
    }
        
    if($error != 0){
        return array(false, 'Database Account Creation Install Error');
    } else {
        return array(true, '');
    }
}

function _perform_rackforms_express_install(){
    
    // Collect Database Details.
    global $sanedb;
    global $wpdb;
    $sanedb = new SaneDb($wpdb);
    
    $dbname = $sanedb->getDbName();
    $dbuser = $sanedb->getDbUser();
    $dbhost = $sanedb->getDbHost();
    $dbpassword = $sanedb->getDbPass();
    
    
    // Sanity
    if($dbhost != "" && $dbname != "" && $dbpassword != "" && $dbuser != ""){
        
        // create app config
        $step1 = _install_create_app_config($dbhost, $dbname, $dbpassword, $dbuser);
        
        // create movefiles config
        $step2 = _install_create_movefiles_config($dbhost, $dbname, $dbpassword, $dbuser);
        
        // create database
        $step3 = _install_create_database($dbhost, $dbname, $dbpassword, $dbuser);
        
        // create user account
        $step4 = _install_create_user_account($dbhost, $dbname, $dbpassword, $dbuser);
        
    }
    
    if($step1[0] == true && $step2[0] == true && $step3[0] == true && $step4[0] == true){
        
        return array(true, '');
        
    } else {
        
        return array(false, array_merge($step1, $step2, $step3, $step4));
        
    }
    
    
}



function _install_rackforms_express_init() {

	$arrContextOptions=array(
		"ssl"=>array(
			"verify_peer"=>false,
			"verify_peer_name"=>false,
		),
	);  

    $url = 'https://www.rackforms.com/files/download.php?file=' . _RACKFORMS_INSTALL_NAME_ . '.zip';

    $uploadDir = wp_upload_dir();
    
    $zip_name = 'express';

    if (file_put_contents($uploadDir["path"] . "/" . $zip_name . ".zip", file_get_contents($url, false, stream_context_create($arrContextOptions)))) {

        @chmod($uploadDir["path"] . "/" . $zip_name . ".zip", octdec(755));

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($uploadDir["path"] . "/" . $zip_name . ".zip") === TRUE) {
                $zip->extractTo(WP_PLUGIN_DIR);
                $zip->close();
            } else {
                $error = __("Failed to open the zip archive. Please, upload the plugin manually", 'rackforms-express');
            }
        } elseif (class_exists('Phar')) {
            $phar = new PharData($uploadDir["path"] . "/" . $zip_name . ".zip");
            $phar->extractTo(WP_PLUGIN_DIR);
        } else {
            $error = __("Your server does not support either ZipArchive or Phar. Please, upload the plugin manually", 'rackforms-express');
        }

        @unlink($uploadDir["path"] . "/" . $zip_name . ".zip");
        
        $ret = _perform_rackforms_express_install();
        
        return $ret;
        
    } else {
        
        $error = __("Failed to download the zip archive. Please, upload the plugin manually", 'rackforms-express');
        
    }
}





/**
 * Caled From WP Menu System.
 */
function rackforms_express_options() {
    
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    echo '<div class="wrap" style="margin: 0 20px 0 -20px;">';

    $path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . _RACKFORMS_INSTALL_NAME_;
        
    if (!file_exists( $path . DIRECTORY_SEPARATOR . 'index.php' )) {
    	
    	if(isset($_GET['install']) && $_GET['install'] == 'true'){
    		
    		$result = _install_rackforms_express_init();
    		
    		if($result[0] != true){
    			
    			// print_r($result[1]); die;
    		
    			$error = __("Failed to install RackForms Express", 'rackforms-express');
    			
    			$e_messages = "";
    			
    			foreach($result[1] as $r){
    				if($r != "" && $r != "1")
    					$e_messages .= $r . "<br/>";
    			}
    			
    			$message = <<<EOF
<div style="margin-top:25px; margin-left:20px;">
<h2>Your RackForms Express Installation is Complete, But The Following Issues Were Noted During The Install Process:</h2>
{$e_messages}
<br/><br/>
<span>If the messages are primarily about 'tables already existing', you can safely ignore these messages and refresh your browser now to start building forms!</span>		
</div>
EOF;
    			echo $message;
    		
    		} else {
    		
    			$message = <<<EOF
<div style="margin-top:25px; margin-left:20px;">
<h2>Your RackForms Express Installation is Complete!</h2>
Simply refresh this page to begin buiding forms.
</div>
EOF;
    			echo $message;
    		
    		}
    	
    	} else {
    		
    		
    		
    		$self = $_SERVER['PHP_SELF'];
    		
    		$message = <<<EOF
<div style="margin-top:25px; margin-left:20px;">
<h2>RackForms Express For WordPress Installation.</h2>
<span>To start the install processes click the button below. Please note this process may take up to 2 minutes to complete, so please do not refresh your browser during this time!</span><br/><br/>
<form method="GET" action="{$self}?page=rackforms_express_options">
<input type="submit" name="Submit" value="Install RackForms Express" />
<input type="hidden" name="install" value="true" />
<input type="hidden" name="page" value="rackforms_express_options" />
</form>
</div>
EOF;
    		echo $message;
    		
    	}
    	
        
    } else {
    	
    	// RackForms Express Installed - Show Editor.
    	
        echo '<iframe style="background-color:white;" id="rackforms-iframe" class="rackforms-iframe" name="express" src="' . plugins_url() . '/' . _RACKFORMS_INSTALL_NAME_ . '/app/editor.php' . '" frameborder="0" scrolling="YES" ALLOWTRANSPARENCY="true"></iframe>';
  
    }


    echo '</div>';
}


$path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . _RACKFORMS_INSTALL_NAME_;

if (file_exists( $path . DIRECTORY_SEPARATOR . 'index.php' ))
	add_action( 'admin_enqueue_scripts', 'rackforms_enqueue' );



/*
  Plugin Name: RackForms iFrame Resize.
  Plugin URI: http://www.rackforms.com/documentation/rackforms/wordpress/index.php
  Description: Dynamically resizes any RackForms iFrame elements embedded in our posts when the window resizes, though in most cases this is only needed for responsive/liquid templates like the default 'Twenty Twelve', 'Twenty Eleven ', etc. This plugin also browsers sniffs and if a mobile browser is detected, automatically loads the -mobile version of our form. <strong>Important Note:</strong> Your forms must be hosted on the same domain as your WordPress install for this plugin to work!
  Version: 1.1
  Author: nicsoft
  Author URI: http://www.rackforms.com
  Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
 */

/*
  Copyright 2008-2012 nicsoft  (email : info@rackforms.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

define('HEFO_VERSION', '1.3.9');

$hefo_options = get_option('hefo');

add_action('init', 'hefo_init');
function hefo_init() {
  global $hefo_options;
  
  if (get_option('hefo_version') == null) {
    update_option('hefo_version', HEFO_VERSION);
    // Patch for version 1.3.9
    $hefo_options['og_enabled'] = 1;
    update_option('hefo', $hefo_options);
  }
  
  if (get_option('hefo_version') != HEFO_VERSION) {
    update_option('hefo_version', HEFO_VERSION);
  }
  
}

add_action('wp_head', 'hefo_wp_head_post', 11);

// RackForms - Replace.
function hefo_wp_head_post() {
	
	//wp_enqueue_script('jquery');
	
    global $hefo_options, $wp_query, $wpdb;
    $buffer = '';
    if (is_home ()) $buffer .= hefo_replace($hefo_options['head_home']);

    $rackforms_js_content = <<<EOF

<script type="text/javascript">

/**
 * This is freely distributable code brought to you by nicSoft, makers of RackForms.
 *
 * Code should work on almost all modern browsers, including old versions of IE.
 * 
 * This code will resize an iframe (or any other element) based on window size.
 *
 * USAGE
 *
 * 1. set_width and set_height control which direction we want to
 *    resize in.
 *
 * 2. width_offset and height_offset control the padding.
 */
 
var obj_name = 'rackforms-iframe'; // must match the iframe class name, defaults to rackforms-iframe
var set_width = true; // default true
var set_height = true; // default true

var width_offset = -500; // this should be the column width the iframe sits in.
var height_offset = 20;


// get iframe object based on class name
function getObj(name){	
	object = getElementsByClassName(name);
	
	if(typeof(object[0]) == "undefined"){
		this.obj = false;
		return;
	}
	
	this.obj = object[0];
    this.style = object[0].style;	
};

// get window size
function getWinSize(){
	var iWidth = 0, iHeight = 0;
	
	if (document.getElementById){
		iWidth = window.innerWidth;
		iHeight = window.innerHeight;
	} else if (document.all){
		iWidth = document.body.offsetWidth;
 		iHeight = document.body.offsetHeight;
	}
	
	return {width:iWidth, height:iHeight};
};

// http://james.padolsey.com/javascript/get-document-height-cross-browser/
function getDocHeight(D) {
    return Math.max(
        Math.max(D.body.scrollHeight, D.documentElement.scrollHeight),
        Math.max(D.body.offsetHeight, D.documentElement.offsetHeight),
        Math.max(D.body.clientHeight, D.documentElement.clientHeight)
    );
}

// resize window logic
function resize_id(obj) {

	var _parentDocHeight = (document.height !== undefined) ? document.height : document.body.offsetHeight;
	var _parentDocWidth = (document.width !== undefined) ? document.width : document.body.offsetWidth;
	
	var oContent = new getObj(obj);
	
	if(oContent.obj == false)
		return;
	
	var oWinSize = getWinSize();
	
	var cw = getElementsByClassName('rackforms-iframe');
	cw = cw[0];
	
	var _docHeight = -1;
	
	if(cw.document !== undefined){ // IE
		
		// http://www.w3schools.com/jsref/prop_frame_contentdocument.asp
		var y = (cw.contentWindow || cw.contentDocument);
		if (y.document)
			y = y.document;
		
		// get height
		if(y.documentElement)
			_docHeight = y.documentElement.scrollHeight;
	
	} else if(cw.contentDocument.documentElement.scrollHeight !== undefined) { // Chrome
	
		if(cw.contentDocument.documentElement.scrollHeight !== undefined)
			_docHeight = cw.contentDocument.documentElement.scrollHeight;
			
	}
	
	var h = oWinSize.height - parseInt(oContent.obj.offsetTop,10);
	var w = oWinSize.width - parseInt(oContent.obj.offsetTop,10);
	
	h = h - height_offset;
	w = w - width_offset;
	
	//if (h > 0 && w > 0) { // doesn't work well with console enabled.
	
		if(set_height && _docHeight > 150) // must be at least 100px to avoid a FF bug where the form doesn't show on load.
			oContent.style.height = _docHeight.toString() + "px";
		
		if(set_width)
			oContent.style.width = w.toString()+"px";
			
	//}

};

/*
	Developed by Robert Nyman, http://www.robertnyman.com
	Code/licensing: http://code.google.com/p/getelementsbyclassname/
*/	
var getElementsByClassName = function (className, tag, elm){
	if (document.getElementsByClassName) {
		getElementsByClassName = function (className, tag, elm) {
			elm = elm || document;
			var elements = elm.getElementsByClassName(className),
				nodeName = (tag)? new RegExp("\\b" + tag + "\\b", "i") : null,
				returnElements = [],
				current;
			for(var i=0, il=elements.length; i<il; i+=1){
				current = elements[i];
				if(!nodeName || nodeName.test(current.nodeName)) {
					returnElements.push(current);
				}
			}
			return returnElements;
		};
	}
	else if (document.evaluate) {
		getElementsByClassName = function (className, tag, elm) {
			tag = tag || "*";
			elm = elm || document;
			var classes = className.split(" "),
				classesToCheck = "",
				xhtmlNamespace = "http://www.w3.org/1999/xhtml",
				namespaceResolver = (document.documentElement.namespaceURI === xhtmlNamespace)? xhtmlNamespace : null,
				returnElements = [],
				elements,
				node;
			for(var j=0, jl=classes.length; j<jl; j+=1){
				classesToCheck += "[contains(concat(' ', @class, ' '), ' " + classes[j] + " ')]";
			}
			try	{
				elements = document.evaluate(".//" + tag + classesToCheck, elm, namespaceResolver, 0, null);
			}
			catch (e) {
				elements = document.evaluate(".//" + tag + classesToCheck, elm, null, 0, null);
			}
			while ((node = elements.iterateNext())) {
				returnElements.push(node);
			}
			return returnElements;
		};
	}
	else {
		getElementsByClassName = function (className, tag, elm) {
			tag = tag || "*";
			elm = elm || document;
			var classes = className.split(" "),
				classesToCheck = [],
				elements = (tag === "*" && elm.all)? elm.all : elm.getElementsByTagName(tag),
				current,
				returnElements = [],
				match;
			for(var k=0, kl=classes.length; k<kl; k+=1){
				classesToCheck.push(new RegExp("(^|\\s)" + classes[k] + "(\\s|$)"));
			}
			for(var l=0, ll=elements.length; l<ll; l+=1){
				current = elements[l];
				match = false;
				for(var m=0, ml=classesToCheck.length; m<ml; m+=1){
					match = classesToCheck[m].test(current.className);
					if (!match) {
						break;
					}
				}
				if (match) {
					returnElements.push(current);
				}
			}
			return returnElements;
		};
	}
	return getElementsByClassName(className, tag, elm);
};


// http://code.google.com/p/domready/
// This way we do not need the entire jQuery libraries.
(function(){

    var DomReady = window.DomReady = {};

	// Everything that has to do with properly supporting our document ready event. Brought over from the most awesome jQuery. 

    var userAgent = navigator.userAgent.toLowerCase();

    // Figure out what browser is being used
    var browser = {
    	version: (userAgent.match( /.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/ ) || [])[1],
    	safari: /webkit/.test(userAgent),
    	opera: /opera/.test(userAgent),
    	msie: (/msie/.test(userAgent)) && (!/opera/.test( userAgent )),
    	mozilla: (/mozilla/.test(userAgent)) && (!/(compatible|webkit)/.test(userAgent))
    };    

	var readyBound = false;	
	var isReady = false;
	var readyList = [];

	// Handle when the DOM is ready
	function domReady() {
		// Make sure that the DOM is not already loaded
		if(!isReady) {
			// Remember that the DOM is ready
			isReady = true;
        
	        if(readyList) {
	            for(var fn = 0; fn < readyList.length; fn++) {
	                readyList[fn].call(window, []);
	            }
            
	            readyList = [];
	        }
		}
	};

	// From Simon Willison. A safe way to fire onload w/o screwing up everyone else.
	function addLoadEvent(func) {
	  var oldonload = window.onload;
	  if (typeof window.onload != 'function') {
	    window.onload = func;
	  } else {
	    window.onload = function() {
	      if (oldonload) {
	        oldonload();
	      }
	      func();
	    }
	  }
	};

	// does the heavy work of working through the browsers idiosyncracies (let's call them that) to hook onload.
	function bindReady() {
		if(readyBound) {
		    return;
	    }
	
		readyBound = true;

		// Mozilla, Opera (see further below for it) and webkit nightlies currently support this event
		if (document.addEventListener && !browser.opera) {
			// Use the handy event callback
			document.addEventListener("DOMContentLoaded", domReady, false);
		}

		// If IE is used and is not in a frame
		// Continually check to see if the document is ready
		if (browser.msie && window == top) (function(){
			if (isReady) return;
			try {
				// If IE is used, use the trick by Diego Perini
				// http://javascript.nwbox.com/IEContentLoaded/
				document.documentElement.doScroll("left");
			} catch(error) {
				setTimeout(arguments.callee, 0);
				return;
			}
			// and execute any waiting functions
		    domReady();
		})();

		if(browser.opera) {
			document.addEventListener( "DOMContentLoaded", function () {
				if (isReady) return;
				for (var i = 0; i < document.styleSheets.length; i++)
					if (document.styleSheets[i].disabled) {
						setTimeout( arguments.callee, 0 );
						return;
					}
				// and execute any waiting functions
	            domReady();
			}, false);
		}

		if(browser.safari) {
		    var numStyles;
			(function(){
				if (isReady) return;
				if (document.readyState != "loaded" && document.readyState != "complete") {
					setTimeout( arguments.callee, 0 );
					return;
				}
				if (numStyles === undefined) {
	                var links = document.getElementsByTagName("link");
	                for (var i=0; i < links.length; i++) {
	                	if(links[i].getAttribute('rel') == 'stylesheet') {
	                	    numStyles++;
	                	}
	                }
	                var styles = document.getElementsByTagName("style");
	                numStyles += styles.length;
				}
				if (document.styleSheets.length != numStyles) {
					setTimeout( arguments.callee, 0 );
					return;
				}
			
				// and execute any waiting functions
				domReady();
			})();
		}

		// A fallback to window.onload, that will always work
	    addLoadEvent(domReady);
	};

	// This is the public function that people can use to hook up ready.
	DomReady.ready = function(fn, args) {
		// Attach the listeners
		bindReady();
    
		// If the DOM is already ready
		if (isReady) {
			// Execute the function immediately
			fn.call(window, []);
	    } else {
			// Add the function to the wait list
	        readyList.push( function() { return fn.call(window, []); } );
	    }
	};
    
	bindReady();
	
})();

var getLocation = function(href) {
	var l = document.createElement("a");
	l.href = href;
	return l;
};

/**
 * Gets all related CSS rules, though will not pick up on media query blocks.
 */
function getCSSRule(ruleName){
	
	ruleName = ruleName.toLowerCase(); 
	
	foundRules = new Array();
	
	if (document.styleSheets) {
	
		for (var i = 0; i < document.styleSheets.length; i++) {
			
			var styleSheet = document.styleSheets[i]; 
			var ii = 0; 
			var cssRule = false;
			var rulesCount = -1;
			
			// must be same origin for FF or we throw an exception
			var l = getLocation(styleSheet.href);
			if(l.hostname == window.location.hostname){
			
				if (styleSheet.cssRules) {
					rulesCount = styleSheet.cssRules.length;
				} else {
					rulesCount = styleSheet.rules.length;
	            }
			
				for (var j = 0; j < rulesCount; j++) {
				
					if (styleSheet.cssRules) {
						cssRule = styleSheet.cssRules[ii];
					} else {
						cssRule = styleSheet.rules[ii];
		            }
		            
		            if (cssRule)  {
						
		            	// CSSStyleRule
		            	if (cssRule.selectorText && cssRule.selectorText.toLowerCase() == ruleName) {
							foundRules.push(cssRule);
							ii++;
							continue;
						}
						
						// CSSMediaRule
						if (cssRule.cssRules) {

							for(var r = 0; r < cssRule.cssRules.length; r++){
							
								var cssMediaRule = cssRule.cssRules[r];
								
								if (cssMediaRule.selectorText && cssMediaRule.selectorText.toLowerCase() == ruleName) {
									foundRules.push(cssMediaRule);
								}
							
							}
						
			            } else if(cssRule.rules) {
			            
			            	for(var r = 0; r < cssRule.rules.length; r++){
							
								var cssMediaRule = cssRule.rules[r];
								
								if (cssMediaRule.selectorText && cssMediaRule.selectorText.toLowerCase() == ruleName) {
									foundRules.push(cssMediaRule);
								}
							
							}
			            
			            }

			            
						
					}

					ii++;
					
				}

			}
	
		}
		
		return foundRules;
		
	}
};
                      
// http://www.abeautifulsite.net/blog/2011/11/detecting-mobile-devices-with-javascript/
var isMobile = {
    Android: function() {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function() {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function() {
        return navigator.userAgent.match(/iPhone|iPad|iPod/i);
    },
    Opera: function() {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function() {
        return navigator.userAgent.match(/IEMobile/i);
    },
    any: function() {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
};

/**
 * Function Not Used As Of Version 1.2.
 */
function isResponsive(){
	
	// Rules for responsive/liquid detection:
	// All post content held in a div called #content.
	// in 2012 we wrap #content with a div called #primary, but this has a class of .site-content, this is what has the %.
	// in 2011 we wrap #content with a div called primary, but this div spans the entire page.
	// in 2011 the #content div has % as a css file declaration.
	// in 2010 #content has no %, and no wrapper that uses % -- it is a non-responsive template.
	
	// 2012
	var r = getCSSRule('.site-content');
	for(var t = 0; t < r.length; t++){
		if(r[t].style.width.indexOf('%') != -1){
			return true;
		}
	}
	
	// 2011
	r = getCSSRule('#content');
	for(var t = 0; t < r.length; t++){
		if(r[t].style.width.indexOf('%') != -1){
			return true;
		}
	}
	
	// other conditions...
	
	return false;
	
};


var rf_isMobile;
var rf_isResponsive;

// handle onload()
DomReady.ready(function() {

	rf_isMobile = isMobile.any();
	rf_isResponsive = isResponsive();

	// only activate mobile version if a mobile device is being used (detected)
	if(rf_isMobile){
	
		// get iframe
		o = getElementsByClassName(obj_name);
	
		iframe = o[0];

        // Set iframe to 100% width.
        iframe.width = "100%";
 		
	} else {
	
		// always resize iframe on page load
		
		// Build 1.3 Fix.
		
		var iframe = document.getElementById('fb-iframe');
		
		if (iframe.attachEvent){
		   iframe.attachEvent("onload", function(){
			   resize_id(obj_name);
		   });
		} else {
		   iframe.onload = function(){
			   resize_id(obj_name);
		   };
		}
		
 		
	
	}
	
	// always enable the resize event handler
	window.onresize = function() { resize_id(obj_name); };

});

/**
 * Called From RackForms.
 */
function DOM_Loaded(){
    
    if(isMobile.any()){
            
        // Set main output div to 100%
        jQuery('#fb-iframe').contents().find('.formboss-output-div').css({width: '100%'});

        // text input
        jQuery('#fb-iframe').contents().find(':text').css({width: '96%'});

        // text area input
        jQuery('#fb-iframe').contents().find('textarea').css({width: '96%'});

        // select input
        jQuery('#fb-iframe').contents().find('select').css({width: '98%'});

        // field containers
        jQuery('#fb-iframe').contents().find('.rackforms-field-container').css({width: '100%'});
               
    }
    
    // Always resize content on iframe content load.
    resize_id(obj_name);

};
            
</script>

            
            
EOF;
	
    // replace the head content with our custom script
	$buffer .= hefo_replace($rackforms_js_content);

    ob_start();
    eval('?>' . $buffer);
    $buffer = ob_get_contents();
    ob_end_clean();
    echo $buffer;
}

function hefo_replace($buffer) {
    global $hefo_options;
    if (empty($buffer)) return '';
    for ($i=1; $i<=5; $i++) {
        $buffer = str_replace('[snippet_' . $i . ']', $hefo_options['snippet_' . $i], $buffer);
    }
    return $buffer;
}

function hefo_execute($buffer) {
    if (empty($buffer)) return '';
    ob_start();
    eval('?>' . $buffer);
    $buffer = ob_get_contents();
    ob_end_clean();
    return $buffer;
}
?>