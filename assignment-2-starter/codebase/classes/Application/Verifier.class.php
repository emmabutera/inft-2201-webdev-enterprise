<?php
namespace Application;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Verifier
{
    public $userId = null;
    public $role = null;

    public function decode($jwt)
    {
        if (!empty($jwt)) {
            // Trim whitespace
            $jwt = trim($jwt);

            // Remove "Bearer " prefix if present
            if (substr($jwt, 0, 7) === 'Bearer ') {
                $jwt = substr($jwt, 7);
            }

            try {
                // MUST match Node's JWT_SECRET
                $secret = "SUPER_RANDOM_SECRET_983274982374";

                $token = JWT::decode($jwt, new Key($secret, 'HS256'));

                $this->userId = $token->userId ?? null;
                $this->role   = $token->role ?? null;
            } catch (\Throwable $e) {
                // Invalid token → leave properties null
                $this->userId = null;
                $this->role = null;
            }
        }
    }

    public function isValid()
    {
        return !empty($this->userId) && !empty($this->role);
    }
}
