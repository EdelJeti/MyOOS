<?php
/* ----------------------------------------------------------------------

   MyOOS [Shopsystem]
   http://www.oos-shop.de/

   Copyright (c) 2003 - 2017 by the MyOOS Development Team.
   ----------------------------------------------------------------------
   Based on:

   File: create_account.php,v 1.59 2003/02/14 05:51:17 hpdl 
   ----------------------------------------------------------------------
   osCommerce, Open Source E-Commerce Solutions
   http://www.oscommerce.com

   Copyright (c) 2003 osCommerce
   ----------------------------------------------------------------------
   Released under the GNU General Public License
   ---------------------------------------------------------------------- */

/** ensure this file is being included by a parent file */
defined( 'OOS_VALID_MOD' ) OR die( 'Direct Access to this location is not allowed.' );

// require  the password crypto functions
require_once MYOOS_INCLUDE_PATH . '/includes/functions/function_password.php';

// require  validation functions (right now only email address)
require_once MYOOS_INCLUDE_PATH . '/includes/functions/function_validations.php';  
require_once MYOOS_INCLUDE_PATH . '/includes/functions/function_validate_vatid.php';

require_once MYOOS_INCLUDE_PATH . '/includes/languages/' . $sLanguage . '/create_account.php';

// start the session
if ( $session->hasStarted() === FALSE ) $session->start();

// navigation history
if (!isset($_SESSION['navigation'])) {
	$_SESSION['navigation'] = new oosNavigationHistory();
} 

if ( $_SESSION['login_count'] > 3) {
	oos_redirect(oos_href_link($aContents['403']));
}

