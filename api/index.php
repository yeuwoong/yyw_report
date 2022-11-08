<?php

	class CommonRestFunc {
		private $methodType;
		private $routes;
		private $arrUri;
		private $postContent;
		private $responseData;
		private $remoteAddr;
		private $networkData;
		private $headers;

		function __construct() {
			$network_envData  	= file_get_contents("network_stat.json");
			$this->networkData	= json_decode($network_envData);

			// header 정보 parsing
			if( !$this->parseHeaderInfo() ) {
				$this->handle(401);

				return ;
			}

			$this->responseData = array();
			$this->responseData['message'] = "";
			$this->responseData['result']  = "";
			$this->responseData['code']    = "";

			$this->methodType  = $_SERVER['REQUEST_METHOD'];
			$this->postContent = json_decode(file_get_contents('php://input'));

			$baseUrl = $this->getCurrentURI();

			$this->routes = array();
			$routes = explode('/', $baseUrl);

			foreach( $routes as $route ) {
				if( trim($route) != '' )
					array_push($this->routes, $route);
			}

			return ;
		}
		private function getHostIpAddr($_type) {
			if( isset($_type) ) {
				if( $this->networkData->$_type->view == "enabled"
				 && $this->networkData->$_type->use  == "enabled" ) {
					if( $this->networkData->$_type->ip_address == "" ) return "-";

					return $this->networkData->$_type->ip_address;

				} else {
					return "-";
				}

			} else {
				if( $this->networkData->network_bonding->view == "enabled"
				 && $this->networkData->network_bonding->use  == "enabled" ) {
					return $this->networkData->network_bonding->ip_address;

				} else if( $this->networkData->network_primary->view == "enabled"
						&& $this->networkData->network_primary->use  == "enabled" ) {
					return $this->networkData->network_primary->ip_address;

				} else if( $this->networkData->network_secondary->view == "enabled"
						&& $this->networkData->network_secondary->use  == "enabled" ) {
					return $this->networkData->network_secondary->ip_address;
				}
			}
		}

		private function parseHeaderInfo() {
			return $this->getDeviceKeyInfo();
		}
		
		private function getDeviceKeyInfo() {
			$this->headers = apache_request_headers();
			
			// 1. 비인가 API 인증 처리
			$authlessList = array(	// 비인가 API list
									"/get_all_auth",
									"/get_api_list",
									"/test"
								);

			if( in_array($this->getCurrentURI(), $authlessList) ) {
				return true;
			}

			// 2. 일반 API 인증 처리
			if(    !array_key_exists('X-Account-Sign',		  $this->headers)
				|| !array_key_exists('X-Account-Secret', 	  $this->headers) ) return false;

			$keyInfo = json_decode(file_get_contents("../conf/key.json"));
			
			$tmp_flag = false;
			foreach( $keyInfo->user_list as $key => $auth ) {
				if( strcmp($key, $this->headers['X-Account-Sign']) === 0
				  && strcmp($auth, $this->headers['X-Account-Secret']) === 0 ) {
					$tmp_flag = true;
				}
			}
			
			return $tmp_flag;

			//return true;
		}

		private function getCurrentURI() {
			$basePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
			$uri = substr($_SERVER['REQUEST_URI'], strlen($basePath));

			if( strstr($uri, '?') ) {
				$uri = substr($uri, 0, strpos($uri, '?'));
			}
			$uri = '/' . trim($uri, '/');

			return $uri;
		}

		private function getPathRoute($_path) {
			$routes  = array();
			$arrPath = explode('/', $_path);

			foreach( $arrPath as $route ) {
				if( trim($route) != '' )
					array_push($routes, $route);
			}

			return $routes;
		}

		private function setRequestURI($_arrUri) {
			$this->arrUri = $_arrUri;

			return ;
		}

		private function setJsonContent($_arrData) {

			return json_encode($_arrData);
		}

		private function checkCurrentURI($_path) {
			if( ($cnt = preg_match_all("/\{.*\}/iU", $_path, $matches)) ) {
				$arrUri = $this->getPathRoute($this->getCurrentURI());
				$reqUri = explode('/', trim($_path, '/'));

				$reqCnt = count($reqUri);
				$uriCnt = count($arrUri);

				// URI depth check
				if( $reqCnt != $uriCnt ) return false;

				// URI name check
				for( $idx = 0 ; $idx < $reqCnt ; $idx++ ) {
					if( !preg_match_all("/\{.*\}/iU", $reqUri[$idx])
						&& $arrUri[$idx] != $reqUri[$idx] ) {
						return false;
					}
				}

				$arrPath = array();
				for( $idx = 0 ; $idx < $cnt ; $idx++ ) {
					$key =  preg_replace("/[{}]+/", '', $matches[0][$idx]);
					$arrPath[$key] = $arrUri[$idx + 1];
				}

				return $arrPath;
			}

			if( $this->getCurrentURI() == $_path ) {
				return true;

			} else {
				return false;
			}
		}

		private function response($_data) {
			header('Content-Type: application/json');
			echo $_data;

			return ;
		}

		public function getHttpStatusMessage($_statusCode) {
			$httpStatus = array(
				100 => 'Continue',
				101 => 'Switching Protocols',
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				306 => '(Unused)',
				307 => 'Temporary Redirect',
				400 => 'Bad Request',
				401 => 'Unauthorized',
				402 => 'Payment Required',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported');

			if( $_statusCode == null ) {
				return $httpStatus;

			} else {
				return ($httpStatus[$_statusCode]) ? $httpStatus[$_statusCode] : $status[500];
			}
		}

		public function setResponseMessage($_message) {

			$this->responseData['message'] = $_message;
		}

		public function setResponseResult($_result) {

			$this->responseData['result']  = $_result;
		}

		public function setResponseCode($_code) {

			$this->responseData['code']    = $_code;
		}

		public function getPostContent() {

			return $this->postContent;
		}

		public function getRequestURI() {

			return $this->arrUri;
		}

		public function getResponseData() {

			return $this->setJsonContent($this->responseData);
		}

		public function getRemoteAddr() {

			return $this->remoteAddr;
		}
		
		public function getHeaders() {
			return $this->headers;
		}

		/* Method 정의 */
		// Method type 및 URI가 없을 때
		public function handle($_httpCode = NULL) {
			if( !isset($_httpCode) ) $_httpCode = 404;

			$this->setResponseMessage("error");
			$this->setResponseResult($this->getHttpStatusMessage($_httpCode));
			$this->setResponseCode($_httpCode);

			$this->response($this->getResponseData());

			exit;
		}

		// Method type : GET
		public function get($_path, $_func, $_type = "GET") {
			if( $this->methodType != $_type ) return ;
			if( !($rc = $this->checkCurrentURI($_path)) ) return ;

			$this->setRequestURI($rc);
			$this->response($_func());

			exit;
		}

		// Method type : POST
		public function post($_path, $_func, $_type = "POST") {
			if( $this->methodType != $_type ) return ;
			if( !($rc = $this->checkCurrentURI($_path)) ) return ;
			if( !in_array('application/json', explode(';',$_SERVER['CONTENT_TYPE'])) ) return ;

			$this->setRequestURI($rc);
			$this->response($_func());

			exit;
		}
	} // end of CommonRestFunc()

	$app = new CommonRestFunc();

	// [비인가] API 테스트
	$app->post(
		"/test",
		function() use($app) {
			$app->setResponseMessage("ok");
			$app->setResponseResult(
						array(
							"Description"	=> "인가되지 않고 동작 가능한 API 테스트입니다.",
							"test"	=> "test is ok!"
						)
			);
			$app->setResponseCode("200");
			return $app->getResponseData();
		}
	);

	// 인가 API 테스트 ( key.json 의 내용을 기반으로 매칭되지 않는다면 api 사용 불가하도록 )
	$app->get(
		"/auth_check",
		function() use($app) {
			$account = $app->getHeaders()['X-Account-Sign'];
			$app->setResponseMessage("ok");
			$app->setResponseResult(
						array(
							"Description"	=> "내부 서버의 key.json 의 내용을 기반으로 매칭되지 않는다면 api 사용 불가하도록 동작됩니다.",
							$account	=> "auth is ok!"
						)
			);
			$app->setResponseCode("200");
			return $app->getResponseData();
		}
	);

	// header로 넘어온 User 사용 권한 체크
	$app->post(
		"/get_auth_info",
		function() use($app) {
			$account = $app->getHeaders()['X-Account-Sign'];
			$key_data = json_decode(file_get_contents("/workspace/yyw_report/conf/key.json"));
			
			$app->setResponseMessage("ok");
			$app->setResponseResult(
						array(
							"Description"	=> "해당하는 User가 사용 가능한 권한을 Return 합니다.",
							$account		=> $key_data->auth->{$account}
						)
			);
			$app->setResponseCode("200");
			return $app->getResponseData();
		}
	);

	// [비인가] api 리스트 전달
	$app->post(
		"/get_api_list",
		function() use($app) {
			$app->setResponseMessage("ok");
			$app->setResponseResult(
						array(
							"/test"				=> "[비인가] API 테스트",
							"/get_api_list"		=> "[비인가] api 리스트 전달",
							"/get_all_auth"		=> "[비인가] 전체 Auth 전달",
							"/auth_check"		=> "인가 API 테스트 ( key.json 의 내용을 기반으로 매칭되지 않는다면 api 사용 불가하도록 )",
							"/get_auth_info"	=> "header로 넘어온 User 사용 권한 체크",
							"/chg_auth_role"	=> "권한 변경"
						)
			);
			$app->setResponseCode("200");
			return $app->getResponseData();
		}
	);

	// [비인가] 전체 Auth 전달
	$app->post(
		"/get_all_auth",
		function() use($app) {
			$key_data = json_decode(file_get_contents("/workspace/yyw_report/conf/key.json"));
			
			$app->setResponseMessage("ok");
			$app->setResponseResult(
						array(
							$key_data
						)
			);
			$app->setResponseCode("200");
			return $app->getResponseData();
		}
	);

	// 권한 변경
	$app->post(
		"/chg_auth_role",
		function() use($app) {
			$account = $app->getHeaders()['X-Account-Sign'];
			$key_data = json_decode(file_get_contents("/workspace/yyw_report/conf/key.json"));
			$inputData = $app->getPostContent();
			$chg_auth = $inputData->auth;
			
			$return_data = array();
			// admin 혹은 setup 만 권한 변경 부여
			if( strcmp($account, "admin") === 0 || strcmp($account, "setup") === 0 ) {
				if( !isset($chg_auth) ) {
					$return_data["fail"] = "변경 ID가 없습니다.";
					
					$app->setResponseMessage("fail");
					$app->setResponseResult(
								$return_data
					);
					$app->setResponseCode("200");
					return $app->getResponseData();
				}
				if( isset($inputData->writable) ) {
					$key_data->auth->{$chg_auth}->writable = $inputData->writable;
				}
				if( isset($inputData->readable) ) {
					if ( strcmp($inputData->readable, "N") === 0 ) {
						$key_data->auth->{$chg_auth}->writable = "N";
					}
					
					$key_data->auth->{$chg_auth}->readable = $inputData->readable;
				}
				if( isset($inputData->visible) ) {
					if ( strcmp($inputData->visible, "N") === 0 ) {
						$key_data->auth->{$chg_auth}->writable = "N";
						$key_data->auth->{$chg_auth}->readable = "N";
					}
					
					$key_data->auth->{$chg_auth}->visible = $inputData->visible;
				}
				$return_data["success"] = "success";
				$return_data["result"] = "변경되었습니다.";
				$return_data["chg"] = $key_data;
			} else {
				$return_data["fail"] = "권한이 없습니다(" . $account . ")";
			}
			
			$app->setResponseMessage("ok");
			$app->setResponseResult(
						array(
							$return_data
						)
			);
			$app->setResponseCode("200");
			return $app->getResponseData();
		}
	);
	
	//error_log(var_export($key_data, true), 3, "/tmp/debug.txt");
	$app->handle();
?>