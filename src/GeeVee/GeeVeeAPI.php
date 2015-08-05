<?php

namespace GeeVee;

/**
 *  Allows you to send SMS to any US/Canada phone number using your google voice account
 *
 *  Author: Sam Battat hbattat@msn.com
 *          http://github.com/hbattat
 *
 *  License: This code is released under the MIT Open Source License. (Feel free to do whatever)
 *
 *  Last update: Jul 30 2015
 *
 * @package GeeVee
 * @author  Husam (Sam) Battat <hbattat@msn.com>
 */

class GeeVeeAPI {
	public $email;
	public $pass;
	private $cookies_file;
	private $init_url = 'https://www.google.com/voice/m';
	private $auth1_url = 'https://accounts.google.com/ServiceLogin?service=grandcentral&passive=1209600&continue=https://www.google.com/voice/m?initialauth&followup=https://www.google.com/voice/m?initialauth';
	private $action_url;
	private $gvx;
	private $all;

	public function __construct($email, $pass){
		$this->email = $email;
		$this->pass = $pass;
		$this->cookies_file = tempnam(sys_get_temp_dir(), 'GeeVee-cookies');
		require_once 'simple_html_dom.php';
		$this->login();
	}

	private function login(){
		$this->curlGet($this->init_url);
		$fields = $this->extractFormFields($this->auth1_url);
		$auth2_url = $this->action_url;
		$fields['Email'] = $this->email;
		$fields_str = $this->fieldsStr($fields);

		$login_step1_html = $this->curlPost($auth2_url, $fields_str, $this->auth1_url);

		$fields = $this->extractFormFields($login_step1_html, true);
		$auth3_url = $this->action_url;
		$fields['Passwd'] = $this->pass;
		$fields_str = $this->fieldsStr($fields);

		$login_step2_html = $this->curlPost($auth3_url, $fields_str, $auth2_url);

		$this->gvx = $this->getGVX();
	}

	public function getAllMessages($cached=false){
		if($cached){
			return $this->all;
		}
		else{
			$fields_str = json_encode(array('gvx' => $this->gvx));
			$inbox_url = 'https://www.google.com/voice/m/x?m=init&v=13';
                	$inbox_html = $this->curlPost($inbox_url, $fields_str, $this->init_url);
                	$inbox_html = preg_replace("/^\)]}',\n*/", '', $inbox_html);
			$all = json_decode($inbox_html, true);
			$this->all = $all;
			return $all;
		}
	}

	public function sendSMS($phone, $message){
		$phone = urlencode($phone);
		$message = urlencode($message);
		$send_url = 'https://www.google.com/voice/m/x?m=sms&n='.$phone.'&txt='.$message.'&v=13';
		$fields_str = json_encode(array('gvx' => $this->gvx));
		$send_html = $this->curlPost($send_url, $fields_str, $this->init_url);
		return $send_html;
	}

	public function markAsRead($conv_id){
		$mod_url = 'https://www.google.com/voice/m/x?m=mod&id='.$conv_id.'&rm=unread&v=13';
		$fields_str = json_encode(array('gvx' => $this->gvx));
                $read_html = $this->curlPost($mod_url, $fields_str, $this->init_url);
                return $read_html;
	}
	
	public function delete($conv_id){
		$delete_url = 'https://www.google.com/voice/m/x?m=mod&id='.$conv_id.'&add=trash&v=13';
		$fields_str = json_encode(array('gvx' => $this->gvx));
                $delete_html = $this->curlPost($delete_url, $fields_str, $this->init_url);
                return $delete_html;
	}

	public function getMessagesFrom($numbers=null, $cached=false)
	{
		$messages = array();
		if($cached){
			$all = $this->all;
		}
		else{
			$all = $this->getAllMessages()['conversations_response']['conversationgroup'];
		}
		$check_num = true;
		if(is_null($numbers)){
			$check_num = false;
		}
		elseif(!is_array($numbers)){
			$numbers = array($numbers);
		}

		foreach($all as $a){
			foreach($a['call'] as $msg){
				$phone_num = ltrim($msg['phone_number'], '+1');
				if($check_num){
					if(isset($msg['originator']) && $msg['originator'] != 0 && $msg['type'] = 10 && in_array($phone_num, $numbers) ){
						$messages[$phone_num][] = $msg;
					}
				}
				else{
					if(isset($msg['originator']) && $msg['originator'] != 0 && $msg['type'] = 10){
						$messages[$phone_num][] = $msg;
					}
				}
			}
		}
		return $messages;
	}

	private function getGVX(){
		$cookies = file_get_contents($this->cookies_file);
		preg_match('/gvx\s+(.*?)\s/i', $cookies, $match);
		return $match[1];
	}

	private function extractFormFields($form, $html=false){
		$fields = array();
		if(!$html){
			$form_html = $this->curlGet($form);
			$html_object = str_get_html($form_html);
		}
		else{
			$html_object = str_get_html($form);
		}
		$form_object = $html_object->find('form', 0);
		$this->action_url = $form_object->action;
		$fields = array();
		foreach($form_object->find('[name]') as $input){
			$fields[$input->name] = $input->value;
		}

		return $fields;
	}


	private function curlGet($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookies_file);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookies_file);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	private function curlPost($url, $fields_str, $referer = null, $json=false){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookies_file);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookies_file);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		if(!empty($referer)){
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POST, "application/x-www-form-urlencoded");
		if(!$json){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_str);
		}
		else{
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',                     
						'Content-Length: ' . strlen($fields_str))                                        );
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	} 

	private function fieldsStr($fields){
		$post_string = '';
		foreach($fields as $key => $value) {
			$post_string .= $key . '=' . urlencode($value) . '&';
		}
		$post_string = substr($post_string, 0, -1);
		return $post_string;
	}
}
