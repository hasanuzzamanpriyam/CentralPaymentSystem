import { Head, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { useState } from 'react';
import axios from 'axios';

export default function DemoStore() {
    const { isMerchant } = usePage().props;
    
    const [amount, setAmount] = useState('150.00');
    const [gateway, setGateway] = useState('stripe');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const breadcrumbs = [
        {
            title: 'Demo Store',
            href: '/demo/store',
        },
    ];

    const generateIdempotencyKey = () => setAmount(amount); // trick to just rerender if needed, but we'll use a real UUID below.
    const getGuid = () => {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    const handleCheckout = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            // Using Sanctum session auth to hit the api endpoints
            const response = await axios.post('/api/payments/intent', {
                amount: amount,
                currency: 'BDT',
                gateway: gateway,
                metadata: {
                    order_id: 'ORDER-' + Math.floor(Math.random() * 10000),
                    product: 'Premium Tech Gadget'
                }
            }, {
                headers: {
                    'Idempotency-Key': getGuid()
                }
            });

            if (response.data.transaction_id) {
                // Redirect user to the mock checkout page
                window.location.href = `/demo/checkout/${response.data.transaction_id}`;
            } else {
                setError('Failed to create payment intent. No transaction ID returned.');
            }
        } catch (err) {
            setError(err.response?.data?.error || err.response?.data?.message || 'An error occurred during checkout validation.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sandbox Store" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-6 bg-muted/10">
                <div className="w-full max-w-lg rounded-xl border border-sidebar-border bg-card p-8 shadow-lg">
                    <div className="text-center mb-8">
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">NextGen Electronics</h1>
                        <p className="text-sm text-muted-foreground mt-2">Simulate a customer making a purchase on your external application.</p>
                    </div>

                    <form onSubmit={handleCheckout} className="space-y-6">
                        {error && (
                            <div className="p-3 text-sm text-red-600 bg-red-50 dark:bg-red-900/10 rounded-md border border-red-200 dark:border-red-900/50">
                                {error}
                            </div>
                        )}

                        <div className="p-4 rounded-lg bg-muted/40 border border-sidebar-border flex items-center justify-between">
                            <div className="flex flex-col">
                                <span className="font-semibold">Premium Wireless Headphones</span>
                                <span className="text-xs text-muted-foreground mt-1 text-left">SKU: HEAD-99X</span>
                            </div>
                            <div className="text-right">
                                <label className="text-xs text-muted-foreground block mb-1">Price (BDT)</label>
                                <input 
                                    type="number"
                                    step="0.01"
                                    value={amount}
                                    onChange={(e) => setAmount(e.target.value)}
                                    className="w-24 px-2 py-1 text-right text-sm border-sidebar-border rounded-md bg-background focus:ring-primary focus:border-primary"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-foreground mb-3">Select Payment Gateway</label>
                            <div className="grid grid-cols-3 gap-3">
                                {[
                                    { id: 'stripe', name: 'Stripe' },
                                    { id: 'bkash', name: 'bKash' },
                                    { id: 'sslcommerz', name: 'SSLCommerz' }
                                ].map(g => (
                                    <label 
                                        key={g.id}
                                        className={`
                                            cursor-pointer flex items-center justify-center px-3 py-3 rounded-md border text-sm font-medium transition-colors
                                            ${gateway === g.id 
                                                ? 'bg-primary/10 border-primary text-primary' 
                                                : 'bg-background border-sidebar-border text-foreground hover:bg-muted/50'}
                                        `}
                                    >
                                        <input
                                            type="radio"
                                            name="gateway"
                                            value={g.id}
                                            checked={gateway === g.id}
                                            onChange={() => setGateway(g.id)}
                                            className="sr-only"
                                        />
                                        <span>{g.name}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <button
                            type="submit"
                            disabled={loading || !isMerchant}
                            className="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-primary-foreground bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 transition-colors"
                        >
                            {loading ? 'Processing...' : (!isMerchant ? 'Merchants Only' : 'Pay Now')}
                        </button>
                    </form>

                    {!isMerchant && (
                        <p className="mt-4 text-xs text-center text-amber-600 dark:text-amber-500 font-medium">
                            Warning: You are logged into a Personal account. The orchestrator API will fail because you cannot create a merchant payment intent.
                        </p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
