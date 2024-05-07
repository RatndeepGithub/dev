<?php



class Ced_Walmart_Insights {

	public $store_id;

	public function __construct() {

		$this->store_id = ced_walmart_get_current_active_store();
	}



	public function ced_walmart_pro_seller_badge_data() {

		$badge_data = array();

		$pro_seller_badge_data = get_option( 'ced_walmart_pro_seller_badge_details' . $this->store_id );
		if ( isset( $pro_seller_badge_data ) && ! empty( $pro_seller_badge_data ) ) {
			$pro_seller_badge_data = isset( $pro_seller_badge_data ) ? json_decode( $pro_seller_badge_data, 1 ) : array();
			$badge_since           = isset( $pro_seller_badge_data['badgedSince'] ) ? $pro_seller_badge_data['badgedSince'] : '';
			$defect_count          = 0;
			$healthy_count         = 0;
			$criteria_count        = 0;

			foreach ( $pro_seller_badge_data['meetsCriteria'] as $key => $value ) {
				if ( ! $value ) {
					++$defect_count;
				} else {
					++$healthy_count;
				}

				++$criteria_count;
			}

			$badge_data['badgedSince']            = $badge_since;
			$badge_data['criteriaCount']          = $criteria_count;
			$badge_data['defectCount']            = $defect_count;
			$badge_data['healthyCount']           = $healthy_count;
			$badge_data['healthyCountPercentage'] = $healthy_count * 100 / $criteria_count;
			$badge_data['meetsCriteria']          = $pro_seller_badge_data['meetsCriteria'];
			$badge_data['criteriaData']           = $pro_seller_badge_data['criteriaData'];
			$badge_data['recommendations']        = $pro_seller_badge_data['recommendations'];
			$badge_data['hasBadge']               = $pro_seller_badge_data['hasBadge'];
			$badge_data['isEligible']             = $pro_seller_badge_data['isEligible'];
			$badge_data['isProhibited']           = $pro_seller_badge_data['isProhibited'];
			$badge_data['badgeStatus']            = $pro_seller_badge_data['badgeStatus'];
		}

		return $badge_data;
	}

	public function ced_walmart_overall_listing_quality_data() {
		$quality_data_arr     = array();
		$listing_quality_data = get_option( 'ced_walmart_overall_listing_quality_details' . $this->store_id );
		$listing_quality_data = isset( $listing_quality_data ) ? json_decode( $listing_quality_data, 1 ) : array();

		$quality_data_arr['overAllQuality']    = isset( $listing_quality_data['payload']['listingQuality'] ) ? $listing_quality_data['payload']['listingQuality'] : 0;
		$quality_data_arr['offerScore']        = isset( $listing_quality_data['payload']['score']['offerScore'] ) ? $listing_quality_data['payload']['score']['offerScore'] : 0;
		$quality_data_arr['ratingReviewScore'] = isset( $listing_quality_data['payload']['score']['ratingReviewScore'] ) ? $listing_quality_data['payload']['score']['ratingReviewScore'] : 0;
		$quality_data_arr['contentScore']      = isset( $listing_quality_data['payload']['score']['contentScore'] ) ? $listing_quality_data['payload']['score']['contentScore'] : 0;
		$quality_data_arr['itemDefectCnt']     = isset( $listing_quality_data['payload']['postPurchaseQuality']['itemDefectCnt'] ) ? $listing_quality_data['payload']['postPurchaseQuality']['itemDefectCnt'] : 0;
		$quality_data_arr['defectRatio']       = isset( $listing_quality_data['payload']['postPurchaseQuality']['defectRatio'] ) ? $listing_quality_data['payload']['postPurchaseQuality']['defectRatio'] : 0;

		return $quality_data_arr;
	}



	public function ced_walmart_unpublished_counts() {
		$unpublished_item_count_data = get_option( 'ced_walmart_unpublished_items_counts' . $this->store_id );
		$unpublished_item_count_data = isset( $unpublished_item_count_data ) ? json_decode( $unpublished_item_count_data, 1 ) : array();

		return isset( $unpublished_item_count_data['payload'] ) ? $unpublished_item_count_data['payload'] : array();
	}
}
