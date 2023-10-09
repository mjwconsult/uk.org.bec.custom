<?php

namespace Civi\Bec;

use Civi\Api4\Membership;
use Civi\Crypto\Exception\CryptoException;
use CRM_Bec_ExtensionUtil as E;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 * @service
 */
class Tokens extends AutoService implements EventSubscriberInterface {

  /** @var string */
  private static $entity = 'membership';

  public static function getSubscribedEvents(): array {
    return [
      'civi.token.list' => 'registerTokens',
      'civi.token.eval' => 'evaluateTokens',
    ];
  }

  /**
   * Expose tokens for use in UI.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::tokens()
   */
  public static function registerTokens(\Civi\Token\Event\TokenRegisterEvent $event) {
    $event->entity(self::$entity)
      ->register('BEC_mem_name', E::ts('BEC_mem_name'))
      ->register('BEC_fee', E::ts('BEC_fee'))
      ->register('BEC_start_date', E::ts('BEC_start_date'))
      ->register('BCA_mem_name', E::ts('BCA_mem_name'))
      ->register('BCA_fee', E::ts('BCA_fee'))
      ->register('BCA_start_date', E::ts('BCA_start_date'))
      ->register('Student_mem_name', E::ts('Student_mem_name'))
      ->register('Student_fee', E::ts('Student_fee'))
      ->register('Student_start_date', E::ts('Student_start_date'))
      ->register('Total_fee', E::ts('Total_fee'));
  }

  /**
   * Substitute any tokens with actual values.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::tokenValues()
   */
  public static function evaluateTokens(\Civi\Token\Event\TokenValueEvent $event) {
    $contactIDs = [];

    // Pre-fetch membership data
    foreach ($event->getRows() as $row) {
      $contactIDs[] = $row->context['contact_id'];
    }
    // Get BEC membership data
    $becMembershipData = Membership::get(FALSE)
      ->addSelect('contact_id', 'start_date', 'end_date', 'membership_type_id.name', 'membership_type_id.minimum_fee')
      ->addWhere('contact_id', 'IN', $contactIDs)
      ->addWhere('membership_type_id', '<', 8)
      ->addOrderBy('end_date', 'DESC')
      ->execute()
      ->indexBy('contact_id');
    // Get BCA membership data
    $bcaMembershipData = Membership::get(FALSE)
      ->addSelect('contact_id', 'start_date', 'end_date', 'membership_type_id.name', 'membership_type_id.minimum_fee')
      ->addWhere('contact_id', 'IN', $contactIDs)
      ->addWhere('membership_type_id', 'IN', [9, 10, 11, 12])
      ->addWhere('status_id:name', 'IN', ['New', 'Current', 'Grace'])
      ->addGroupBy('contact_id')
      ->execute()
      ->indexBy('contact_id');
    // Get student membership data
    $studentMembershipData = Membership::get(FALSE)
      ->addSelect('contact_id', 'start_date', 'end_date', 'membership_type_id.name', 'membership_type_id.minimum_fee')
      ->addWhere('contact_id', 'IN', $contactIDs)
      ->addWhere('membership_type_id', '=', MEMTYPE_STUDENTDISCOUNT)
      ->addWhere('status_id:name', 'IN', ['New', 'Current'])
      ->addGroupBy('contact_id')
      ->execute()
      ->indexBy('contact_id');

    // @todo: refactoring to pre-fetch data and then need to loop through filling in tokens..

    foreach ($event->getRows() as $row) {
      /** @var \Civi\Token\TokenRow $row */
      $row->format('text/html');
      $totalFee = 0;

      // BEC tokens
      $row->tokens(self::$entity, 'BEC_mem_name', $becMembershipData[$row->context['contact_id']]['membership_type_id.name']);
      $row->tokens(self::$entity, 'start_date', $becMembershipData[$row->context['contact_id']]['start_date']);
      $row->tokens(self::$entity, 'BEC_fee', \CRM_Utils_Money::format($becMembershipData[$row->context['contact_id']]['membership_type_id.minimum_fee']));
      $totalFee = $totalFee + $becMembershipData[$row->context['contact_id']]['membership_type_id.minimum_fee'];

      // BCA tokens
      $row->tokens(self::$entity, 'BCA_mem_name', $bcaMembershipData[$row->context['contact_id']]['membership_type_id.name']);
      $row->tokens(self::$entity, 'BCA_start_date', $bcaMembershipData[$row->context['contact_id']]['start_date']);
      $row->tokens(self::$entity, 'BCA_fee', \CRM_Utils_Money::format($bcaMembershipData[$row->context['contact_id']]['membership_type_id.minimum_fee']));
      $totalFee = $totalFee + $bcaMembershipData[$row->context['contact_id']]['membership_type_id.minimum_fee'];

      // Student tokens
      $row->tokens(self::$entity, 'Student_mem_name', $studentMembershipData[$row->context['contact_id']]['membership_type_id.name']);
      $row->tokens(self::$entity, 'Student_start_date', $studentMembershipData[$row->context['contact_id']]['start_date']);
      $row->tokens(self::$entity, 'Student_fee', \CRM_Utils_Money::format($studentMembershipData[$row->context['contact_id']]['membership_type_id.minimum_fee']));
      $totalFee = $totalFee + $studentMembershipData[$row->context['contact_id']]['membership_type_id.minimum_fee'];

      $row->tokens(self::$entity, 'Total_fee', \CRM_Utils_Money::format($totalFee));
    }

  }

}
