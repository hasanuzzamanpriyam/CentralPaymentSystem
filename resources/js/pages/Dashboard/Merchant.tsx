import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { useState } from 'react';

export default function MerchantDashboard({ totalVolume, totalTransactions, webhookUrl, hasApiKey, rawCredentials }) {
    const breadcrumbs = [
        {
            title: 'Merchant Dashboard',
            href: '/dashboard/merchant',
        },
    ];

    const { data, setData, post: postWebhook, processing: processingWebhook, errors } = useForm({
        webhook_url: webhookUrl || '',
    });

    const { post: postRegenerate, processing: processingRegenerate } = useForm();
    const [showWarning, setShowWarning] = useState(false);

    const submitWebhook = (e) => {
        e.preventDefault();
        postWebhook('/dashboard/merchant/webhook');
    };

    const confirmRegenerate = () => {
        setShowWarning(true);
    };

    const doRegenerate = () => {
        postRegenerate('/dashboard/merchant/api-key', {
            onSuccess: () => setShowWarning(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Merchant Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                
                {/* Stats Section */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="rounded-xl border border-sidebar-border bg-card p-6 shadow-sm">
                        <h2 className="text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Volume</h2>
                        <p className="mt-2 text-3xl font-bold tracking-tight text-primary">
                            {Number(totalVolume).toFixed(2)} <span className="text-xl font-normal text-muted-foreground">BDT</span>
                        </p>
                    </div>
                    <div className="rounded-xl border border-sidebar-border bg-card p-6 shadow-sm">
                        <h2 className="text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Transactions</h2>
                        <p className="mt-2 text-3xl font-bold tracking-tight text-primary">
                            {totalTransactions}
                        </p>
                    </div>
                </div>

                {/* Developer Settings Section */}
                <div className="rounded-xl border border-sidebar-border bg-card shadow-sm overflow-hidden">
                    <div className="p-6 border-b border-sidebar-border bg-muted/20">
                        <h3 className="text-lg font-semibold text-card-foreground">Developer Settings</h3>
                        <p className="text-sm text-muted-foreground mt-1">Manage your API keys and webhook integrations.</p>
                    </div>
                    
                    <div className="p-6 space-y-8">
                        {/* Webhook Form */}
                        <form onSubmit={submitWebhook} className="space-y-4 max-w-xl">
                            <div>
                                <label htmlFor="webhook_url" className="block text-sm font-medium text-foreground">
                                    Webhook URL
                                </label>
                                <div className="mt-2 flex shadow-sm rounded-md">
                                    <input
                                        type="url"
                                        name="webhook_url"
                                        id="webhook_url"
                                        className="block w-full min-w-0 flex-1 rounded-md border-input bg-background px-3 py-2 text-sm focus:border-primary focus:ring-primary dark:text-white"
                                        placeholder="https://your-domain.com/webhook"
                                        value={data.webhook_url}
                                        onChange={e => setData('webhook_url', e.target.value)}
                                    />
                                    <button
                                        type="submit"
                                        disabled={processingWebhook}
                                        className="ml-3 inline-flex justify-center rounded-md bg-primary py-2 px-4-sm text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 px-4"
                                    >
                                        Save
                                    </button>
                                </div>
                                {errors.webhook_url && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.webhook_url}</p>}
                                <p className="mt-2 text-xs text-muted-foreground">
                                    We will send POST requests to this URL when important events happen in your account.
                                </p>
                            </div>
                        </form>

                        <hr className="border-sidebar-border" />

                        {/* API Keys */}
                        <div>
                            <h4 className="text-md font-medium text-foreground">API Credentials</h4>
                            
                            {rawCredentials ? (
                                <div className="mt-4 p-4 border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800 rounded-md">
                                    <div className="flex items-center">
                                        <svg className="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                        </svg>
                                        <h5 className="text-sm font-medium text-green-800 dark:text-green-300">Credentials Generated Successfully</h5>
                                    </div>
                                    <div className="mt-3 text-sm text-green-700 dark:text-green-400">
                                        <p className="font-semibold mb-1">Please copy these now. You won't be able to see them again!</p>
                                        <div className="mt-2 bg-white dark:bg-black/40 p-3 rounded border border-green-200 dark:border-green-800 break-all shadow-inner">
                                            <div className="mb-2"><span className="text-muted-foreground font-mono text-xs">API KEY:</span><br /><code className="text-primary font-mono select-all">{rawCredentials.api_key}</code></div>
                                            <div><span className="text-muted-foreground font-mono text-xs">WEBHOOK SECRET:</span><br /><code className="text-primary font-mono select-all">{rawCredentials.webhook_secret}</code></div>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="mt-4">
                                    <p className="text-sm text-muted-foreground mb-4">
                                        {hasApiKey 
                                            ? "You have already generated API credentials. If you lost them, you must regenerate them."
                                            : "You haven't generated any API credentials yet."}
                                    </p>
                                    
                                    {!showWarning ? (
                                        <button
                                            onClick={confirmRegenerate}
                                            className="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:w-auto"
                                        >
                                            {hasApiKey ? "Regenerate API Credentials" : "Generate API Credentials"}
                                        </button>
                                    ) : (
                                        <div className="p-4 border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-900 rounded-md">
                                            <h5 className="text-sm font-medium text-red-800 dark:text-red-300">Are you absolutely sure?</h5>
                                            <p className="mt-1 text-sm text-red-700 dark:text-red-400">
                                                This will invalidate your current API Key and Webhook Secret. Any existing integrations using them will stop working immediately.
                                            </p>
                                            <div className="mt-4 flex space-x-3">
                                                <button
                                                    onClick={doRegenerate}
                                                    disabled={processingRegenerate}
                                                    className="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50"
                                                >
                                                    Yes, regenerate them
                                                </button>
                                                <button
                                                    onClick={() => setShowWarning(false)}
                                                    className="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
