<?php

namespace App\Exceptions;

class AuthException extends BaseException
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid username or password');
    }

    public static function emailNotVerified(): self
    {
        return new self('Email address has not been verified');
    }

    public static function invalidToken(): self
    {
        return new self('Invalid or expired verification token');
    }

    public static function userNotFound(): self
    {
        return new self('User not found');
    }
}