<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models;

class IdentityTokenResponse
{
    /** @var string */
    public $accessToken;
    /** @var string */
    public $refreshToken;
    /** @var string */
    public $expiresIn;
    /** @var string */
    public $tokenType;
    
    public function __construct(
        string $accessToken,
        string $refreshToken,
        int $expiresIn,
        string $tokenType
    ) {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresIn = $expiresIn;
        $this->tokenType = $tokenType;
    }
}
