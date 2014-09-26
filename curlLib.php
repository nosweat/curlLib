<?php
    /**
     * PHP Curl Wrapper
     *
     * @author DWAPPU
     * @copyright 2014 DWAPPU
     * @version 1.1 
     */
    class curlLib{
        private $CH;
        private $METHOD;
        private $DATA;
        private $HEADERS = array();
        private $REDIRECTION;
        
        /**
         * Sets the Curl Objects. HEAD is not implement since PHP get_header() can be used
         * @param string $method GET, POST, PUT, DELETE, HEAD
         * @param string $url
         * @param string/array $data String for json payload and Array for normal Form Post
         * @param array $headers
         * @param string $user_agent
         * @param bool $redirection
         */
        public function __construct($method,$url,$data,$headers=array(),$user_agent='',$redirection=FALSE){
            $this->CH = curl_init();
            
            if(($method == 'GET' || $method == 'HEAD') && !empty($data))
                $url .= '?'.http_build_query($data);
            
            curl_setopt($this->CH, CURLOPT_URL, $url);
            curl_setopt($this->CH, CURLOPT_RETURNTRANSFER, 1);
            
            if($user_agent != '') {
                curl_setopt($this->CH, CURLOPT_USERAGENT, $user_agent);
            }
            if($redirection){
    			curl_setopt($this->CH, CURLOPT_FOLLOWLOCATION, true);	
			}
            
            $this->METHOD = $method;
            $this->DATA = $data;
            $this->HEADERS = $headers;
            $this->REDIRECTION = $redirection;
        }
        
        private function setHeader(){
            curl_setopt($this->CH, CURLOPT_HTTPHEADER, $this->HEADERS);
            curl_setopt($this->CH, CURLINFO_HEADER_OUT, true);
        }
        
        private function setCurlMethod($custom_request=FALSE){
            if(!$custom_request) {
                if($this->METHOD == 'POST')
                    curl_setopt($this->CH, CURLOPT_POST, 1);
                else if($this->METHOD == 'PUT')
                    curl_setopt($this->CH, CURLOPT_PUT, 1);
                else if($this->METHOD == 'HEAD'){
                	curl_setopt($this->CH, CURLOPT_NOBODY, 1);
                	curl_setopt($this->CH, CURLOPT_HEADER, 1);
                }
            } else {
                curl_setopt($this->CH, CURLOPT_CUSTOMREQUEST, $this->METHOD);
            }
        }
        
        private function setPostFields(){
            if(is_array($this->DATA)) { 
                curl_setopt($this->CH, CURLOPT_POSTFIELDS, http_build_query($this->DATA));
            } else {
                curl_setopt($this->CH, CURLOPT_POSTFIELDS, $this->DATA);
            }
        }
        
        /**
         * Sets CURLOPT_FAILONERROR
         * @param bool $fail
         */
        public function setFailOnError($fail=TRUE){
            curl_setopt($this->CH, CURLOPT_FAILONERROR, $fail);
        }
        
        /**
         * Sets the CURLOPT_HTTPAUTH
         * @param string $http_auth
         * @param string $username
         * @param string $password
         */
        public function setAuth($http_auth=CURLAUTH_BASIC,$username,$password){
            if($username != '' && $password != ''){
                curl_setopt($this->CH, CURLOPT_HTTPAUTH, $http_auth);
                curl_setopt($this->CH, CURLOPT_USERPWD, "{$username}:{$password}");
            }
        }
        
     	/**
          * Sets the CURLOPT_CONNECTTIMEOUT
          * @param integer $connect_timeout - in seconds
          */
         public function setConnectTimeout($connect_timeout=0){
             curl_setopt($this->CH, CURLOPT_CONNECTTIMEOUT, $connection_timeout);
         }
         
         /**
          * Sets the CURLOPT_TIMEOUT
          * @param integer $timeout - in seconds
          */
         public function setTimeout($timeout=0){
             curl_setopt($this->CH, CURLOPT_TIMEOUT, $timeout);
         }
        
        private function getError($result){
            $curlError = array();
            
            if($result === false){
                $curlError = array('ERROR' => array('message' => 'Curl error: '.curl_error($this->CH)));
            }
                                                    
            return $curlError;
        }
        
        private function getHeaderResponse(){
            $headerSize = curl_getinfo($this->CH, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
        }
        
        /**
         * 
         * Parse Header Response from HEAD Request
         * @param Curl Response $result
         * @return Array $headerOutput e.g. : <br/><pre>{
		        "0": "HTTP/1.1 200 OK",
		        "Date": "Sat, 20 Sep 2014 02:47:09 GMT",
		        "Server": "Apache/2.2.22 (Ubuntu)",
		        "X-Powered-By": "PHP/5.3.10-1ubuntu3.10",
		        "Access-Control-Allow-Origin": "*",
		        "Access-Control-Allow-Headers": "X_DW_Method_Override, X_DW_Session_Token",
		        "Content-Row-Count": "289",
		        "Content-Type": "application/json; charset=utf-8"
		    }</pre>
         */
        private function parseHeaderRespsonse($result = NULL){
        	if(is_null($result)){
        		return false;
        	}else{
        		list($header, $body) = explode("\r\n\r\n", $result, 2);
				$header = explode("\r\n",$header);
				$headerOutput = array();
				if(!empty($header)){
					foreach($header as $key => $h){
						if($key > 0){
							list($headerKey,$headerValue) = explode(":",$h);
							if($headerKey == 'Date'){
								$dateData = explode(":",$h,2);
								$dateString = '';
								if(isset($dateData[1])){
									$dateString = trim($dateData[1]," ");
								}
								$headerOutput[$headerKey] = $dateString;
							}else{
								$headerOutput[$headerKey] = trim($headerValue," ");
							}
						}else{
							$headerOutput[$key] = $h;
						}
					}
					return $headerOutput;
				}
        	}
        	return false;
        }
        
        /**
         * Executes the Curl request
         * @param bool $custom_request Set to TRUE for Custom HTTP Request
         * @param bool $verbose Set to TRUE to activate debugging
         * @return array Request Header, Status Code, Error and Result
         */
        public function execute($custom_request=FALSE,$verbose=FALSE){
            if($verbose) {
                curl_setopt($this->CH, CURLOPT_VERBOSE, TRUE);
                //Change the Directory to your log directory
                curl_setopt($this->CH, CURLOPT_STDERR, @fopen('/tmp/curl-lib.log','a+'));
            }
            
            $this->setHeader();
            $this->setCurlMethod($custom_request);
            $this->setPostFields();
            $result = curl_exec($this->CH);
            
            $curlResponse = array();
            $curlResponse['request_header'] = curl_getinfo($this->CH, CURLINFO_HEADER_OUT);
            $curlResponse['status_code'] = curl_getinfo($this->CH, CURLINFO_HTTP_CODE);
            $curlResponse['error'] = $this->getError($result);
            
            if($this->METHOD == 'HEAD'){
            	$result = $this->parseHeaderRespsonse($result);
            }
            
            if(empty($curlResponse['error']))
                $curlResponse['result'] = $result;
            
            curl_close($this->CH);
            
            return $curlResponse;
        }
    }
?>
