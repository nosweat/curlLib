<?php
    /**
     * PHP Curl Wrapper
     *
     * @author DWAPPU
     * @copyright 2014 DWAPPU
     * @version 1.0
     */
    class curlLib{
        private $CH;
        private $METHOD;
        private $DATA;
        private $HEADERS = array();
        private $REDIRECTION;
        
        /**
         * Sets the Curl Objects. HEAD is not implement since PHP get_header() can be used
         * @param string $method GET, POST, PUT, DELETE
         * @param string $url
         * @param string/array $data String for json payload and Array for normal Form Post
         * @param array $headers
         * @param string $user_agent
         * @param bool $redirection
         */
        public function __construct($method,$url,$data,$headers=array(),$user_agent='',$redirection=FALSE){
            $this->CH = curl_init();
            
            if($method == 'GET' && !empty($data))
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
        
        private function getError($result){
            $curlError = array();
            
            if($result === false){
                $curlError = array('ERROR' => array('message' => 'Curl error: '.curl_error($this->CH)));
            }
                                                    
            return $curlError;
        }
        
        private functon getHeaderResponse(){
            $headerSize = curl_getinfo($this->CH, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
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
                curl_setopt($this->CH, CURLOPT_STDERR, fopen('/tmp/curl-lib.log','a+'));
            }
            
            $this->setHeader();
            $this->setCurlMethod($custom_request);
            $this->setPostFields();
            $result = curl_exec($this->CH);
            
            $curlResponse = array();
            $curlResponse['request_header'] = curl_getinfo($this->CH, CURLINFO_HEADER_OUT);
            $curlResponse['status_code'] = curl_getinfo($this->CH, CURLINFO_HTTP_CODE);
            $curlResponse['error'] = $this->getError($result);
            
            if(empty($curlResponse['error']))
                $curlResponse['result'] = $result;
            
            curl_close($this->CH);
            
            return $curlResponse;
        }
    }
?>
