<?php

    /**
     * Wrapper for posting to Diaspora. Ugly as they have no API.
     */

    namespace IdnoPlugins\KnownDiaspora {

        class DiasporaAPI {

            static $_token_regex = '#content="(.*?)"\s+name="csrf-token#';


            private function _curl($url, $verb="get", $postfields=array(), $headers=array()) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                if ($verb == "post") {
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
                }
                if (!empty($headers)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $this->tmp_cookie_file);
                curl_setopt($ch, CURLOPT_COOKIEJAR, $this->tmp_cookie_file);
                $http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
                $content = curl_exec($ch);
                curl_close($ch);

                return array('status_code' => $http_status, 'text' => $content);
            }

            public function __construct($pod, $username, $password) {
                $this->pod = $pod;
                $data = array(
                    'user[username]' => $username,
                    'user[password]' => $password,
                    'authenticity_token' => $this->_fetch_token()
                );
                $response = $this->_curl($this->pod."/users/sign_in", "post", $data);
                if ($response['status_code'] != 302) {
                    throw new Exception("Invalid status code: ".$response['status_code']);
                }
                $this->tmp_cookie_file = tempnam(sys_get_temp_dir(), 'cookiejar');
            }

            public function __destruct() {
                unlink($this->tmp_cookie_file);
            }

            private function _fetch_token() {
                $response = $this->_curl($this->pod."/stream");
                $matches = array();
                preg_match(self::$_token_regex, $response['text'], $matches);
                return $matches[1];
            }

            public function post($text, $aspect_ids='public') {
                $data = json_encode(array(
                    'status_message' => array('text' => $text),
                    'aspect_ids' => $aspect_ids
                ));
                $headers = array(
                    'content-type' => 'application/json',
                    'accept' => 'application/json',
                    'x-csrf-token' => $this->_fetch_token()
                );
                $response = $this->_curl($this->pod."/status_messages", "post", $data, $headers);
                if ($response['status_code'] != 201) {
                    throw new Exception("Invalid status code: ".$response['status_code']);
                }
            }

        }

    }
