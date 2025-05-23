<?php

declare(strict_types=1);

namespace Drupal\farm_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Uri constraint.
 */
class UriValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint) {

    // Allow empty values.
    if (empty($value->value)) {
      return;
    }

    // Require a scheme.
    if (!str_contains($value->value, ':')) {
      // PHPStan level 2+ throws the following error on the next line:
      // Access to an undefined property
      // Symfony\Component\Validator\Constraint::$message.
      // We ignore this because we are following Drupal core's pattern.
      // @phpstan-ignore property.notFound
      $this->context->addViolation($constraint->message);
    }

    // Require a valid URI.
    $regex = "/^(?:
        [A-Za-z][A-Za-z0-9+\-.]* :
        (?: \/\/
          (?: (?:[A-Za-z0-9\-._~!$&'()*+,;=:]|%[0-9A-Fa-f]{2})* @)?
          (?:
            \[
            (?:
              (?:
                (?:                                                    (?:[0-9A-Fa-f]{1,4}:){6}
                |                                                   :: (?:[0-9A-Fa-f]{1,4}:){5}
                | (?:                            [0-9A-Fa-f]{1,4})? :: (?:[0-9A-Fa-f]{1,4}:){4}
                | (?: (?:[0-9A-Fa-f]{1,4}:){0,1} [0-9A-Fa-f]{1,4})? :: (?:[0-9A-Fa-f]{1,4}:){3}
                | (?: (?:[0-9A-Fa-f]{1,4}:){0,2} [0-9A-Fa-f]{1,4})? :: (?:[0-9A-Fa-f]{1,4}:){2}
                | (?: (?:[0-9A-Fa-f]{1,4}:){0,3} [0-9A-Fa-f]{1,4})? ::    [0-9A-Fa-f]{1,4}:
                | (?: (?:[0-9A-Fa-f]{1,4}:){0,4} [0-9A-Fa-f]{1,4})? ::
                ) (?:
                    [0-9A-Fa-f]{1,4} : [0-9A-Fa-f]{1,4}
                  | (?: (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?) \.){3}
                        (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)
                  )
              |   (?: (?:[0-9A-Fa-f]{1,4}:){0,5} [0-9A-Fa-f]{1,4})? ::    [0-9A-Fa-f]{1,4}
              |   (?: (?:[0-9A-Fa-f]{1,4}:){0,6} [0-9A-Fa-f]{1,4})? ::
              )
            | [Vv][0-9A-Fa-f]+\.[A-Za-z0-9\-._~!$&'()*+,;=:]+
            )
            \]
          | (?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}
               (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)
          | (?:[A-Za-z0-9\-._~!$&'()*+,;=]|%[0-9A-Fa-f]{2})*
          )
          (?: : [0-9]* )?
          (?:\/ (?:[A-Za-z0-9\-._~!$&'()*+,;=:@]|%[0-9A-Fa-f]{2})* )*
        | \/
          (?:    (?:[A-Za-z0-9\-._~!$&'()*+,;=:@]|%[0-9A-Fa-f]{2})+
            (?:\/ (?:[A-Za-z0-9\-._~!$&'()*+,;=:@]|%[0-9A-Fa-f]{2})* )*
          )?
        |        (?:[A-Za-z0-9\-._~!$&'()*+,;=:@]|%[0-9A-Fa-f]{2})+
            (?:\/ (?:[A-Za-z0-9\-._~!$&'()*+,;=:@]|%[0-9A-Fa-f]{2})* )*
        |
        )
        (?:\? (?:[A-Za-z0-9\-._~!$&'()*+,;=:@\/?]|%[0-9A-Fa-f]{2})* )?
        (?:\# (?:[A-Za-z0-9\-._~!$&'()*+,;=:@\/?]|%[0-9A-Fa-f]{2})* )?
      | (?: \/\/
          (?: (?:[A-Za-z0-9\-._~!$&'()*+,;=:]|%[0-9A-Fa-f]{2})* @)?
          (?:
            \[
            (?:
              (?:
                (?:                                                    (?:[0-9A-Fa-f]{1,4}:){6}
                |                                                   :: (?:[0-9A-Fa-f]{1,4}:){5}
                | (?:                            [0-9A-Fa-f]{1,4})? :: (?:[0-9A-Fa-f]{1,4}:){4}
                | (?: (?:[0-9A-Fa-f]{1,4}:){0,1} [0-9A-Fa-f]{1,4})? :: (?:[0-9A-Fa-f]{1,4}:){3}
                | (?: (?:[0-9A-Fa-f]{1,4}:){0,2} [0-9A-Fa-f]{1,4})? :: (?:[0-9A-Fa-f]{1,4}:){2}
                | (?: (?:[0-9A-Fa-f]{1,4}:){0,3} [0-9A-Fa-f]{1,4})? ::    [0-9A-Fa-f]{1,4}:
                | (?: (?:[0-9A-Fa-f]{1,4}:){0,4} [0-9A-Fa-f]{1,4})? ::
                ) (?:
                    [0-9A-Fa-f]{1,4} : [0-9A-Fa-f]{1,4}
                  | (?: (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?) \.){3}
                        (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)
                  )
              |   (?: (?:[0-9A-Fa-f]{1,4}:){0,5} [0-9A-Fa-f]{1,4})? ::    [0-9A-Fa-f]{1,4}
              |   (?: (?:[0-9A-Fa-f]{1,4}:){0,6} [0-9A-Fa-f]{1,4})? ::
              )
            | [Vv][0-9A-Fa-f]+\.[A-Za-z0-9\-._~!$&'()*+,;=:]+
            )
            \]
          | (?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}
               (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)
          | (?:[A-Za-z0-9\-._~!$&'()*+,;=]|%[0-9A-Fa-f]{2})*
          )
          (?: : [0-9]* )?
          (?:\/ (?:[A-Za-z0-9\-._~!$&'()*+,;=:@]|%[0-9A-Fa-f]{2})* )*
        | \/
          (?:    (?:[A-Za-z0-9\-._~!$&'()*+,;=:@]|%[0-9A-Fa-f]{2})+
            (?:\/ (?:[A-Za-z0-9\-._~!$&'()*+,;=:@]|%[0-9A-Fa-f]{2})* )*
          )?
        |        (?:[A-Za-z0-9\-._~!$&'()*+,;=@] |%[0-9A-Fa-f]{2})+
            (?:\/ (?:[A-Za-z0-9\-._~!$&'()*+,;=:@]|%[0-9A-Fa-f]{2})* )*
        |
        )
        (?:\? (?:[A-Za-z0-9\-._~!$&'()*+,;=:@\/?]|%[0-9A-Fa-f]{2})* )?
        (?:\# (?:[A-Za-z0-9\-._~!$&'()*+,;=:@\/?]|%[0-9A-Fa-f]{2})* )?
      )$/x";
    if (!preg_match($regex, $value->value)) {
      // PHPStan level 2+ throws the following error on the next line:
      // Access to an undefined property
      // Symfony\Component\Validator\Constraint::$message.
      // We ignore this because we are following Drupal core's pattern.
      // @phpstan-ignore property.notFound
      $this->context->addViolation($constraint->message);
    }
  }

}
