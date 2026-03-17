from typing import Dict, Any, Optional
import requests
from requests.exceptions import RequestException

from .webhooks.verifier import WebhookVerifier

class CentralPaymentClient:
    """
    Official Python SDK Client for the Central Payment System.
    """

    def __init__(self, api_key: str, project_id: str, environment: str = 'sandbox'):
        if not api_key or not project_id:
            raise ValueError("api_key and project_id are required to initialize CentralPaymentClient")

        self.api_key = api_key
        self.project_id = project_id
        
        self.base_url = 'https://api.centralpayment.com/api' if environment == 'production' else 'http://127.0.0.1:8000/api'
        
        self.session = requests.Session()
        self.session.headers.update({
            'Authorization': f'Bearer {self.api_key}',
            'X-Project-Id': str(self.project_id),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        })

    def get_gateways(self) -> list:
        """
        Fetch active gateways and their required credential formats for this project.
        """
        response = self.session.get(f"{self.base_url}/projects/{self.project_id}/gateways")
        response.raise_for_status()
        return response.json().get('gateways', [])

    def create_payment(self, amount: float, currency: str, gateway: str, 
                       metadata: Optional[Dict[str, Any]] = None, 
                       idempotency_key: Optional[str] = None) -> Dict[str, Any]:
        """
        Create a new payment intent and obtain a checkout URL.
        """
        headers = {}
        if idempotency_key:
            headers['Idempotency-Key'] = idempotency_key

        payload = {
            'amount': amount,
            'currency': currency,
            'gateway': gateway,
            'project_id': self.project_id,
        }
        
        if metadata is not None:
            payload['metadata'] = metadata

        response = self.session.post(
            f"{self.base_url}/payments/intent", 
            json=payload, 
            headers=headers
        )
        response.raise_for_status()
        return response.json()

    @staticmethod
    def verify_webhook(raw_payload: str, signature: str, webhook_secret: str) -> bool:
        """
        Utility method to verify incoming webhooks from the Central Payment System
        """
        return WebhookVerifier.verify_signature(raw_payload, signature, webhook_secret)