if ( isset($_POST['action']) && ($_POST['action'] == 'process') && 
	( isset($_SESSION['formid']) && ($_SESSION['formid'] == $_POST['formid'])) ){

    if (ACCOUNT_GENDER == 'true') {
		if (isset($_POST['gender'])) {
			$gender = oos_db_prepare_input($_POST['gender']);
		} else {
			$gender = FALSE;
		}
    }
    $firstname = oos_db_prepare_input($_POST['firstname']);
    $lastname = oos_db_prepare_input($_POST['lastname']);
    if (ACCOUNT_DOB == 'true') $dob = oos_db_prepare_input($_POST['dob']);
    $email_address = oos_db_prepare_input($_POST['email_address']);
    if (ACCOUNT_COMPANY == 'true') $company = oos_db_prepare_input($_POST['company']);
    if (ACCOUNT_OWNER == 'true') $owner = oos_db_prepare_input($_POST['owner']);
    if (ACCOUNT_VAT_ID == 'true') $vat_id = oos_db_prepare_input($_POST['vat_id']);
    $street_address = oos_db_prepare_input($_POST['street_address']);
    $postcode = oos_db_prepare_input($_POST['postcode']);
    $city = oos_db_prepare_input($_POST['city']);
    if (ACCOUNT_STATE == 'true') {
		$state = oos_db_prepare_input($_POST['state']);
		if (isset($_POST['zone_id'])) {
			$zone_id = oos_db_prepare_input($_POST['zone_id']);
		} else {
			$zone_id = FALSE;
		}
    }
    $country = oos_db_prepare_input($_POST['country']);
    $telephone = oos_db_prepare_input($_POST['telephone']);
    $password = oos_db_prepare_input($_POST['password']);
    $confirmation = oos_db_prepare_input($_POST['confirmation']);
    if (isset($_POST['newsletter'])) {
		$newsletter = oos_db_prepare_input($_POST['newsletter']);
    } 
    if (isset($_POST['agree'])) {
		$agree = oos_db_prepare_input($_POST['agree']);
    } 
	
	$bError = FALSE; // reset error flag
    if (ACCOUNT_GENDER == 'true') {
		if ( ($gender != 'm') && ($gender != 'f') ) {
			$bError = TRUE;
			$oMessage->add('create_account', $aLang['entry_gender_error']);
		}
    }

    if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
		$bError = TRUE;
		$oMessage->add('create_account', $aLang['entry_first_name_error'] );
    }	

	if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
		$bError = TRUE;
		$oMessage->add('create_account', $aLang['entry_last_name_error'] );
    }

	if (ACCOUNT_DOB == 'true') {
		if ((strlen($dob) < ENTRY_DOB_MIN_LENGTH) || (!empty($dob) && 
			(!is_numeric(oos_date_raw($dob)) ||
			!checkdate(substr(oos_date_raw($dob), 4, 2), substr(oos_date_raw($dob), 6, 2), substr(oos_date_raw($dob), 0, 4))))) {		
	
			$bError = TRUE;
			$oMessage->add('create_account', $aLang['entry_date_of_birth_error'] );
		}
	}

    if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
		$bError = TRUE;
		$oMessage->add('create_account', $aLang['entry_email_address_error']);
    } elseif (oos_validate_is_email($email_address) == FALSE) {
		$bError = TRUE;
		$oMessage->add('create_account', $aLang['entry_email_address_check_error']);
    } else {
		$customerstable = $oostable['customers'];
		$check_email_sql = "SELECT customers_email_address
                      FROM $customerstable
                      WHERE customers_email_address = '" . oos_db_input($email_address) . "'";
		$check_email = $dbconn->Execute($check_email_sql);
		if ($check_email->RecordCount()) {		
			$bError = TRUE;
			$oMessage->add('create_account', $aLang['entry_email_address_error_exists']);
		}
    }

	if (ACCOUNT_COMPANY_VAT_ID_CHECK == 'true'){
		if (!empty($vat_id) && (!oos_validate_is_vatid($vat_id))) {
			$bError = TRUE;
			$oMessage->add('create_account', $aLang['entry_vat_id_error']);
		} else {
			$vatid_check_error == FALSE;
		}
	}

	if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
		$bError = TRUE;
		$oMessage->add('create_account', $aLang['entry_street_address_error']);
	}	

	if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
		$bError = TRUE;
		$oMessage->add('create_account', $aLang['entry_post_code_error']);
	}
 
	if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
		$bError = TRUE;
		$oMessage->add('create_account', $aLang['entry_city_error']);
	}

	if (is_numeric($country) == FALSE) {
		$bError = TRUE;
		$oMessage->add('create_account', $aLang['entry_country_error']);
    }
	
	if (ACCOUNT_STATE == 'true') {
		$zone_id = 0;
		$zonestable = $oostable['zones'];
		$country_check_sql = "SELECT COUNT(*) AS total
								FROM $zonestable
								WHERE zone_country_id = '" . intval($country) . "'";
		$country_check = $dbconn->Execute($country_check_sql);
		$entry_state_has_zones = ($country_check->fields['total'] > 0);
		if ($entry_state_has_zones == TRUE) {
			$zonestable = $oostable['zones'];
			$zone_query = "SELECT DISTINCT zone_id
                           FROM $zonestable
                           WHERE zone_country_id = '" . intval($country) . "'
                             AND (zone_name = '" . oos_db_input($state) . "'
							OR zone_code = '" . oos_db_input($state) . "')";							
			$zone_result = $dbconn->Execute($zone_query);
			if ($zone_result->RecordCount() == 1) {
				$zone = $zone_result->fields;
				$zone_id = $zone['zone_id'];
			} else {
				$bError = TRUE;
				$oMessage->add('create_account', $$aLang['entry_state_error_select']);
			}
		} else {
			if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
				$bError = TRUE;
				$oMessage->add('create_account', $aLang['entry_state_error']);
			}
		}
	}	

