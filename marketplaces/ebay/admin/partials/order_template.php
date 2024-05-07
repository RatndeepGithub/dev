<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
global $post;

$order_id              = isset( $post->ID ) ? intval( $post->ID ) : '';
$umb_ebay_order_status = get_post_meta( $order_id, '_ebay_umb_order_status', true );

$merchant_order_id = get_post_meta( $order_id, '_ced_ebay_order_id', true );
$purchaseOrderId   = get_post_meta( $order_id, 'purchaseOrderId', true );
$fulfillment_node  = get_post_meta( $order_id, 'fulfillment_node', true );
$order_detail      = get_post_meta( $order_id, 'order_detail', true );
$order_item        = get_post_meta( $order_id, 'order_items', true );
if ( isset( $order_item[0] ) ) {
	$order_items = $order_item;
}

$number_items          = 0;
$umb_ebay_order_status = get_post_meta( $order_id, '_ebay_umb_order_status', true );
if ( empty( $umb_ebay_order_status ) ) {
	$umb_ebay_order_status = 'Acknowledged';
}
$umb_ebay_order_status = 'Acknowledged';


?>

<div id="umb_ebay_order_settings" class="panel woocommerce_options_panel">
<div class="ced_ebay_loader" class="loading-style-bg" style="display: none;">
	<img src="<?php echo esc_attr( CED_EBAY_URL ) . 'admin/images/loading.gif'; ?>">
</div>

		<div class="options_group umb_ebay_options">

				<input type="hidden" id="ebay_orderid" value="<?php echo esc_attr( $purchaseOrderId ); ?>" readonly>
			<input type="hidden" id="woocommerce_orderid" value="<?php echo esc_attr( $order_id ); ?>">


				<!-- Ship Complete Order -->
				<div id="ced_ebay_complete_order_shipping">
					<table class="wp-list-table widefat fixed striped">
					<tbody>
							<tr>
								<td><b><?php esc_attr_e( 'Reference Order Id on ebay', 'ebay-integration-for-woocommerce' ); ?></b></td>
								<td><?php echo esc_attr( $merchant_order_id ); ?></td>
							</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Tracking Number', 'ebay-integration-for-woocommerce' ); ?></b></td>
							<td><input type="text" id="umb_ebay_tracking_number" value=""></td>
						</tr>
						<tr>
						<td><b><?php esc_attr_e( 'Shipping Service', 'ebay-integration-for-woocommerce' ); ?></b></td>

								<td>
								<select name="ced_ebay_shipping_service_selected" id="ced_ebay_shipping_service_selected">
