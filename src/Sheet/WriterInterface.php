<?php

namespace Drupal\commerce_sheets\Sheet;

use Drupal\commerce_sheets\EntityFormat\EntityFormatInterface;

/**
 * Defines the interface for all Commerce Sheets writer services.
 *
 * The writer service is responsible for converting the given entities into data
 * and writing them to a spreadhseet file.
 */
interface WriterInterface {

  /**
   * Writes the given entities to a spreadsheet file based on the given format.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities to write to the spreadsheet.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The format to use.
   */
  public function write(array $entities, EntityFormatInterface $format);

}
