<?php

namespace Drupal\commerce_sheets\Sheet;

use Drupal\commerce_sheets\Entity\ImportInterface;

/**
 * Defines the interface for all Commerce Sheets reader services.
 *
 * The reader service is responsible for converting the data in the file
 * associated with the given Import entity and updating the corresponding
 * entitiy(ies).
 */
interface ReaderInterface {

  /**
   * Reads the given Import and updates the corresponding entities.
   *
   * @param \Drupal\commerce_sheets\Entity\ImportInterface $import
   *   The Import entity to read.
   */
  public function read(ImportInterface $import);

}
