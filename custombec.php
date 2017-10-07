<?php

require_once 'custombec.civix.php';
use CRM_custombec_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function custombec_civicrm_config(&$config) {
  _custombec_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function custombec_civicrm_xmlMenu(&$files) {
  _custombec_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function custombec_civicrm_install() {
  _custombec_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function custombec_civicrm_postInstall() {
  _custombec_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function custombec_civicrm_uninstall() {
  _custombec_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function custombec_civicrm_enable() {
  _custombec_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function custombec_civicrm_disable() {
  _custombec_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function custombec_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _custombec_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function custombec_civicrm_managed(&$entities) {
  _custombec_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function custombec_civicrm_caseTypes(&$caseTypes) {
  _custombec_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function custombec_civicrm_angularModules(&$angularModules) {
  _custombec_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function custombec_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _custombec_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function custombec_civicrm_tokens( &$tokens ) {
  $tokens['membership'] = array( 'membership.BEC_mem_name', 'membership.BEC_fee','membership.BEC_start_date', 'membership.BCA_mem_name', 'membership.BCA_fee', 'membership.BCA_start_date','membership.Student_mem_name', 'membership.Student_fee', 'membership.Student_start_date', 'membership.Total_fee' );
}

function custombec_civicrm_tokenValues( &$values, &$contactIDs ) {
  if ( is_array( $contactIDs ) ) {
    $contactIDString = implode( ',', array_values( $contactIDs ) );
    $single = false;
  } else {
    $contactIDString = "( $contactIDs )";
    $single = true;
  }
  $value['membership.Total_fee'] = 0;

  $query = "                                                                                                                              
SELECT contact_id,                                                                                                                          
       civicrm_membership_type.name,                                                                                                                  
       start_date,
       end_date,
       civicrm_membership_type.minimum_fee 
FROM   civicrm_membership  
       LEFT JOIN civicrm_membership_type ON civicrm_membership.membership_type_id=civicrm_membership_type.id 

WHERE  contact_id IN ( $contactIDString )
       AND civicrm_membership.membership_type_id <=8
       AND    is_test = 0
ORDER BY end_date DESC                                                                                                                       
";

  $dao = CRM_Core_DAO::executeQuery( $query );
  while ( $dao->fetch( ) ) {
    if ( $single ) {
      $value =& $values;
    } else {
      if ( ! array_key_exists( $dao->contact_id, $values ) ) {
        $values[$dao->contact_id] = array( );
      }
      $value =& $values[$dao->contact_id];
    }

    $value['membership.BEC_mem_name'] = $dao->name;
    $value['membership.start_date'  ] = $dao->start_date;
    $value['membership.BEC_fee'  ] = $dao->minimum_fee;
    $value['membership.Total_fee'  ] = $dao->minimum_fee;
  }

  //  ------------------------------------round 2 --------------------------------------------------------------
  $query = "                                                                                                                              
SELECT contact_id,                                                                                                                          
       civicrm_membership_type.name,                                                                                                                  
       start_date,
       civicrm_membership_type.minimum_fee 
FROM   civicrm_membership  
       LEFT JOIN civicrm_membership_type ON civicrm_membership.membership_type_id=civicrm_membership_type.id

WHERE  contact_id IN ( $contactIDString )
	AND civicrm_membership.membership_type_id IN (9,10,11,12)
AND    is_test = 0                                                                                                                          
GROUP BY contact_id                                                                                                                         
";

  $dao = CRM_Core_DAO::executeQuery( $query );
  while ( $dao->fetch( ) ) {
    if ( $single ) {
      $value =& $values;
    } else {
      if ( ! array_key_exists( $dao->contact_id, $values ) ) {
        $values[$dao->contact_id] = array( );
      }
      $value =& $values[$dao->contact_id];
    }

    $value['membership.BCA_mem_name'] = $dao->name;
    $value['membership.BCA_start_date'  ] = $dao->start_date;
    $value['membership.BCA_fee'  ] = $dao->minimum_fee;
    $value['membership.Total_fee'  ] += $dao->minimum_fee;
  }
  //  ------------------------------------round 3 --------------------------------------------------------------
  $query = "                                                                                                                              
SELECT contact_id,                                                                                                                          
       civicrm_membership_type.name,                                                                                                                  
       start_date,
       civicrm_membership_type.minimum_fee 
FROM   civicrm_membership  
       LEFT JOIN civicrm_membership_type ON civicrm_membership.membership_type_id=civicrm_membership_type.id

WHERE  contact_id IN ( $contactIDString )
	AND civicrm_membership.membership_type_id = 15
AND    is_test = 0                                                                                                                          
GROUP BY contact_id                                                                                                                         
";

  $dao = CRM_Core_DAO::executeQuery( $query );
  while ( $dao->fetch( ) ) {
    if ( $single ) {
      $value =& $values;
    } else {
      if ( ! array_key_exists( $dao->contact_id, $values ) ) {
        $values[$dao->contact_id] = array( );
      }
      $value =& $values[$dao->contact_id];
    }

    $value['membership.Student_mem_name'] = $dao->name;
    $value['membership.Student_start_date'  ] = $dao->start_date;
    $value['membership.Student_fee'  ] = $dao->minimum_fee;
    $value['membership.Total_fee'  ] += $dao->minimum_fee;
    $value['membership.Total_fee'  ] += 100;
  }
}
