import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function PersonalDashboard({ wallet, transactions }) {
    const breadcrumbs = [
        {
            title: 'Personal Dashboard',
            href: '/dashboard/personal',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Personal Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                
                {/* Wallet Balance Card */}
                <div className="rounded-xl border border-sidebar-border bg-card p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-card-foreground">My Wallet</h2>
                    <p className="mt-2 text-4xl font-bold tracking-tight text-primary">
                        {wallet.balance} <span className="text-2xl font-normal text-muted-foreground">{wallet.currency}</span>
                    </p>
                </div>

                {/* Transaction History Table */}
                <div className="flex-1 rounded-xl border border-sidebar-border bg-card shadow-sm overflow-hidden">
                    <div className="p-6 border-b border-sidebar-border">
                        <h3 className="text-lg font-semibold text-card-foreground">Transaction History</h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left">
                            <thead className="bg-muted/50 text-muted-foreground uppercase text-xs">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Date</th>
                                    <th className="px-6 py-4 font-medium">Type</th>
                                    <th className="px-6 py-4 font-medium">Amount</th>
                                    <th className="px-6 py-4 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-sidebar-border">
                                {transactions.data.length === 0 ? (
                                    <tr>
                                        <td colSpan="4" className="px-6 py-8 text-center text-muted-foreground">
                                            No transactions yet.
                                        </td>
                                    </tr>
                                ) : (
                                    transactions.data.map((tx) => (
                                        <tr key={tx.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {new Date(tx.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap capitalize">
                                                {tx.type.replace('_', ' ')}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap font-medium">
                                                {tx.amount}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2.5 py-1 rounded-full text-xs font-medium 
                                                    ${tx.status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ''}
                                                    ${tx.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : ''}
                                                    ${tx.status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : ''}
                                                    ${tx.status === 'refunded' ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' : ''}
                                                `}>
                                                    {tx.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </AppLayout>
    );
}
