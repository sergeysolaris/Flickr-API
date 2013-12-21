<?php
    class FlickrAPI {
    	private $api_key = 'your_api_key';
		private $api_secret = 'your_api_secret';
		private $api_token = 'your_api_token';
		
		
		function getPhotoWhithTag($tag) {
			$p = array(
				'method' => 'flickr.photos.search',
				'user_id' => 'me',
				'tags' => $tag
			);
			$xml = $this->apiCall($p);
			$res = new DOMDocument();
			$res->loadXML($xml);
			$photosArray = Array();
			$pid = "";
			
			$photos = $res->getElementsByTagName("photo");
			
			if (count($photos)>0) {
				$pid = $photos->item(0)->getAttribute('id');
			}
						
			return $pid;
		}
		
		function tokenIsSet() {
			if($this->api_token != '') {
				return true;
			} else {
				return false;
			}
		}
		
		function init() {
			if (!$this->tokenIsSet()) {
				if(isset($_GET['frob'])) {
					print_r($this->getAuthToken());
				}
			}
		}
		
		function getPhotoDate($id) {
			$p = array(
				'method' => 'flickr.photos.getInfo',
				'photo_id ' => $id
			);
			$xml = $this->apiCall($p);
			$res = new DOMDocument();
			$res->loadXML($xml);
			$photosArray = Array();
			$cnt = 0;
			$dt = '';
			
			$photos = $res->getElementsByTagName("dates");
			foreach ($photos as $photo) {
				$dt = $photo->getAttribute('posted');
			}
			return $dt;
		}
		
		function postPhoto() {

			$photos = $this->getHiddenPhotos();

			$curTime = time();
			$timeMinus = 0;

			if (count($photos) > 0) {
				
				$p = array(
						'method' => 'flickr.photos.setDates',
						'photo_id' => $photos[count($photos)-1]['id'],
						'date_posted' => $curTime
					);
					$xml = $this->apiCall($p);
				
				$p1 = array(
					'method' => 'flickr.photos.setPerms',
					'photo_id' => $photos[count($photos)-1]['id'],
					'is_public' => '1',
					'is_friend' => '0',
					'is_family' => '0',
					'perm_comment' => '3',
					'perm_addmeta' => '3',
				);
				$xml = $this->apiCall($p1);
				$this->addToGroups($photos[count($photos)-1]['id']);

			} else {
				$to      = 'your_email';
				$subject = 'Hey, update your Flickr stream';
				$message = 'We need more photos!!!';
				$headers = 'From: your_server' . "\r\n" .
					'Reply-To: your_server' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();

				mail($to, $subject, $message, $headers);
			}
		}
		
		function getHiddenPhotos() {
			$p = array(
				'method' => 'flickr.people.getPhotos',
				'user_id' => 'me',
				'privacy_filter' => '5'
			);
			$xml = $this->apiCall($p);
			$res = new DOMDocument();
			$res->loadXML($xml);
			$photosArray = Array();
			$cnt = 0;
			
			$photos = $res->getElementsByTagName("photo");
			foreach ($photos as $photo) {
				$photosArray[$cnt]['id'] = $photo->getAttribute('id');
				$photosArray[$cnt]['owner'] = $photo->getAttribute('owner');
				$photosArray[$cnt]['secret'] = $photo->getAttribute('secret');
				$photosArray[$cnt]['server'] = $photo->getAttribute('server');
				$photosArray[$cnt]['farm'] = $photo->getAttribute('farm');
				$photosArray[$cnt]['title'] = $photo->getAttribute('title');
				$photosArray[$cnt]['ispublic'] = $photo->getAttribute('ispublic');
				$photosArray[$cnt]['isfriend'] = $photo->getAttribute('isfriend');
				$photosArray[$cnt]['isfamily'] = $photo->getAttribute('isfamily');
				$cnt++;
				
			}
			return $photosArray;
		}
		
		function getAuthToken() {
			$p = array(
				'method' => 'flickr.auth.getToken',
				'frob' => $_GET['frob']
			);
			return $this->apiCall($p);
		}
		
		function apiCall($params) {
			
			$params['api_key'] = $this->api_key;
			
			$apiSig = $this->api_secret;
			
			if ($this->api_token != '') {
				$params['auth_token'] = $this->api_token;
			}
			
			ksort($params);
			
			foreach ($params as $key => $val) {
				$apiSig .= $key.$val;
			}
			
			$params['api_sig'] = md5($apiSig);
			
			$encoded_params = array();
			
			foreach ($params as $k => $v){ $encoded_params[] = urlencode($k).'='.urlencode($v); }

			$ch = curl_init();
			$timeout = 40;
			curl_setopt ($ch, CURLOPT_URL, 'http://api.flickr.com/services/rest/?'.implode('&', $encoded_params));
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$file_contents = curl_exec($ch);
			curl_close($ch);
			print_r($file_contents);
			return $file_contents;
			
		}
		
		
		function getAuthLink() {
			$authLink = "http://flickr.com/services/auth/?api_key=".$this->api_key."&perms=write&api_sig=".md5($this->api_secret."api_key".$this->api_key."permswrite");
			return $authLink;
		}
		
		
		function getGroups() {
			$p = array(
				'method' => 'flickr.groups.pools.getGroups'
			);
			$xml = $this->apiCall($p);
			$res = new DOMDocument();
			$res->loadXML($xml);
			$photosArray = Array();
			$cnt = 0;
			
			$photos = $res->getElementsByTagName("group");
			foreach ($photos as $photo) {
				$photosArray[$cnt]['id'] = $photo->getAttribute('id');
				$cnt++;
				
			}

			return $photosArray;
		}
		
		function getContexts($pid) {
			$p = array(
				'method' => 'flickr.photos.getAllContexts',
				'photo_id' => $pid
			);
			$xml = $this->apiCall($p);
			$res = new DOMDocument();
			$res->loadXML($xml);
			$photosArray = Array();
			$cnt = 0;
			
			$photos = $res->getElementsByTagName("pool");
			foreach ($photos as $photo) {
				$photosArray[$cnt]['id'] = $photo->getAttribute('id');
				$cnt++;
				
			}
			
			return $photosArray;
		}
			
		
		function getOldestPhotoWhithTag($tag) {
			$max_upload_date = time() - 25*3600;
			$p = array(
				'method' => 'flickr.photos.search',
				'user_id' => 'me',
				'tags' => $tag,
				'max_upload_date' => $max_upload_date,
				'sort' => 'date-posted-asc'
			);
			$xml = $this->apiCall($p);
			$res = new DOMDocument();
			$res->loadXML($xml);
			$photosArray = Array();
			$cnt = 0;
			
			$photos = $res->getElementsByTagName("photo");
			foreach ($photos as $photo) {
				$photosArray[$cnt]['id'] = $photo->getAttribute('id');
				$cnt++;
			}
			
			return $photosArray[0]['id'];
		}
		
		function removeTag($pid, $tag) {
			$p = array(
				'method' => 'flickr.photos.getInfo',
				'photo_id' => $pid
			);
			$xml = $this->apiCall($p);
			$res = new DOMDocument();
			$res->loadXML($xml);
			
			$tid = '';
			
			$photos = $res->getElementsByTagName("tag");
			foreach ($photos as $photo) {
				if ($photo->getAttribute('raw') == $tag)
					$tid = $photo->getAttribute('id');
				
			}
			
			if($tid != '') {
				$p1 = array(
					'method' => 'flickr.photos.removeTag',
					'tag_id' => $tid
				);
				$xml1 = $this->apiCall($p1);
			}
			
			//return $tid;
		}
		
		function addToGroups($pid) {
			$groups = Array('GROUP_ID_1', "GROUP_ID_2", ... );
			$random_groups = Array();
			
			while (count($random_groups)<4) {
				$rnd = rand(0, (count($groups)-1));
				if(!in_array($groups[$rnd], $random_groups)) {
					array_push($random_groups, $groups[$rnd]);
				}
			}
			
			$p = array(
				'method' => 'flickr.groups.pools.add',
				'photo_id' => $pid,
				'group_id' => '',
				
			);
			
			foreach ($random_groups as $group) {
				$p['group_id'] = $group;
				$this->apiCall($p);
				sleep(5);
			}
		}
		
		function addToAllGroups($pid) {
			$p = array(
				'method' => 'flickr.groups.pools.add',
				'photo_id' => $pid,
				'group_id' => '',
				
			);
			
			$groups = $this->getGroups();
			foreach ($groups as $group) {
				$p['group_id'] = $group['id'];
				$this->apiCall($p);
				sleep(5);
			}
		}
		
		function updateTime() {
			$newdate = time() - rand(10, 125);
			$p = array(
				'method' => 'flickr.photos.search',
				'user_id' => 'me'
			);
			$xml = $this->apiCall($p);
			$res = new DOMDocument();
			$res->loadXML($xml);
			$photosArray = Array();
			$cnt = 0;
			
			$photos = $res->getElementsByTagName("photo");
			foreach ($photos as $photo) {
				$photosArray[$cnt]['id'] = $photo->getAttribute('id');
				$cnt++;
			}
			
			$p1 = array(
				'method' => 'flickr.photos.setDates',
				'photo_id' => $photosArray[0]['id'],
				'date_posted' => $newdate
			);
			$xml1 = $this->apiCall($p1);
		}
	} 
?>