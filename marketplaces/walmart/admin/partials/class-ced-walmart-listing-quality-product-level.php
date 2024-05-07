<?php

class Ced_Walmart_Insights_Product_Level {

	public function __construct() {
	}

	public function ced_walmart_prepare_html_for_listing_quality( $products_id ) {

		$listing_data = get_post_meta( $products_id, 'ced_walmart_listing_quality_data', true );
		$listing_data = json_decode( $listing_data, true );

		if ( empty( $listing_data ) ) {
			return;
		}

		$product_name  = isset( $listing_data[0]['productName'] ) ? $listing_data[0]['productName'] : 'N/A';
		$product_sku   = isset( $listing_data[0]['sku'] ) ? $listing_data[0]['sku'] : 'N/A';
		$item_id       = isset( $listing_data[0]['itemId'] ) ? $listing_data[0]['itemId'] : 'N/A';
		$priority      = $this->ced_walmart_priority_badge_for_listing_quality( $listing_data[0]['priority'] );
		$product_type  = isset( $listing_data[0]['productType'] ) ? $listing_data[0]['productType'] : 'N/A';
		$stats         = $this->ced_walmart_stats_value_for_listing_quality( $listing_data[0]['stats'] );
		$max_ratings   = isset( $listing_data[0]['scoreDetails']['ratingReviews']['maxRating'] ) ? $listing_data[0]['scoreDetails']['ratingReviews']['maxRating'] : 0;
		$ratings_count = isset( $listing_data[0]['scoreDetails']['ratingReviews']['ratingCount'] ) ? $listing_data[0]['scoreDetails']['ratingReviews']['ratingCount'] : 0;

		$overall_quality_data      = isset( $listing_data[0]['qualityScoreData']['score'] ) ? $listing_data[0]['qualityScoreData']['score'] : 0;
		$quality_score_data        = $this->ced_walmart_prepare_qualitiy_score_data( $listing_data[0]['qualityScoreData']['values'] );
		$render_content_offer_html = $this->ced_walmart_render_content_offer_html( $listing_data[0]['scoreDetails'] );

		$html = '<div><div class="row mt-auto">
		<div class="col-7">
		<div class="row">
		<div class="col-7">
		<div class="card">
		<div class="card-body">
		<h5 class="card-title">' . $product_name . '</h5>
		<span class="pro-card-p text-muted">SKU : ' . $product_sku . '</span> |
		<span class="pro-card-p text-muted">Item ID : ' . $item_id . '</span>
		<span class="mx-1">' . $priority . '</span>
		</div>
		</div>
		</div>
		<div class="col-5">
		<div class="card">
		<div class="card-body text-center">
		<h5 class="card-title mt-2">Product Type</h5>
		<p class="pro-card-p">' . $product_type . '</p>
		</div>
		</div>
		</div>
		</div>

		<div class="row">
		<div class="col-7">
		<div class="card px-2">
		<div class="card-body">
		<h5 class="card-title">Your Stats at a Glance</h5>
		' . $stats . '
		</div>
		</div>
		</div>
		<div class="col-5">
		<div class="card">
		<div class="card-body text-center">
		<h6 class="card-title mt-1">Ratings & Reviews</h6>
		<p class="pro-card-p">Max Rating : ' . $max_ratings . '</p>
		<p class="pro-card-p">Rating Count : ' . $ratings_count . '</p>
		</div>
		</div>
		</div>
		</div>
		</div>
		<div class="col-5">
		<div class="card" style="height:85%">
		<div class="card-header">
		<h6 class="text-center">Listing Quality</h6>
		</div>
		<div class="card-body">
		<div class="product-progress-circle" data-value="' . round( $overall_quality_data, 2 ) . '">
		<span class="product-progress-circle-left">
		<span class="product-progress-circle-bar border-primary"></span>
		</span>
		<span class="product-progress-circle-right">
		<span class="product-progress-circle-bar border-primary"></span>
		</span>
		<div class="product-progress-circle-value w-100 h-100 rounded-circle d-flex align-items-center justify-content-center">
		<div class="h5 font-weight-bold">' . round( $overall_quality_data, 2 ) . '<sup class="small">%</sup></div>
		</div>
		</div>
		' . $quality_score_data . '
		</div>
		</div>
		</div>
		</div>
		' . $render_content_offer_html . '
		</div>
		';

		return $html;
	}


