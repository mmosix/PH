<?php

namespace App\Utils;

trait InputValidator
{
    /**
     * Validate required fields in data array
     * @param array $data Input data
     * @param array $required Required field names
     * @return array List of missing fields
     */
    protected function validateRequired(array $data, array $required): array
    {
        return array_filter($required, fn($field) => empty($data[$field]));
    }

    /**
     * Validate that a value is within allowed options
     * @param mixed $value Value to check
     * @param array $allowed Allowed values
     * @return bool Whether value is allowed
     */
    protected function validateAllowed($value, array $allowed): bool
    {
        return in_array($value, $allowed, true);
    }

    /**
     * Validate string length
     * @param string $value String to check
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @return bool Whether length is valid
     */
    protected function validateLength(string $value, int $min, int $max): bool
    {
        $length = mb_strlen($value);
        return $length >= $min && $length <= $max;
    }

    /**
     * Validate numeric range
     * @param int|float $value Number to check
     * @param int|float|null $min Minimum value
     * @param int|float|null $max Maximum value
     * @return bool Whether value is in range
     */
    protected function validateRange($value, $min = null, $max = null): bool
    {
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        return true;
    }
}