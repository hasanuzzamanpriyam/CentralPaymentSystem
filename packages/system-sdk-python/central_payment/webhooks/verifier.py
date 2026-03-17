import hmac
import hashlib
from typing import Dict, Any, Optional
import requests

class WebhookVerifier:
    @staticmethod
    def verify_signature(raw_payload: str, signature: str, webhook_secret: str) -> bool:
        """
        Verify the HMAC SHA-256 signature of an incoming webhook.
        """
        if not signature or not webhook_secret:
            return False

        # Create HMAC-SHA256 signature
        expected_signature = hmac.new(
            webhook_secret.encode('utf-8'),
            raw_payload.encode('utf-8'),
            hashlib.sha256
        ).hexdigest()

        # Constant time comparison to prevent timing attacks
        return hmac.compare_digest(expected_signature, signature)