	public function ced_walmart_priority_badge_for_listing_quality( $priority = 'LOW' ) {
		$html = '';
		if ( 'HIGH' == $priority ) {
			$html = '<span class="badge rounded-pill bg-danger">High Priority</span>';
		} elseif ( 'MEDIUM' == $priority ) {
			$html = '<span class="badge rounded-pill bg-warning text-dark">Medium Priority</span>';

		} else {
			$html = '<span class="badge rounded-pill bg-success">Low Priority</span>';

		}

		return $html;
	}


	public function ced_walmart_stats_value_for_listing_quality( $stats = array() ) {
		$html  = '';
		$html .= '<div class="row">';

		if ( is_array( $stats ) && ! empty( $stats ) ) {
			foreach ( $stats as $key => $value ) {
				if ( is_array( $value ) ) {
					$html .= '<div class=col-sm col-3> <p>' . ucfirst( $key ) . '</p> <span>' . $value['amount'] . ' </span> <span>' . $value['currency'] . ' </span>  </div>';

				} else {
					$html .= '<div class=col-sm col-3> <p>' . ucfirst( $key ) . '</p> <span>' . $value . ' </span> </div>';

				}
			}
		}

		$html .= '</div>';

		return $html;
	}

	public function ced_walmart_prepare_qualitiy_score_data( $quality_score_data = array() ) {
		$html = '';
		$html = '<div class="container">

		<table class="table table-sm table-borderless">
		<thead>
		<th class="text-muted"> SCORE </th>
		<th class="text-muted"> TYPE </th>
		<th></th>
		<th class="text-muted"> IMPACT </th>
		</thead>
		<tbody>';

		if ( is_array( $quality_score_data ) && ! empty( $quality_score_data ) ) {
			foreach ( $quality_score_data as $key => $value ) {
				$impact = $value['impact'];

				if ( 'MEDIUM' == $impact ) {
					$color = 'text-info';
				} elseif ( 'HIGH' == $impact ) {
					$color = 'text-danger';
				} else {
					$color = 'text-success';
				}

				$html .= '<tr>
				<td class="fw-light"><span>' . $value['scoreValue'] . '%</span></td>
				<td colspan="2" class="fw-light">' . ucwords( $value['scoreType'] ) . '  </td>
				<td class="fw-light"><i class="fa fa-ellipsis-h ' . $color . ' fs-3 mx-3"></i></td>
				</tr>';
			}
		}
		$html .= '</tbody></table></div>';

		return $html;
	}

	public function ced_walmart_render_content_offer_html( $score_details = array() ) {

		$content_discoverability_issue_count = $this->ced_walmart_count_issue_for_content( $score_details['contentAndDiscoverability']['issues'] );
		$offer_issue_count                   = isset( $score_details['offer']['issueCount'] ) ? $score_details['offer']['issueCount'] : 0;
		$render_content_table                = $this->ced_walmart_render_content_table( $score_details['contentAndDiscoverability']['issues'] );
		$render_offer_table                  = $this->ced_walmart_render_offer_table( $score_details['offer'] );

		$html = '<div class="accordion accordion-flush" id="accordionFlushExample">
		<div class="accordion-item">
		<h2 class="accordion-header" id="flush-headingOne">
		<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
		CONTENT & DISCOVERABILITY <span class="text-danger mx-3">ISSUES(' . $content_discoverability_issue_count . ')</span>
		</button>
		</h2>
		<div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
		<div class="accordion-body">' . $render_content_table . '</div>
		</div>
		</div>
		<div class="accordion-item">
		<h2 class="accordion-header" id="flush-headingTwo">
		<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
		OFFER <span class="text-danger mx-3">ISSUES(' . $offer_issue_count . ')</span>
		</button>
		</h2>
		<div id="flush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#accordionFlushExample">
		<div class="accordion-body">' . $render_offer_table . '</div>
		</div>
		</div>
		</div>
		';

		return $html;
	}



