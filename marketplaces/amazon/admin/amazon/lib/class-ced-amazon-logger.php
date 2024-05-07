<?php
if (!class_exists('Class_Ced_Amazon_Logger')) {
	class Class_Ced_Amazon_Logger {

		public function __construct() {
			$upload_dir = wp_upload_dir()['basedir'];
			$folderpath = $upload_dir . '/ced-amazon-logger';
			if (!is_dir($folderpath)) {
				mkdir($folderpath, 0755);
			}
		}

		public function ced_add_log_response_serverless( $seller_id, $body, $topic, $timestamp, $http_code ) {

			$dateTime   = gmdate('d-m-Y H:i:s', $timestamp);
			$today_date = gmdate('d-m-Y');
			$upload_dir = wp_upload_dir()['basedir'];
			$folderpath = $upload_dir . '/ced-amazon-logger';
			$filename = $folderpath . '/amazon-serverless-logs-' . $today_date . '.txt';
			if (!file_exists($filename)) {
				$handle = fopen($filename, 'w');
				
			} else {
				$handle = fopen($filename, 'a');
			}

			if ( false !== $handle ) {
				$data = '================================================================================================' . PHP_EOL .
				'Time-->' . $dateTime . PHP_EOL . 'Seller ID-->' . $seller_id . PHP_EOL . 'Topic-->' . $topic . PHP_EOL .
				'HTTP_code-->' . $http_code . PHP_EOL . 'Response-->' . json_encode($body) . PHP_EOL .
				'================================================================================================';
				fwrite($handle, $data);
				fclose($handle);
			}
		}
	}
}
