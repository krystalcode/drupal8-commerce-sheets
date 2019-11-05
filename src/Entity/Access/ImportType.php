<?php

namespace Drupal\commerce_sheets\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\BundleEntityAccessControlHandler;

/**
 * Defines the access control handler for Import bundles.
 */
class ImportType extends BundleEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(
    EntityInterface $entity,
    $operation,
    AccountInterface $account
  ) {
    // For now, import types cannot be updated, deleted or duplicated. Only the
    // import types provided by default are supported and they are not meant to
    // be changed.
    if (in_array($operation, ['update', 'delete', 'duplicate'])) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
