import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';

export default function GatewayCheckout({ transaction }) {
    const [status, setStatus] = useState('idle'); // idle, processing, success, error
    const [errorMsg, setErrorMsg] = useState('');

    const handleMockPayment = async () => {
        setStatus('processing');
        setErrorMsg('');

        try {
            // We simulate the external gateway sending a webhook back to our system
            const payload = {
                type: 'checkout.session.completed',
                data: {
                    object: {
                        id: 'mock_stripe_tx_' + Math.floor(Math.random() * 1000000),
                        client_reference_id: transaction.id,
                        amount_total: transaction.amount * 100, // Stripe uses cents usually
                        currency: 'bdt',
                        payment_status: 'paid'
                    }
                }
            };

            await axios.post('/api/webhooks/stripe', payload, {
                headers: {
                    'Stripe-Signature': 'mock_signature_12345'
                }
            });

            setStatus('success');
            
            // Redirect back to dashboard after a short delay
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 2000);

        } catch (err) {
            setStatus('error');
            setErrorMsg(err.response?.data?.error || 'Failed to process mock webhook.');
        }
    };

    if (status === 'success') {
        return (
            <div className="min-h-screen bg-green-50 flex items-center justify-center p-4">
                <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center border border-green-100">
                    <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg className="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h2 className="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h2>
                    <p className="text-gray-600 mb-8">The webhook was delivered and the transaction is complete.</p>
                    <p className="text-sm text-gray-400">Redirecting to dashboard...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-100 flex items-center justify-center p-4 font-sans">
            <Head title="Mock Checkout" />
            
            <div className="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">
                {/* Header */}
                <div className="bg-slate-900 px-6 py-6 text-center text-white relative">
                    <div className="absolute top-4 left-4 text-xs font-mono bg-white/20 px-2 py-1 rounded">
                        TEST MODE
                    </div>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg" alt="Stripe" className="h-8 mx-auto invert opacity-90 mb-4 mt-2" />
                    <h1 className="text-slate-300 text-sm font-medium">Payment to Test Merchant</h1>
                    <p className="text-4xl font-bold mt-2 font-mono">
                        {transaction.amount} <span className="text-xl font-normal text-slate-400">{transaction.gateway === 'stripe' ? 'USD/BDT' : 'BDT'}</span>
                    </p>
                </div>

                {/* Body */}
                <div className="p-6 space-y-6">
                    {status === 'error' && (
                        <div className="bg-red-50 text-red-600 p-3 rounded-md text-sm border border-red-100">
                            {errorMsg}
                        </div>
                    )}

                    <div className="space-y-3">
                        <h3 className="font-semibold text-gray-800">Payment Details</h3>
                        <div className="flex justify-between text-sm border-b pb-2">
                            <span className="text-gray-500">Transaction ID</span>
                            <span className="font-mono text-gray-700">{transaction.id}</span>
                        </div>
                        <div className="flex justify-between text-sm border-b pb-2">
                            <span className="text-gray-500">Intended Gateway</span>
                            <span className="font-medium text-gray-700 capitalize">{transaction.gateway}</span>
                        </div>
                        <div className="flex justify-between text-sm pb-2">
                            <span className="text-gray-500">Status</span>
                            <span className="font-medium text-yellow-600 bg-yellow-50 px-2 py-0.5 rounded capitalize">{transaction.status}</span>
                        </div>
                    </div>

                    <div className="pt-4">
                        <button
                            onClick={handleMockPayment}
                            disabled={status === 'processing'}
                            className="w-full bg-[#635BFF] hover:bg-[#4B45D6] text-white font-medium py-3 px-4 rounded-xl shadow-sm transition-colors focus:ring-4 focus:ring-[#635BFF]/30 disabled:opacity-75 disabled:cursor-not-allowed flex justify-center items-center"
                        >
                            {status === 'processing' ? (
                                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            ) : null}
                            {status === 'processing' ? 'Processing...' : 'Simulate Successful Payment'}
                        </button>
                    </div>

                    <div className="text-center mt-6">
                        <Link href="/demo/store" className="text-sm text-gray-500 hover:text-gray-800">
                            Cancel and return to store
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
