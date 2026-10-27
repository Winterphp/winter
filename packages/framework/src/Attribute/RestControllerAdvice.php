<?php

declare(strict_types=1);

namespace Winter\Attribute;

/**
 * Alias de #[ControllerAdvice] pour les applications JSON.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class RestControllerAdvice extends ControllerAdvice
{
}