/*	
	if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
		$bError = TRUE;
		$oMessage->add('create_account', $aLang['entry_telephone_number_error']);
	}
*/ 
	if (CUSTOMER_NOT_LOGIN == 'false') {
		if (strlen($password) < ENTRY_PASSWORD_MIN_LENGTH) {
			$bError = TRUE;
			$oMessage->add('create_account', $aLang['entry_password_error']);
		} elseif ($password != $confirmation) {
			$bError = TRUE;
			$oMessage->add('create_account', $aLang['entry_password_error_not_matching']);
		}
	}

	if (empty($agree)) {
		$bError = TRUE;
		$oMessage->add('create_account', $aLang['entry_agree_error']);
	}	
	
	if ($bError == FALSE) {
		$customer_max_order = DEFAULT_MAX_ORDER;
		$customers_status = DEFAULT_CUSTOMERS_STATUS_ID;

		if (CUSTOMER_NOT_LOGIN == 'true') {
			$customers_login = '0';
		} else {
			$customers_login = '1';
		}

		$time = mktime();
		$wishlist_link_id = oos_create_wishlist_code;

		$sql_data_array = array('customers_firstname' => $firstname,
								'customers_lastname' => $lastname,
								'customers_email_address' => $email_address,
								'customers_telephone' => $telephone,
								'customers_newsletter' => $newsletter,
								'customers_status' => $customers_status,
								'customers_login' => $customers_login,
								'customers_language' => $sLanguage,
								'customers_max_order' => $customer_max_order,
								'customers_password' => oos_encrypt_password($password),
								'customers_wishlist_link_id' => $wishlist_link_id,
								'customers_default_address_id' => 1);

		if (ACCOUNT_GENDER == 'true') $sql_data_array['customers_gender'] = $gender;
		if (ACCOUNT_DOB == 'true') $sql_data_array['customers_dob'] = oos_date_raw($dob);
		if (ACCOUNT_VAT_ID == 'true') {
			$sql_data_array['customers_vat_id'] = $vat_id;
			if ((ACCOUNT_COMPANY_VAT_ID_CHECK == 'true') && ($vatid_check_error == FALSE) && ($country != STORE_COUNTRY)) {
				$sql_data_array['customers_vat_id_status'] = 1;
			} else {
				$sql_data_array['customers_vat_id_status'] = 0;
			}
		}
		oos_db_perform($oostable['customers'], $sql_data_array);

		$customer_id = $dbconn->Insert_ID();

		$sql_data_array = array('customers_id' => $customer_id,
                            'address_book_id' => 1,
                            'entry_firstname' => $firstname,
                            'entry_lastname' => $lastname,
                            'entry_street_address' => $street_address,
                            'entry_postcode' => $postcode,
                            'entry_city' => $city,
                            'entry_country_id' => $country);

		if (ACCOUNT_GENDER == 'true') $sql_data_array['entry_gender'] = $gender;
		if (ACCOUNT_COMPANY == 'true') $sql_data_array['entry_company'] = $company;
		if (ACCOUNT_OWNER == 'true') $sql_data_array['entry_owner'] = $owner;
		if (ACCOUNT_STATE == 'true') {
			if ($zone_id > 0) {
				$sql_data_array['entry_zone_id'] = $zone_id;
				$sql_data_array['entry_state'] = '';
			} else {
				$sql_data_array['entry_zone_id'] = '0';
				$sql_data_array['entry_state'] = $state;
			}
		}

		oos_db_perform($oostable['address_book'], $sql_data_array);

		$customers_infotable = $oostable['customers_info'];
		$dbconn->Execute("INSERT INTO $customers_infotable
						(customers_info_id,
						customers_info_number_of_logons, 
						customers_info_date_account_created) VALUES ('" . intval($customer_id) . "',
																	'0',
																	now())");


		if (CUSTOMER_NOT_LOGIN != 'true') {
			$_SESSION['customer_id'] = $customer_id;
			if (ACCOUNT_GENDER == 'true') $_SESSION['customer_gender'] = $gender;
			$_SESSION['customer_first_name'] = $firstname;
			$_SESSION['customer_lastname'] = $lastname;
			$_SESSION['customer_default_address_id'] = 1;
			$_SESSION['customer_country_id'] = $country;
			$_SESSION['customer_zone_id'] = $zone_id;
			$_SESSION['customer_wishlist_link_id'] = $wishlist_link_id;
			$_SESSION['customer_max_order'] = $customer_max_order;

			if (ACCOUNT_VAT_ID == 'true') {
				if ((ACCOUNT_COMPANY_VAT_ID_CHECK == 'true') && ($vatid_check_error == FALSE)) {
					$_SESSION['customers_vat_id_status'] = 1;
				} else {
					$_SESSION['customers_vat_id_status'] = 0;
				}
			}

			// restore cart contents
			$_SESSION['cart']->restore_contents();

			$_SESSION['user']->restore_group();
			$aUser = $_SESSION['user']->group;
		}

		// build the message content
		$name = $firstname . " " . $lastname;

		if (ACCOUNT_GENDER == 'true') {
			if ($gender == 'm') {
				$email_text = $aLang['email_greet_mr'];
			} else {
				$email_text = $aLang['email_greet_ms'];
			}
		} else {
			$email_text = $aLang['email_greet_none'];
		}

		$email_text .= $aLang['email_welcome'];
		if (MODULE_ORDER_TOTAL_GV_STATUS == 'true') {
			if (NEW_SIGNUP_GIFT_VOUCHER_AMOUNT > 0) {
				$coupon_code = oos_create_coupon_code();
				$couponstable = $oostable['coupons'];
				$insert_result = $dbconn->Execute("INSERT INTO $couponstable
                                    (coupon_code,
                                     coupon_type,
                                     coupon_amount,
                                     date_created) VALUES ('" . oos_db_input($coupon_code) . "',
                                                           'G',
                                                           '" . NEW_SIGNUP_GIFT_VOUCHER_AMOUNT . "',
                                                           now())");
				$insert_id = $dbconn->Insert_ID();
				$coupon_email_tracktable = $oostable['coupon_email_track'];
				$insert_result = $dbconn->Execute("INSERT INTO $coupon_email_tracktable
                                    (coupon_id,
                                     customer_id_sent,
                                     sent_firstname,
                                     emailed_to,
                                     date_sent) VALUES ('" . oos_db_input($insert_id) ."',
                                                        '0',
                                                        'Admin',
                                                        '" . $email_address . "',
                                                        now() )");

				$email_text .= sprintf($aLang['email_gv_incentive_header'], $oCurrencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)) . "\n\n" .
                       sprintf($aLang['email_gv_redeem'], $coupon_code) . "\n\n" .
                       $aLang['email_gv_link'] . oos_href_link($aContents['gv_redeem'], 'gv_no=' . $coupon_code, 'NONSSL', false, false) . 
                       "\n\n";  
			}
			if (NEW_SIGNUP_DISCOUNT_COUPON != '') {
				$coupon_id = NEW_SIGNUP_DISCOUNT_COUPON;
				$couponstable = $oostable['coupons'];
				$sql = "SELECT *
						FROM $couponstable
						WHERE coupon_id = '" . oos_db_input($coupon_id) . "'";
				$coupon_result = $dbconn->Execute($sql);

				$coupons_descriptiontable = $oostable['coupons_description'];
				$sql = "SELECT *
					FROM " . $coupons_descriptiontable . "
					WHERE coupon_id = '" . oos_db_input($coupon_id) . "'
					AND coupon_languages_id = '" .  intval($nLanguageID) . "'";
				$coupon_desc_result = $dbconn->Execute($sql);
				$coupon = $coupon_result->fields;
				$coupon_desc = $coupon_desc_result->fields;
				$coupon_email_tracktable = $oostable['coupon_email_track'];
				$insert_result = $dbconn->Execute("INSERT INTO $coupon_email_tracktable
                                          (coupon_id,
                                           customer_id_sent,
                                           sent_firstname,
                                           emailed_to,
                                           date_sent) VALUES ('" . oos_db_input($coupon_id) ."',
                                                              '0',
                                                              'Admin',
                                                              '" . oos_db_input($email_address) . "',
                                                              now() )");

				$email_text .= $aLang['email_coupon_incentive_header'] .  "\n\n" .
							$coupon_desc['coupon_description'] .
						sprintf($aLang['email_coupon_redeem'], $coupon['coupon_code']) . "\n\n" .
                       "\n\n";
			}
		}

		$email_text .= $aLang['email_text'] . $aLang['email_contact'] . $aLang['email_warning'] . $aLang['email_disclaimer'];

		oos_mail($name, $email_address, $aLang['email_subject'], nl2br($email_text), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, '3');

		if (SEND_CUSTOMER_EDIT_EMAILS == 'true') {
			$email_owner = $aLang['owner_email_subject'] . "\n" .
						$aLang['email_separator'] . "\n" .
						$aLang['owner_email_date'] . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n" .
						$aLang['email_separator'] . "\n";

			if (ACCOUNT_COMPANY == 'true') {
				$email_owner .= $aLang['owner_email_company_info'] . "\n" .
							$aLang['owner_email_company'] . ' ' . $company . "\n";
				if (ACCOUNT_OWNER == 'true') {
					$email_owner .= $aLang['owner_email_owner'] . ' ' . $owner . "\n";
				}
				if (ACCOUNT_VAT_ID == 'true') {
					$email_owner .= $aLang['entry_vat_id'] . ' ' . $vat_id . "\n";
				}
			}
			if (ACCOUNT_GENDER == 'true') {
				if ($gender == 'm') {
					$email_owner .= $aLang['entry_gender'] . ' ' . $aLang['male'] . "\n";
				} else {
					$email_owner .= $aLang['entry_gender'] . ' ' . $aLang['female'] . "\n";
				}
			}

			$email_owner .= $aLang['owner_email_first_name'] . ' ' . $firstname . "\n" .
                      $aLang['owner_email_last_name'] . ' ' . $lastname . "\n\n" .
                      $aLang['owner_email_street'] . ' ' . $street_address . "\n" .
                      $aLang['owner_email_post_code'] . ' ' . $postcode . "\n" .
                      $aLang['owner_email_city'] . ' ' . $city . "\n" .
                      $aLang['email_separator'] . "\n\n" .
                      $aLang['owner_email_contact'] . "\n" .
                      $aLang['owner_email_telephone_number'] . ' ' . $telephone . "\n" .
                      $aLang['owner_email_address'] . ' ' . $email_address . "\n" .
                      $aLang['email_separator'] . "\n\n" .
                      $aLang['owner_email_options'] . "\n";

			oos_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $aLang['owner_email_subject'], nl2br($email_owner), $name, $email_address, '1');  
		}
	
	
		if (NEWSLETTER == 'true') {
			if ( isset($newsletter) && ($newsletter == 'yes') ) {
				oos_newsletter_subscribe_mail($email_address);
			}
		}

		oos_redirect(oos_href_link($aContents['create_account_success'], '', 'SSL'));
	}
}


// links breadcrumb
$oBreadcrumb->add($aLang['navbar_title'], oos_href_link($aContents['create_account']));
$sCanonical = oos_href_link($aContents['create_account'], '', 'SSL', FALSE, TRUE);

$snapshot = count($_SESSION['navigation']->snapshot);


if (isset($_GET['email_address'])) {
	$email_address = oos_db_prepare_input($_GET['email_address']);
}
$account['entry_country_id'] = STORE_COUNTRY;


$aTemplate['page'] = $sTheme . '/page/create_account.html';
$aTemplate['javascript'] = $sTheme . '/js/create_account.html';

$nPageType = OOS_PAGE_TYPE_ACCOUNT;
$sPagetitle = $aLang['heading_title'] . ' ' . OOS_META_TITLE;

if ($oMessage->size('create_account') > 0) {
	$aInfoMessage = array_merge ($aInfoMessage, $oMessage->output('create_account') );
}

require_once MYOOS_INCLUDE_PATH . '/includes/system.php';
if (!isset($option)) {
	require_once MYOOS_INCLUDE_PATH . '/includes/message.php';
	require_once MYOOS_INCLUDE_PATH . '/includes/blocks.php';
}


// assign Smarty variables;
$smarty->assign(
	array(
		'breadcrumb'	=> $oBreadcrumb->trail(),
		'heading_title' => $aLang['heading_title'],
		'robots'		=> 'noindex,follow,noodp,noydir',
		'canonical'		=> $sCanonical
	)
);

$smarty->assign('account', $account);
$smarty->assign('email_address', $email_address);

$smarty->assign('snapshot', $snapshot);
$smarty->assign('login_orgin_text', sprintf($aLang['text_origin_login'], oos_href_link($aContents['login'], '', 'SSL')));
$smarty->assign('login_agree', sprintf($aLang['agree'], oos_href_link($aContents['information'], 'information_id=2', 'SSL'), oos_href_link($aContents['information'], 'information_id=4', 'SSL')));

$smarty->assign('javascript', $smarty->fetch($aTemplate['javascript']));

// display the template
$smarty->display($aTemplate['page']);
