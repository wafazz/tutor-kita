import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Payout = {
    id: number;
    amount: string;
    sessions_count: number;
    period_start: string;
    period_end: string;
    status: string;
    paid_at: string | null;
    reference: string | null;
    tutor: { name: string };
};

type Props = {
    payouts: { data: Payout[]; links: any[] };
    totals: { pending: string; processing: string; paid: string };
    filters: { status?: string };
};

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
};

export default function PayoutsIndex({ payouts, totals, filters }: Props) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Tutor Payouts</h2>}
        >
            <Head title="Payouts" />

            <>
                {/* Summary */}
                <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="overflow-hidden rounded-xl bg-white p-5 shadow-sm border-l-4 border-yellow-500">
                        <p className="text-sm font-medium text-gray-500">Pending</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">RM {Number(totals.pending).toFixed(2)}</p>
                    </div>
                    <div className="overflow-hidden rounded-xl bg-white p-5 shadow-sm border-l-4 border-blue-500">
                        <p className="text-sm font-medium text-gray-500">Processing</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">RM {Number(totals.processing).toFixed(2)}</p>
                    </div>
                    <div className="overflow-hidden rounded-xl bg-white p-5 shadow-sm border-l-4 border-green-500">
                        <p className="text-sm font-medium text-gray-500">Paid Out</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">RM {Number(totals.paid).toFixed(2)}</p>
                    </div>
                </div>

                {/* Filters + Create */}
                <div className="mb-4 flex items-center justify-between">
                    <div className="flex gap-2">
                        {['', 'pending', 'processing', 'paid'].map((s) => (
                            <Link
                                key={s}
                                href={route('admin.payouts.index', s ? { status: s } : {})}
                                className={`rounded-md px-3 py-1 text-xs font-medium ${
                                    (filters.status ?? '') === s
                                        ? 'bg-gray-800 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-100'
                                }`}
                                preserveScroll
                            >
                                {s ? s.charAt(0).toUpperCase() + s.slice(1) : 'All'}
                            </Link>
                        ))}
                    </div>
                    <Link
                        href={route('admin.payouts.create')}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Create Payout
                    </Link>
                </div>

                {/* Table */}
                <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">ID</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Period</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sessions</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Paid At</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                            {payouts.data.map((payout) => (
                                <tr key={payout.id} className="hover:bg-gray-50">
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">#{payout.id}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{payout.tutor.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{payout.period_start} — {payout.period_end}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{payout.sessions_count}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">RM {Number(payout.amount).toFixed(2)}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[payout.status]}`}>
                                            {payout.status}
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{payout.paid_at ?? '-'}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        <Link href={route('admin.payouts.show', payout.id)} className="text-indigo-600 hover:text-indigo-900">View</Link>
                                    </td>
                                </tr>
                            ))}
                            {payouts.data.length === 0 && (
                                <tr>
                                    <td colSpan={8} className="px-6 py-4 text-center text-sm text-gray-500">No payouts found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex justify-center space-x-1">
                    {payouts.links.map((link: any, i: number) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            className={`rounded px-3 py-1 text-sm ${
                                link.active ? 'bg-gray-800 text-white' : link.url ? 'bg-white text-gray-700 hover:bg-gray-100' : 'cursor-not-allowed bg-gray-100 text-gray-400'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            preserveScroll
                        />
                    ))}
                </div>
            </>
        </AuthenticatedLayout>
    );
}
