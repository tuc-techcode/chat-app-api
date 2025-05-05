<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class Jwt_Token_Helper
{
  private const DEFAULT_ALGORITHM = 'HS256';
  private $secretKey = 'TUC_Super_Secret_Key_Na_Malala';
  private $issuer = 'tuc-dev';  // Issuer (your domain/application name)
  private $expirationTime = 86400 * 7; // Token expiration time in seconds (7 days)

  /**
   * Generate a new JWT token
   * 
   * @param int|string $userId   User identifier
   * @param array $additionalPayload  Additional claims to include
   * @return string             Generated JWT token
   */
  public function generateToken($userId, array $additionalPayload = [])
  {
    $issuedAt = time();

    $payload = array_merge([
      'iat'  => $issuedAt,
      'exp'  => $issuedAt + $this->expirationTime,
      'iss'  => $this->issuer,
      'sub'  => $userId,
      'jti'  => bin2hex(random_bytes(16)), // Unique token identifier
    ], $additionalPayload);

    return JWT::encode($payload, $this->secretKey, self::DEFAULT_ALGORITHM);
  }

  /**
   * Validate a JWT token
   * 
   * @param string $token   The JWT token to validate
   * @return stdClass       Decoded token payload
   * @throws Exception      If validation fails
   */
  public static function validate(string $token, string $secretKey)
  {
    try {
      return JWT::decode($token, new Key($secretKey, self::DEFAULT_ALGORITHM));
    } catch (Exception $e) {
      throw new Exception("Token validation failed: " . $e->getMessage(), 401);
    }
  }

  /**
   * Get the secret key
   * @return string
   */
  public function getSecretKey(): string
  {
    return $this->secretKey;
  }
}
