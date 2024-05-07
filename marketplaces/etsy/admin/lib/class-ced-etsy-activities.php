<?php

/**
 * Class Etsy_Activities
 *
 * Represents an activity log for Etsy actions in WordPress.
 */
class Etsy_Activities {

	/**
	 * The action being performed.
	 *
	 * @var string
	 */
	public $action;

	/**
	 * The type of activity.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * The input payload for the activity.
	 *
	 * @var array
	 */
	public $input_payload;

	/**
	 * The response generated by the activity.
	 *
	 * @var array
	 */
	public $response;

	/**
	 * The ID of the post related to the activity.
	 *
	 * @var string
	 */
	public $post_id;

	/**
	 * The title of the post related to the activity.
	 *
	 * @var string
	 */
	public $post_title;

	/**
	 * Indicates whether the activity is automated.
	 *
	 * @var bool
	 */
	public $is_auto;

	/**
	 * The name of the Etsy shop associated with the activity.
	 *
	 * @var string
	 */
	public $shop_name;

	/**
	 * Etsy_Activities constructor.
	 */
	public function __construct() {
		$this->action        = '';
		$this->type          = '';
		$this->input_payload = array();
		$this->response      = array();
		$this->post_id       = '';
		$this->post_title    = '';
		$this->is_auto       = false;
		$this->shop_name     = '';
	}

	/**
	 * Executes the activity and logs it.
	 */
	public function execute() {
		$activity_log = get_option( 'ced_etsy_' . $this->type . '_logs_' . $this->shop_name, '' );
		if ( empty( $activity_log ) ) {
			$activity_log = array();
		} else {
			$activity_log = array_reverse( json_decode( $activity_log, true ) );
		}

		$activity_log[] = array(
			'time'          => date_i18n( 'F j, Y g:i a' ),
			'action'        => $this->action,
			'input_payload' => $this->input_payload,
			'response'      => $this->response,
			'post_id'       => $this->post_id,
			'post_title'    => $this->post_title,
			'is_auto'       => $this->is_auto,
		);

		$activity_log = array_slice( array_reverse( $activity_log ), 0, 1000 );

		update_option( 'ced_etsy_' . $this->type . '_logs_' . $this->shop_name, json_encode( $activity_log ) );
	}
}