<option value="-1">Select Shipping Service</option>
<option value="4PX CHINA">4PX CHINA</option>
<option value="4PX Express">4PX Express</option>
<option value="4PX LTD">4PX LTD</option>
<option value="7LSP">7LSP</option>
<option value="A Duie Pyle">A Duie Pyle</option>
<option value="A J Express">A J Express</option>
<option value="A1 Courier Services">A1 Courier Services</option>
<option value="AAA Cooper">AAA Cooper</option>
<option value="AB Custom Group">AB Custom Group</option>
<option value="ABF">ABF</option>
<option value="ABX Express">ABX Express</option>
<option value="ACS Courier">ACS Courier</option>
<option value="AIR21">AIR21</option>
<option value="AIT Worldwvaluee">AIT Worldwvaluee"/option>
<option value="ALLIED EXPRESS">ALLIED EXPRESS</option>
<option value="AMWST">AMWST</option>
<option value="AO">AO</option>
<option value="APC Overnight Reference">APC Overnight Reference</option>
<option value="APC Overnight UK">APC Overnight UK</option>
<option value="APC Postal Logistics US">APC Postal Logistics US</option>
<option value="APG">APG</option>
<option value="APPLE EXPRESS">APPLE EXPRESS</option>
<option value="ARAMEX">ARAMEX</option>
<option value="ARVATO">ARVATO</option>
<option value="ASM">ASM</option>
<option value="AVRT">AVRT</option>
<option value="AXL Express &amp; Logistics">AXL Express &amp; Logistics</option>
<option value="Adicional Logistics">Adicional Logistics</option>
<option value="AeroPost">AeroPost</option>
<option value="Airpak Express">Airpak Express</option>
<option value="Airspeed International Corporation">Airspeed International Corporation</option>
<option value="AlphaFAST">AlphaFAST</option>
<option value="Amazon Logistics">Amazon Logistics</option>
<option value="An Post">An Post</option>
<option value="Aprisa Express">Aprisa Express</option>
<option value="Aquiline">Aquiline</option>
<option value="Aramex Australia">Aramex Australia</option>
<option value="Arrow XL">Arrow XL</option>
<option value="Asendia Germany">Asendia Germany</option>
<option value="Asendia UK">Asendia UK</option>
<option value="Asendia USA">Asendia USA</option>
<option value="Australia Post">Australia Post</option>
<option value="Australian Air Express">Australian Air Express</option>
<option value="Austrian Post Registered">Austrian Post Registered</option>
<option value="B Post">B Post</option>
<option value="B2C Europe">B2C Europe</option>
<option value="BELGIAN POST">BELGIAN POST</option>
<option value="BKNS">BKNS</option>
<option value="BUYER - EBAY">BUYER - EBAY</option>
<option value="Bartolini">Bartolini</option>
<option value="Belgium Post">Belgium Post</option>
<option value="Belpost">Belpost</option>
<option value="Bert Transport">Bert Transport</option>
<option value="Best Overnite">Best Overnite</option>
<option value="Best Way Parcel">Best Way Parcel</option>
<option value="BirdSystem">BirdSystem</option>
<option value="Blue Package">Blue Package</option>
<option value="Bluecare Express">Bluecare Express</option>
<option value="Bluedart">Bluedart</option>
<option value="Bombino Express Pvt Ltd">Bombino Express Pvt Ltd</option>
<option value="Bonds Couriers">Bonds Couriers</option>
<option value="Border Express">Border Express</option>
<option value="Boxberry">Boxberry</option>
<option value="Boxc">Boxc</option>
<option value="Brazil Correios">Brazil Correios</option>
<option value="Brokers World Wvaluee">Brokers World Wvaluee"/option>
<option value="Bulgarian Posts">Bulgarian Posts</option>
<option value="BusinessPost">BusinessPost</option>
<option value="Buylogic">Buylogic</option>
<option value="CBL Logistics">CBL Logistics</option>
<option value="CENF">CENF</option>
<option value="CEVA">CEVA</option>
<option value="CH Robinson">CH Robinson</option>
<option value="CHUKOU1">CHUKOU1</option>
<option value="CHUKOU1_EXPRESS">CHUKOU1_EXPRESS</option>
<option value="CJ GLS Korea">CJ GLS Korea</option>
<option value="CJ Korea Express Thailand">CJ Korea Express Thailand</option>
<option value="CNWY">CNWY</option>
<option value="CTT">CTT</option>
<option value="Cambodia Post">Cambodia Post</option>
<option value="CanPar">CanPar</option>
<option value="Canada Post">Canada Post</option>
<option value="Capital Transport">Capital Transport</option>
<option value="Caribou">Caribou</option>
<option value="Central Arizona Freight">Central Arizona Freight</option>
<option value="Central Transport">Central Transport</option>
<option value="Century Express Courier Services">Century Express Courier Services</option>
<option value="Ceska Posta">Ceska Posta</option>
<option value="China Post">China Post</option>
<option value="Chit Chats Express">Chit Chats Express</option>
<option value="Chronoexpres">Chronoexpres</option>
<option value="Chronopost">Chronopost</option>
<option value="Chronopost Portugal">Chronopost Portugal</option>
<option value="Chunghwa Post">Chunghwa Post</option>
<option value="CitiPost">CitiPost</option>
<option value="City Link">City Link</option>
<option value="City Link Express">City Link Express</option>
<option value="Click and Quick">Click and Quick</option>
<option value="Coles">Coles</option>
<option value="Coliposte Domestic">Coliposte Domestic</option>
<option value="Coliposte International">Coliposte International</option>
<option value="Colis Prive">Colis Prive</option>
<option value="Colissimo">Colissimo</option>
<option value="CollectCo">CollectCo</option>
<option value="CollectPlus">CollectPlus</option>
<option value="Conway Freight">Conway Freight</option>
<option value="Copa Airlines Courier">Copa Airlines Courier</option>
<option value="Copa Courier">Copa Courier</option>
<option value="Correo Argentino">Correo Argentino</option>
<option value="Correos">Correos</option>
<option value="Correos Chile">Correos Chile</option>
<option value="Correos Express">Correos Express</option>
<option value="Correos de Costa Rica">Correos de Costa Rica</option>
<option value="Correos de Mexico">Correos de Mexico</option>
<option value="Courex">Courex</option>
<option value="Courier IT">Courier IT</option>
<option value="Courier Plus">Courier Plus</option>
<option value="Courier Post">Courier Post</option>
<option value="Couriers Please">Couriers Please</option>
<option value="Coyote">Coyote</option>
<option value="Cyprus Post">Cyprus Post</option>
<option value="DAI Post">DAI Post</option>
<option value="DATS">DATS</option>
<option value="DB Schenker">DB Schenker</option>
<option value="DB Schenker Sweden">DB Schenker Sweden</option>
<option value="DD Express Courier">DD Express Courier</option>
<option value="DHE">DHE</option>
<option value="DHEKEN">DHEKEN</option>
<option value="DHL">DHL</option>
<option value="DHL 2 Mann">DHL 2 Mann</option>
<option value="DHL Active Tracing">DHL Active Tracing</option>
<option value="DHL Benelux">DHL Benelux</option>
<option value="DHL EXPRESS">DHL EXPRESS</option>
<option value="DHL Express">DHL Express</option>
<option value="DHL Express Piecevalue""DHL Express Piecevalue<"option>
<option value="DHL Global Forwarding">DHL Global Forwarding</option>
<option value="DHL Global Mail">DHL Global Mail</option>
<option value="DHL Global Mail Americas">DHL Global Mail Americas</option>
<option value="DHL Netherlands">DHL Netherlands</option>
<option value="DHL Parcel NL">DHL Parcel NL</option>
<option value="DHL Poland Domestic">DHL Poland Domestic</option>
<option value="DHL SC AU">DHL SC AU</option>
<option value="DHL Spain Domestic">DHL Spain Domestic</option>
<option value="DHLEKB">DHLEKB</option>
<option value="DHLG">DHLG</option>
<option value="DMM Network">DMM Network</option>
<option value="DPD">DPD</option>
<option value="DPD Ireland">DPD Ireland</option>
<option value="DPD Poland">DPD Poland</option>
<option value="DPD Romania">DPD Romania</option>
<option value="DPE South Africa">DPE South Africa</option>
<option value="DPEX">DPEX</option>
<option value="DPX Thailand">DPX Thailand</option>
<option value="DSV">DSV</option>
<option value="DTDC Australia">DTDC Australia</option>
<option value="DTDC Express Ltd">DTDC Express Ltd</option>
<option value="DTDC India">DTDC India</option>
<option value="DTS">DTS</option>
<option value="DX">DX</option>
<option value="DX Freight">DX Freight</option>
<option value="Dawn Wing">Dawn Wing</option>
<option value="Day and Ross">Day and Ross</option>
<option value="Daylight Transport">Daylight Transport</option>
<option value="Dayton Freight Lines">Dayton Freight Lines</option>
<option value="Delcart">Delcart</option>
<option value="Delhivery">Delhivery</option>
<option value="Deltec Courier">Deltec Courier</option>
<option value="Demand Ship">Demand Ship</option>
<option value="Detrack">Detrack</option>
<option value="Deutsche Post">Deutsche Post</option>
<option value="Diamond Line">Diamond Line</option>
<option value="Die Schweizerische Post">Die Schweizerische Post</option>
<option value="Direct Couriers">Direct Couriers</option>
<option value="Direct Freight Express">Direct Freight Express</option>
<option value="Direct Link">Direct Link</option>
<option value="Directlog">Directlog</option>
<option value="Dohrn">Dohrn</option>
<option value="Doora Logistics">Doora Logistics</option>
<option value="Dotzot">Dotzot</option>
<option value="Ducros">Ducros</option>
<option value="Dynalogic Benelux BV">Dynalogic Benelux BV</option>
<option value="Dynamic Logistics">Dynamic Logistics</option>
<option value="E GO">E GO</option>
<option value="ECMS International Logistics">ECMS International Logistics</option>
<option value="EDI Express">EDI Express</option>
<option value="EFS Fulfillment Service">EFS Fulfillment Service</option>
<option value="ELTA Hellenic Post">ELTA Hellenic Post</option>
<option value="EMF">EMF</option>
<option value="EZship">EZship</option>
<option value="Easy Mail">Easy Mail</option>
<option value="Ecargo">Ecargo</option>
<option value="Echo">Echo</option>
<option value="Ecom Express">Ecom Express</option>
<option value="Ekart">Ekart</option>
<option value="Email Delivery">Email Delivery</option>
<option value="Emirates Post">Emirates Post</option>
<option value="Endeavour Delivery">Endeavour Delivery</option>
<option value="Ensenda">Ensenda</option>
<option value="Enterprise des Poste Laos">Enterprise des Poste Laos</option>
<option value="Envialia">Envialia</option>
<option value="Epic Freight">Epic Freight</option>
<option value="Estafeta">Estafeta</option>
<option value="Estes">Estes</option>
<option value="Eurodis">Eurodis</option>
<option value="Exapaq">Exapaq</option>
<option value="Expeditors">Expeditors</option>
<option value="FAR International">FAR International</option>
<option value="FASTWAY COURIERS">FASTWAY COURIERS</option>
<option value="FDXCP">FDXCP</option>
<option value="FERCAM Logistics &amp; Transport">FERCAM Logistics &amp; Transport</option>
<option value="FLYT">FLYT</option>
<option value="FLYT Express">FLYT Express</option>
<option value="FTFT">FTFT</option>
<option value="Fastrak Services">Fastrak Services</option>
<option value="Fastway">Fastway</option>
<option value="Fastway Australia">Fastway Australia</option>
<option value="Fastway Ireland">Fastway Ireland</option>
<option value="Fastway New Zealand">Fastway New Zealand</option>
<option value="Fastway South Africa">Fastway South Africa</option>
<option value="FedEx">FedEx</option>
<option value="FedEx Poland Domestic">FedEx Poland Domestic</option>
<option value="FedEx Smart Post">FedEx Smart Post</option>
<option value="Fiege">Fiege</option>
<option value="First Flight Couriers">First Flight Couriers</option>
<option value="First Logistics">First Logistics</option>
<option value="FlytExpress US Direct line">FlytExpress US Direct line</option>
<option value="Forward Air">Forward Air</option>
<option value="FulfilExpress-AccStation">FulfilExpress-AccStation</option>
<option value="FulfilExpress-EverydaySource">FulfilExpress-EverydaySource</option>
<option value="FulfilExpress-eForCity">FulfilExpress-eForCity</option>
<option value="FulfilExpress-iTrimming">FulfilExpress-iTrimming</option>
<option value="GDEX">GDEX</option>
<option value="GEODIS - Distribution &amp; Express">GEODIS - Distribution &amp; Express</option>
<option value="GLS">GLS</option>
<option value="GLS Italy">GLS Italy</option>
<option value="GLS Netherlands">GLS Netherlands</option>
<option value="GSI EXPRESS">GSI EXPRESS</option>
<option value="GSO">GSO</option>
<option value="Gati KWE">Gati KWE</option>
<option value="Gel Express">Gel Express</option>
<option value="General Overnight">General Overnight</option>
<option value="Geniki Taxydromiki">Geniki Taxydromiki</option>
<option value="Geodis Espace">Geodis Espace</option>
<option value="Giao Hang Nhanh">Giao Hang Nhanh</option>
<option value="Global Tranz">Global Tranz</option>
<option value="Globe Logistics">Globe Logistics</option>
<option value="Globegistics">Globegistics</option>
<option value="GoJavas">GoJavas</option>
<option value="Gofly">Gofly</option>
<option value="Greyhound">Greyhound</option>
<option value="HDUSA">HDUSA</option>
<option value="HUNTER EXPRESS">HUNTER EXPRESS</option>
<option value="Havaluea"bao">Havaluea"bao</option>
<option value="Hercules">Hercules</option>
<option value="Hermes">Hermes</option>
<option value="Hermes Italy">Hermes Italy</option>
<option value="Holisol">Holisol</option>
<option value="Holland">Holland</option>
<option value="Home Delivery Network">Home Delivery Network</option>
<option value="Homedirect Logistics">Homedirect Logistics</option>
<option value="Hong Kong Post">Hong Kong Post</option>
<option value="Hrvatska Posta">Hrvatska Posta</option>
<option value="valueS"Logistics">valueS"Logistics</option>
<option value="IMEX Global Solutions">IMEX Global Solutions</option>
<option value="IML">IML</option>
<option value="IMX Mail">IMX Mail</option>
<option value="Iceland Post">Iceland Post</option>
<option value="In Post">In Post</option>
<option value="InPost">InPost</option>
<option value="InPost Paczkomaty">InPost Paczkomaty</option>
<option value="India Post">India Post</option>
<option value="India Post Domestic">India Post Domestic</option>
<option value="India Post International">India Post International</option>
<option value="Indonesia Post">Indonesia Post</option>
<option value="Innovel">Innovel</option>
<option value="Instand Tion Nam Express">Instand Tion Nam Express</option>
<option value="InterPost">InterPost</option>
<option value="Interlink">Interlink</option>
<option value="Interlink Express">Interlink Express</option>
<option value="Interlink Express Reference">Interlink Express Reference</option>
<option value="International Seur">International Seur</option>
<option value="Interparcel Australia">Interparcel Australia</option>
<option value="Interparcel New Zealand">Interparcel New Zealand</option>
<option value="Interparcel UK">Interparcel UK</option>
<option value="IoInvio">IoInvio</option>
<option value="Israel Post">Israel Post</option>
<option value="Israel Post Domestic">Israel Post Domestic</option>
<option value="JEX Jayon Express">JEX Jayon Express</option>
<option value="JNE">JNE</option>
<option value="JP BH Posta">JP BH Posta</option>
<option value="JX">JX</option>
<option value="Jam Express">Jam Express</option>
<option value="Japan Post">Japan Post</option>
<option value="Jersey Post">Jersey Post</option>
<option value="Jet Ship Worldwvaluee">Jet Ship Worldwvaluee"/option>
<option value="Jocom">Jocom</option>
<option value="KGM Hub">KGM Hub</option>
<option value="KIALA">KIALA</option>
<option value="KWT Logistics">KWT Logistics</option>
<option value="Kangaroo Worldwvaluee"Express">Kangaroo Worldwvaluee"Express</option>
<option value="Kerry Express Thailand">Kerry Express Thailand</option>
<option value="Kerry TTC Express">Kerry TTC Express</option>
<option value="Korea Post">Korea Post</option>
<option value="Kuehne Nagel">Kuehne Nagel</option>
<option value="Kurasi">Kurasi</option>
<option value="LA POSTE">LA POSTE</option>
<option value="LBC Express">LBC Express</option>
<option value="LDSO">LDSO</option>
<option value="LTL">LTL</option>
<option value="Landmark">Landmark</option>
<option value="Landmark Global">Landmark Global</option>
<option value="Lao Post">Lao Post</option>
<option value="Lasership">Lasership</option>
<option value="Latvia Post">Latvia Post</option>
<option value="LexShip">LexShip</option>
<option value="Liccardi Express Courier">Liccardi Express Courier</option>
<option value="Lietuvos Pastas">Lietuvos Pastas</option>
<option value="Line Clear Express &amp; Logistics Sdn Bhd">Line Clear Express &amp; Logistics Sdn Bhd</option>
<option value="Lion Parcel">Lion Parcel</option>
<option value="Logwin Logistics">Logwin Logistics</option>
<option value="LoneStar Overnight">LoneStar Overnight</option>
<option value="Lonestar Overnight">Lonestar Overnight</option>
<option value="M Xpress">M Xpress</option>
<option value="MALAYSIA POST">MALAYSIA POST</option>
<option value="MDS Collivery">MDS Collivery</option>
<option value="MNG Turkey">MNG Turkey</option>
<option value="MRW">MRW</option>
<option value="MRW1">MRW1</option>
<option value="MSI">MSI</option>
<option value="MUDITA">MUDITA</option>
<option value="Magyar Posta">Magyar Posta</option>
<option value="MailAmericas">MailAmericas</option>
<option value="Main Freight">Main Freight</option>
<option value="Malaysia Post EMS">Malaysia Post EMS</option>
<option value="Malaysia Post Registered">Malaysia Post Registered</option>
<option value="Manna Freight">Manna Freight</option>
<option value="Mara Xpress">Mara Xpress</option>
<option value="Matdespatch">Matdespatch</option>
<option value="Matkahuolto">Matkahuolto</option>
<option value="Maxcellents Pte Ltd">Maxcellents Pte Ltd</option>
<option value="Metapack">Metapack</option>
<option value="Mexico AeroFlash">Mexico AeroFlash</option>
<option value="Mexico Redpack">Mexico Redpack</option>
<option value="Mexico Senda Express">Mexico Senda Express</option>
<option value="Mikropakket">Mikropakket</option>
<option value="Mondial Relay">Mondial Relay</option>
<option value="Mypostonline">Mypostonline</option>
<option value="NACEX">NACEX</option>
<option value="NEMF">NEMF</option>
<option value="NZ Post">NZ Post</option>
<option value="Nationwvaluee"Express">Nationwvaluee"Express</option>
<option value="New Zealand Post">New Zealand Post</option>
<option value="NewPenn">NewPenn</option>
<option value="Newgistics">Newgistics</option>
<option value="Nexive">Nexive</option>
<option value="Nhans Solutions">Nhans Solutions</option>
<option value="NiPost">NiPost</option>
<option value="Nightline">Nightline</option>
<option value="Nim Express">Nim Express</option>
<option value="Ninja Van">Ninja Van</option>
<option value="Ninja Van Indonesia">Ninja Van Indonesia</option>
<option value="Ninja Van Malaysia">Ninja Van Malaysia</option>
<option value="Ninja Van Philippines">Ninja Van Philippines</option>
<option value="Ninja Van Thailand">Ninja Van Thailand</option>
<option value="Norsk Global">Norsk Global</option>
<option value="Nova Poshta">Nova Poshta</option>
<option value="Nova Poshta International">Nova Poshta International</option>
<option value="NuvoEx">NuvoEx</option>
<option value="OCA Argentina">OCA Argentina</option>
<option value="OCS ANA">OCS ANA</option>
<option value="OCS International">OCS International</option>
<option value="ODFL">ODFL</option>
<option value="OFFD">OFFD</option>
<option value="OTHER">OTHER</option>
<option value="OVNT">OVNT</option>
<option value="Oak Harbor">Oak Harbor</option>
<option value="Old Dominion Freight Line">Old Dominion Freight Line</option>
<option value="Omni Logistics">Omni Logistics</option>
<option value="Omniva">Omniva</option>
<option value="OnTrac">OnTrac</option>
<option value="One World Express">One World Express</option>
<option value="Orange Connex">Orange Connex</option>
<option value="PAL Express Limited">PAL Express Limited</option>
<option value="PBCB">PBCB</option>
<option value="PBI">PBI</option>
<option value="PBI_UK">PBI_UK</option>
<option value="PF Logistics">PF Logistics</option>
<option value="PITD">PITD</option>
<option value="POST ITALIANO">POST ITALIANO</option>
<option value="PTT Posta">PTT Posta</option>
<option value="Packlink">Packlink</option>
<option value="Pandu Logistics">Pandu Logistics</option>
<option value="Panther">Panther</option>
<option value="Panther Order Number">Panther Order Number</option>
<option value="Panther Reference">Panther Reference</option>
<option value="Paquetexpress">Paquetexpress</option>
<option value="Parcel Express">Parcel Express</option>
<option value="Parcel One">Parcel One</option>
<option value="Parcel Point">Parcel Point</option>
<option value="Parcel Pool">Parcel Pool</option>
<option value="Parcel Post Singapore">Parcel Post Singapore</option>
<option value="Parcel2Go">Parcel2Go</option>
<option value="ParcelForce">ParcelForce</option>
<option value="Parcelled In">Parcelled In</option>
<option value="Peninsula">Peninsula</option>
<option value="Philpost">Philpost</option>
<option value="Pilot">Pilot</option>
<option value="Pilot Freight Services">Pilot Freight Services</option>
<option value="Pitt Ohio Trucking">Pitt Ohio Trucking</option>
<option value="Poczta Polska">Poczta Polska</option>
<option value="Pocztex">Pocztex</option>
<option value="Portugal CTT">Portugal CTT</option>
<option value="Portugal Seur">Portugal Seur</option>
<option value="Pos Indonesia Domestic">Pos Indonesia Domestic</option>
<option value="Pos Indonesia Intl">Pos Indonesia Intl</option>
<option value="Post Danmark">Post Danmark</option>
<option value="Post NL">Post NL</option>
<option value="Post Nord Norway">Post Nord Norway</option>
<option value="Post Serbia">Post Serbia</option>
<option value="PostNL Domestic">PostNL Domestic</option>
<option value="PostNL International">PostNL International</option>
<option value="PostNL International 3S">PostNL International 3S</option>
<option value="PostNord Logistics">PostNord Logistics</option>
<option value="Posta Romana">Posta Romana</option>
<option value="Poste Italiane">Poste Italiane</option>
<option value="Poste Italiane Paccocelere">Poste Italiane Paccocelere</option>
<option value="Posten Norge">Posten Norge</option>
<option value="Posti">Posti</option>
<option value="Postur Is">Postur Is</option>
<option value="Prestige">Prestige</option>
<option value="Priority1">Priority1</option>
<option value="Professional Couriers">Professional Couriers</option>
<option value="Purolator">Purolator</option>
<option value="Quantium Solutions">Quantium Solutions</option>
<option value="Qxpress">Qxpress</option>
<option value="RAF Philippines">RAF Philippines</option>
<option value="RAM">RAM</option>
<option value="RETL">RETL</option>
<option value="RL Carriers">RL Carriers</option>
<option value="RPD2man Deliveries">RPD2man Deliveries</option>
<option value="RPX Indonesia">RPX Indonesia</option>
<option value="RR Donnelley">RR Donnelley</option>
<option value="RRUN">RRUN</option>
<option value="RZY Express">RZY Express</option>
<option value="Raben Group">Raben Group</option>
<option value="Ravaluee"eX">Ravaluee"eX</option>
<option value="Red Carpet Logistics">Red Carpet Logistics</option>
<option value="Red Express">Red Express</option>
<option value="Red Express Waybill">Red Express Waybill</option>
<option value="Redur Spain">Redur Spain</option>
<option value="Rincos">Rincos</option>
<option value="Rist Transport">Rist Transport</option>
<option value="Roadbull Logistics">Roadbull Logistics</option>
<option value="Rocket Parcel International">Rocket Parcel International</option>
<option value="Royal Mail">Royal Mail</option>
<option value="Royal Shipments">Royal Shipments</option>
<option value="Russian Post">Russian Post</option>
<option value="SAIA">SAIA</option>
<option value="SAIA LTL Freight">SAIA LTL Freight</option>
<option value="SAILPOST">SAILPOST</option>
<option value="SDA">SDA</option>
<option value="SEKO">SEKO</option>
<option value="SF EXPESS">SF EXPESS</option>
<option value="SFC">SFC</option>
<option value="SFC Express">SFC Express</option>
<option value="SFC_EXPRESS">SFC_EXPRESS</option>
<option value="SGT Corriere Espresso">SGT Corriere Espresso</option>
<option value="SHREE TIRUPATI COURIER SERVICES">SHREE TIRUPATI COURIER SERVICES</option>
<option value="SINGAPORE POST">SINGAPORE POST</option>
<option value="SKYBOX">SKYBOX</option>
<option value="SMART SEND">SMART SEND</option>
<option value="SMSA Express">SMSA Express</option>
<option value="SPRING">SPRING</option>
<option value="SRE Korea">SRE Korea</option>
<option value="Safexpress">Safexpress</option>
<option value="Sagawa">Sagawa</option>
<option value="Saudi Post">Saudi Post</option>
<option value="Scudex Express">Scudex Express</option>
<option value="Sending">Sending</option>
<option value="Sendit">Sendit</option>
<option value="Sendle">Sendle</option>
<option value="Seur">Seur</option>
<option value="Shippit">Shippit</option>
<option value="Shunyou Post">Shunyou Post</option>
<option value="SimplyPost">SimplyPost</option>
<option value="Singapore Speedpost">Singapore Speedpost</option>
<option value="Siodemka">Siodemka</option>
<option value="Sioli and Fontana">Sioli and Fontana</option>
<option value="Siódemka">Siódemka</option>
<option value="SkyNet Worldwvaluee">SkyNet Worldwvaluee"/option>
<option value="SkyNet Worldwvaluee"Express">SkyNet Worldwvaluee"Express</option>
<option value="SkyNet Worldwvaluee"Express UAE">SkyNet Worldwvaluee"Express UAE</option>
<option value="Skynet Malaysia">Skynet Malaysia</option>
<option value="Skynet Worldwvaluee"Express UK">Skynet Worldwvaluee"Express UK</option>
<option value="Sogetras">Sogetras</option>
<option value="SortHub">SortHub</option>
<option value="South African Post Office">South African Post Office</option>
<option value="Southeastern Freight Lines">Southeastern Freight Lines</option>
<option value="optionish Seur">optionish Seur</option>
<option value="Specialised Freight">Specialised Freight</option>
<option value="Spediamo">Spediamo</option>
<option value="SpeeDee">SpeeDee</option>
<option value="Speed Couriers">Speed Couriers</option>
<option value="SpeedPAK">SpeedPAK</option>
<option value="Speedex Courier">Speedex Courier</option>
<option value="Spring GDS">Spring GDS</option>
<option value="Star Track Courier">Star Track Courier</option>
<option value="StarTrack">StarTrack</option>
<option value="Suntek Express LTD">Suntek Express LTD</option>
<option value="Sweden Posten">Sweden Posten</option>
<option value="Swiss Post">Swiss Post</option>
<option value="TAQBIN Malaysia">TAQBIN Malaysia</option>
<option value="TAQBIN Singapore">TAQBIN Singapore</option>
<option value="TCS">TCS</option>
<option value="TELE">TELE</option>
<option value="THAILAND POST">THAILAND POST</option>
<option value="TIPSA">TIPSA</option>
<option value="TN Italy">TN Italy</option>
<option value="TNT">TNT</option>
<option value="TNT Australia">TNT Australia</option>
<option value="TNT Click Italy">TNT Click Italy</option>
<option value="TNT EXPRESS">TNT EXPRESS</option>
<option value="TNT France">TNT France</option>
<option value="TNT Italy">TNT Italy</option>
<option value="TNT Post">TNT Post</option>
<option value="TNT Post Italy Nexive">TNT Post Italy Nexive</option>
<option value="TNT Reference">TNT Reference</option>
<option value="TNT UK">TNT UK</option>
<option value="TNT UK Reference">TNT UK Reference</option>
<option value="TPG">TPG</option>
<option value="TWW">TWW</option>
<option value="Taxy Dromiki">Taxy Dromiki</option>
<option value="Teliway SIC Express">Teliway SIC Express</option>
<option value="Temando">Temando</option>
<option value="The Courier Guy">The Courier Guy</option>
<option value="The Custom Companies">The Custom Companies</option>
<option value="Tiki">Tiki</option>
<option value="Toll">Toll</option>
<option value="Toll IPEC">Toll IPEC</option>
<option value="Toll Priority">Toll Priority</option>
<option value="Topyou">Topyou</option>
<option value="TrakPak">TrakPak</option>
<option value="TransMission">TransMission</option>
<option value="Tuffnells Parcels Express">Tuffnells Parcels Express</option>
<option value="UBI">UBI</option>
<option value="UK Mail">UK Mail</option>
<option value="UPS">UPS</option>
<option value="UPS Mail Innovations">UPS Mail Innovations</option>
<option value="UPSC">UPSC</option>
<option value="USFG">USFG</option>
<option value="USPS">USPS</option>
<option value="USPS CeP">USPS CeP</option>
<option value="USPS PMI">USPS PMI</option>
<option value="Ukrposhta">Ukrposhta</option>
<option value="Unishippers">Unishippers</option>
<option value="United Delivery Service">United Delivery Service</option>
<option value="VITR">VITR</option>
<option value="Veritiv">Veritiv</option>
<option value="VicTas Freight Express">VicTas Freight Express</option>
<option value="Vietnam Post">Vietnam Post</option>
<option value="Vietnam Post EMS">Vietnam Post EMS</option>
<option value="ViettelPost">ViettelPost</option>
<option value="Vision Express">Vision Express</option>
<option value="WATKINS">WATKINS</option>
<option value="WISE">WISE</option>
<option value="WNDirect">WNDirect</option>
<option value="WNdirect">WNdirect</option>
<option value="WPX">WPX</option>
<option value="Wahana">Wahana</option>
<option value="WanSe">WanSe</option>
<option value="Wanb Express">Wanb Express</option>
<option value="Ward">Ward</option>
<option value="WePost Logistics">WePost Logistics</option>
<option value="Whistl">Whistl</option>
<option value="Wilson Trucking">Wilson Trucking</option>
<option value="Winit">Winit</option>
<option value="Wise">Wise</option>
<option value="Wiseloads">Wiseloads</option>
<option value="Worldwvaluee"Express">Worldwvaluee"Express</option>
<option value="XDP Express">XDP Express</option>
<option value="XDP Express Reference">XDP Express Reference</option>
<option value="XL Express">XL Express</option>
<option value="XPO LTL">XPO LTL</option>
<option value="Xend Express">Xend Express</option>
<option value="Xpost">Xpost</option>
<option value="XpressBees">XpressBees</option>
<option value="YANWEN">YANWEN</option>
<option value="YRC">YRC</option>
<option value="Yakit">Yakit</option>
<option value="Yamato Japan">Yamato Japan</option>
<option value="Yodel">Yodel</option>
<option value="Yodel International">Yodel International</option>
<option value="Yun Express">Yun Express</option>
<option value="Zalora 7 Eleven">Zalora 7 Eleven</option>
<option value="ZeptoExpress">ZeptoExpress</option>
<option value="Zinc">Zinc</option>
<option value="Zyllem">Zyllem</option>
<option value="aCommerce">aCommerce</option>
<option value="costmeticsnow">costmeticsnow</option>
<option value="deliverE">deliverE</option>
<option value="eBay Secure Local Pickup">eBay Secure Local Pickup</option>
<option value="eBay Send">eBay Send</option>
<option value="eBay SendIt">eBay SendIt</option>
<option value="eBayBopisAU">eBayBopisAU</option>
<option value="eBayBopisCA">eBayBopisCA</option>
<option value="eBayBopisDE">eBayBopisDE</option>
<option value="eBayBopisUK">eBayBopisUK</option>
<option value="eBayBopisUS">eBayBopisUS</option>
<option value="eBayNowAU">eBayNowAU</option>
<option value="eBayNowDE">eBayNowDE</option>
<option value="eBayNowUK">eBayNowUK</option>
<option value="eBayNowUS">eBayNowUS</option>
<option value="eParcel Korea">eParcel Korea</option>
<option value="eTotal Solution Limited">eTotal Solution Limited</option>
<option value="epacketcnau">epacketcnau</option>
<option value="epacketcnuk">epacketcnuk</option>
<option value="i-parcel">i-parcel</option>
<option value="iCumulus">iCumulus</option>
<option value="iLoxx">iLoxx</option>
<option value="omniparcel">omniparcel</option>
<option value="rpxonline">rpxonline</option>
<option value="sapo">sapo</option>
<option value="skypostal">skypostal</option>
<option value="uShip Freight">uShip Freight</option>
<option value="wedo">wedo</option>
								</select>
								</td>

						</tr>

					</tbody>
				</table>
			</div>

			<input style="margin-top:10px;" data-order_id ="<?php echo esc_attr( $order_id ); ?>" name="ced_ebay_submit_order_fulfillment" type="button" class="button" id="ced_ebay_submit_order_fulfillment" value="Submit Shipment">

</div>
</div>

<script>
	jQuery(document).ready(function() {
	jQuery("#ced_ebay_shipping_service_selected").select2({
		tags: true
	});
});
</script>