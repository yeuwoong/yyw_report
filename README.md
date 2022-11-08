# yyw_report

To do List
 - apache rewrite module 실행
   - cmd : a2enmod rewrite
 - apache site-available 수정
   - vi /etc/apache2/sites-available/000-default.conf
   - DocumentRoot /workspace/yw_report
 - apache.conf 수정
   - <Directory /workspace/yyw_report/>
         Options Indexes FollowSymLinks
		 AllowOverride AllowOverride
		 Require all granted
     </Directory>
 - api 폴더 내부에 REST API 구성을 위한 .htaccess 구성
 - RBAC 를 위한 주 기능
   - 권한 변경 : 접근이 가능한 User 와 Role을 별도로 구분하여 권한별 접근 범위를 구분
                visible, readable, writable 으로 구분하여 파일의 접근 범위를 제한하도록 구현할 수 있음
 - API List
	"/test"				=> "[비인가] API 테스트",
	"/get_api_list"		=> "[비인가] api 리스트 전달",
	"/get_all_auth"		=> "[비인가] 전체 Auth 전달",
	"/auth_check"		=> "인가 API 테스트 ( key.json 의 내용을 기반으로 매칭되지 않는다면 api 사용 불가하도록 )",
	"/get_auth_info"	=> "header로 넘어온 User 사용 권한 체크",
	"/chg_auth_role"	=> "권한 변경"
 - REST API 무분별한 사용을 제한하기 위한 user authentcation check
 - 웹 페이지 구성(미흡)