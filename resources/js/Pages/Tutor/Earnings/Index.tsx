import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

type Stats = {
    totalEarned: string;
    totalPaid: string;
    pendingPayout: string;
    monthEarned: string;
    balance: string;
};

type RecentPayment = {
    id: number;
    amount: string;
    tutor_payout: string;
    commission_amount: string;
    paid_at: string | null;
    booking: {
        student: { name: string };
        subject: { name: string };
    };
    session: { session_date: string; duration_minutes: number | null } | null;
};

type Payout = {
    id: number;
    amount: string;
    sessions_count: number;
    period_start: string;
    period_end: string;
    status: string;
    paid_at: string | null;
    reference: string | null;
};

type Props = {
    stats: Stats;
    recentPayments: RecentPayment[];
    payouts: Payout[];
};

const payoutStatusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
};

function StatCard({ label, value, color, sub }: { label: string; value: string; color: string; sub?: string }) {
    return (
        <div className={`overflow-hidden rounded-xl bg-white p-5 shadow-sm border-l-4 ${color}`}>
            <p className="text-sm font-medium text-gray-500">{label}</p>
            <p className="mt-1 text-2xl font-bold text-gray-900">{value}</p>
            {sub && <p className="mt-0.5 text-xs text-gray-400">{sub}</p>}
        </div>
    );
}

export default function EarningsIndex({ stats, recentPayments, payouts }: Props) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">My Earnings</h2>}
        >
            <Head title="Earnings" />

            <>
                {/* Stats */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total Earned"
                        value={`RM ${Number(stats.totalEarned).toFixed(2)}`}
                        color="border-green-500"
                    />
                    <StatCard
                        label="This Month"
                        value={`RM ${Number(stats.monthEarned).toFixed(2)}`}
                        color="border-blue-500"
                    />
                    <StatCard
                        label="Total Paid Out"
                        value={`RM ${Number(stats.totalPaid).toFixed(2)}`}
                        color="border-purple-500"
                    />
                    <StatCard
                        label="Pending Payout"
                        value={`RM ${Number(stats.pendingPayout).toFixed(2)}`}
                        color="border-yellow-500"
                        sub={`Balance: RM ${Number(stats.balance).toFixed(2)}`}
                    />
                </div>

                {/* Tables */}
                <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Recent Earnings */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Recent Earnings</h3>
                        </div>
                        <div className="divide-y">
                            {recentPayments.length === 0 && (
                                <p className="p-6 text-center text-sm text-gray-400">No earnings yet.</p>
                            )}
                            {recentPayments.map((p) => (
                                <div key={p.id} className="flex items-center justify-between px-6 py-3">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{p.booking.student.name} — {p.booking.subject.name}</p>
                                        <p className="text-xs text-gray-500">
                                            {p.session?.session_date ?? '-'}
                                            {p.session?.duration_minutes ? ` · ${p.session.duration_minutes} min` : ''}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-bold text-green-600">+RM {Number(p.tutor_payout).toFixed(2)}</p>
                                        <p className="text-xs text-gray-400">of RM {Number(p.amount).toFixed(2)}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Payout History */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Payout History</h3>
                        </div>
                        <div className="divide-y">
                            {payouts.length === 0 && (
                                <p className="p-6 text-center text-sm text-gray-400">No payouts yet.</p>
                            )}
                            {payouts.map((po) => (
                                <div key={po.id} className="flex items-center justify-between px-6 py-3">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">RM {Number(po.amount).toFixed(2)}</p>
                                        <p className="text-xs text-gray-500">{po.period_start} — {po.period_end} · {po.sessions_count} session(s)</p>
                                    </div>
                                    <div className="text-right">
                                        <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${payoutStatusColors[po.status]}`}>
                                            {po.status}
                                        </span>
                                        {po.reference && <p className="mt-0.5 text-xs text-gray-400">{po.reference}</p>}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </>
        </AuthenticatedLayout>
    );
}
