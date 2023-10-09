<?php

require_once 'custombec.civix.php';
use CRM_Bec_ExtensionUtil as E;

const MEMTYPE_STUDENTDISCOUNT = 15;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function custombec_civicrm_config(&$config) {
  _custombec_civix_civicrm_config($config);
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
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function custombec_civicrm_enable() {
  _custombec_civix_civicrm_enable();
}

/**
 * Calculate reduced price for students on renewal
 * @param $pageType
 * @param $form
 * @param $amount
 *
 * @throws \CiviCRM_API3_Exception
 */
function custombec_civicrm_buildAmount($pageType, &$form, &$amount) {
  // Initialise
  $studentDiscount = 0;
  $membershipNames = [];

  // Check if we have a contact Id
  $contactId = $form->_contactID;

  if (empty($contactId)) {
    return;
  }

  // Check if that contact has a membership of type 15 (student discount)
  $studentMembership = \Civi\Api4\Membership::get(FALSE)
    ->addWhere('membership_type_id', '=', MEMTYPE_STUDENTDISCOUNT)
    ->addWhere('status_id:name', 'IN', ['New', 'Current', 'Grace'])
    ->addWhere('contact_id', '=', $contactId)
    ->execute();
  if ($studentMembership->count() > 0) {
    // Contact has a student discount membership
    // Get the amount of the student discount
    $membershipType = \Civi\Api4\MembershipType::get(FALSE)
    ->addWhere('id', '=', MEMTYPE_STUDENTDISCOUNT)
    ->execute()
      ->first();
    if (!empty($membershipType)) {
      $studentDiscount = $membershipType['minimum_fee'];
    }
  }

  // Only allow negative amounts for student discount
  if ($studentDiscount > 0) {
    $studentDiscount = 0;
  }

  // Get list of current memberships
  $memberships = \Civi\Api4\Membership::get(FALSE)
  ->addWhere('contact_id', '=', $contactId)
  ->execute();
  if ($memberships->count() === 0) {
    \Civi::log()->debug('beccustom: No memberships for contact id: ' . $contactId);
  }

  // Set array of membership names for contact
  foreach ($memberships as $membership) {
    $membershipNames[] = $membership['membership_name'];
  }

  //sample to modify priceset fee
  $priceSetId = $form->get('priceSetId');
  if (!empty($priceSetId)) {
    $feeBlock = &$amount;
    if (!is_array($feeBlock) || empty($feeBlock)) {
      return;
    }

    if ($pageType == 'membership') {
      // Apply student discount

      foreach ($feeBlock as &$fee) {
        if (strtolower($fee['name']) !== 'bec_membership') {
          continue;
        }
        if (!is_array($fee['options'])) {
          continue;
        }
        $filteredOptions = [];
        foreach ($fee['options'] as &$option) {
          // Check if we already have a membership of this type, don't allow selection if not.
          $match = FALSE;
          foreach ($membershipNames as $membershipName) {
            if ($option['name'] === $membershipName) {
              $match = TRUE;
              break;
            }
          }

          if (!$match) {
            // Contact doesn't have membership of this type, don't allow selection
            $option = NULL; // Don't remove the option or it breaks! But we can set it to NULL
            continue;
          }
          if (!$match) {
            $option['is_active'] = 0;
            $option['visibility_id'] = 0;

          }

          // Apply student discount if they have one
          if (($option['amount'] > 0) && ($studentDiscount < 0)) {
            $option['amount'] = $option['amount'] + $studentDiscount;
            if ($option['amount'] < 0) {
              $option['amount'] = 0;
            }
            $option['label'] .= ' - Student Discount';
          }
        }
      }
      // Set this so changes are applied on confirmation page
      $form->_priceSet['fields'] = $feeBlock;
    }
  }
}

function custombec_civicrm_buildForm($formName, &$form) {
  switch ($formName) {
    case 'CRM_Contribute_Form_Contribution_Main':
      CRM_Core_Resources::singleton()->addScriptFile('uk.org.bec.custom', 'js/contribution' . $form->_id . '.js');
      break;
  }
}
