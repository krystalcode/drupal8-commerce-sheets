<?php

namespace Drupal\commerce_sheets\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\EntityAccessControlHandler;

/**
 * Defines the access control handler for Import bundles.
 */
class Import extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(
    EntityInterface $entity,
    $operation,
    AccountInterface $account
  ) {
    // Imports cannot be updated or deleted. They are only updated
    // programmatically when the corresponding queue items are processed.
    // It probably does not make sense to duplicate an Import either; we can
    // review that if a use case arises.
    if (in_array($operation, ['update', 'delete', 'duplicate'])) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