	public function ced_walmart_count_issue_for_content( $data = array() ) {
		$count = 0;
		foreach ( $data as $key => $value ) {
			if ( 0 < $value['issueCount'] ) {
				$count++;
			}
		}
		return $count;
	}


	public function ced_walmart_render_content_table( $data = array() ) {

		$html = '<table class="table table-sm">
		<thead>
		<th> ATTRIBUTE </th>
		<th> ISSUES </th>
		<th> SCORE </th>
		</thead> 
		<tbody>';
		foreach ( $data as $key => $value ) {
			$html .= '<tr>
			<td style="width: 40%"><p class="fw-bolder">' . ucfirst( str_replace( '_', ' ', $value['attributeName'] ) ) . '</p>';
			if ( ! empty( $value['attributeValue'] ) ) {
				$html .= '<p>' . ( html_entity_decode( $value['attributeValue'] ) ) . '</p></td>';
			}
			$html .= '<td style="width: 40%">';
			if ( 0 < $value['issueCount'] && isset( $value['issues'] ) ) {
				if ( is_array( $value['issues'] ) ) {
					foreach ( $value['issues'] as $keyIssue => $valueIssue ) {
						$html .= '<p class="fw-bolder text-danger">' . str_replace( '_', ' ', $valueIssue['title'] ) . '</p>';
						$html .= '<p>' . $valueIssue['value'] . '</p>';
					}
				}
			} else {
				$html .= '<p class="fw-bolder"><i class="fa fa-check-circle" style="color:green;"" aria-hidden="true"></i> No Issues</p>';
			}
			$html .= '</td>';
			$html .= '<td style="width: 15%">' . $value['score'] . '%</td>';
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';

		return $html;
	}


	public function ced_walmart_render_offer_table( $data = array() ) {

		$html = '<table class="table table-sm">
		<thead>
		<th> ATTRIBUTE </th>
		<th> ISSUES </th>
		<th> SCORE </th>
		</thead> 
		<tbody>';
		foreach ( $data as $key => $value ) {
			if ( 'issueCount' == $key ) {
				continue;
			}

			$score = isset( $value['score'] ) ? $value['score'] : 0;

			$html .= '<tr>';
			$html .= '<td style="width: 40%"><p class="fw-bolder">' . ucfirst( str_replace( '_', ' ', $key ) ) . '</p>';
			$html .= '<td>';
			if ( 'shippingSpeed' == $key ) {

				$html .= '<p class="fw-bolder text-danger">' . str_replace( '_', ' ', $value['issueTitle'] ) . '</p>';
				$html .= '<p>' . $value['issueDesc'] . '</p>';
				$html .= '<p><i>Shipping Type </i> : ' . $value['shippingType'] . '</p>';

			} elseif ( 'price' == $key ) {
				$html .= '<p class="fw-bolder text-danger">' . str_replace( '_', ' ', $value['issueTitle'] ) . '</p>';
				$html .= '<p>' . $value['additionalDes'] . '</p>';
				$html .= '<p><i>Competitor Shipping </i> : ' . $value['competitorShipping'] . '</p>';
				$html .= '<p><i>Walmart Shipping </i> : ' . $value['walmartShipping'] . '</p>';
				$html .= '<p><i> Price : </i> ';
				foreach ( $value['price'] as $priceKey => $priceValue ) {
					$html .= ' ' . $priceValue;
				}
				$html .= ' </p>';

				if ( isset( $value['competitorPrice'] ) && ! empty( $value['competitorPrice']['amount'] ) ) {
					$html .= '<p><i> Competitor Price : </i> ';
					foreach ( $value['competitorPrice'] as $priceKey => $priceValue ) {
						$html .= ' ' . $priceValue;
					}
					$html .= ' </p>';
				}
			} else {

				$html .= '<p class="fw-bolder text-danger">' . str_replace( '_', ' ', $value['issueTitle'] ) . '</p>';

			}
			$html .= '</td>';
			$html .= '<td style="width: 15%">' . $score . '%</td>';
			$html .= '</tr>';

		}
		$html .= '</tbody></table>';

		return $html;
	}
}
