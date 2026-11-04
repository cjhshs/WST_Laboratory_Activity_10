<?php
declare(strict_types=1);

/**
 * Escape a value for safe HTML output.
 *
 * ENT_QUOTES     - encodes both single and double quotes.
 * ENT_SUBSTITUTE - replaces invalid UTF-8 instead of returning an empty string.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
