<?php
date_default_timezone_set("Asia/Kolkata"); 
$log_file = "./error.log";
ini_set("log_errors", TRUE);
ini_set('error_log', $log_file);
parse_ini_file("./attendanceConfig.ini",true);
class AtServerConfiguration{
    public $_appOwnner;
    public $_docType;
    public $_environment;
  
    public $_deployement;
    public $_error;
    
    public function __construct() {
        $config=parse_ini_file("../attendance/attendanceConfig.ini",true);
        $this->_appOwnner = $config['ApplicationOwnner'];
        $this->_docType=$config['DocumentType'];
        $this->_environment=$config['environment'];
        $this->_deployement=$config['deploymentServer'];
      
        $this->_error==$config['error'];
    }
}
?>