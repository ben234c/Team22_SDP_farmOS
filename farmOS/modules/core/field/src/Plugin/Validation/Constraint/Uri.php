<?php

declare(strict_types=1);

namespace Drupal\farm_field\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraints\Url;

/**
 * Checks that a URI is valid.
 */
#[Constraint(
  id: 'Uri',
  label: new TranslatableMarkup('Valid URI', ['context' => 'Validation']),
)]
class Uri extends Url {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'This value is not a valid URI.';

}
