import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Payment = {
    id: number;
    amount: string;
    commission_amount: string;
    tutor_payout: string;
    payment_method: string | null;
    status: string;
    paid_at: string | null;
    created_at: string;
    booking: {
        tutor: { name: string };
        student: { name: string };
        subject: { name: string };
    };
    parent: { name: string };
    session: { session_date: string } | null;
};

type Props = {
    payments: { data: Payment[]; links: any[] };
    totals: { total: string; commission: string; tutorPayout: string; pending: string };
    filters: { status?: string };
};

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    success: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
};

export default function PaymentsIndex({ payments, totals, filters }: Props) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Payments</h2>}
        >
            <Head title="Payments" />

            <>
                {/* Summary Cards */}
                <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="overflow-hidden rounded-xl bg-white p-5 shadow-sm border-l-4 border-green-500">
                        <p className="text-sm font-medium text-gray-500">Total Collected</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">RM {Number(totals.total).toFixed(2)}</p>
                    </div>
                    <div className="overflow-hidden rounded-xl bg-white p-5 shadow-sm border-l-4 border-blue-500">
                        <p className="text-sm font-medium text-gray-500">Commission Earned</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">RM {Number(totals.commission).toFixed(2)}</p>
                    </div>
                    <div className="overflow-hidden rounded-xl bg-white p-5 shadow-sm border-l-4 border-purple-500">
                        <p className="text-sm font-medium text-gray-500">Tutor Payouts</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">RM {Number(totals.tutorPayout).toFixed(2)}</p>
                    </div>
                    <div className="overflow-hidden rounded-xl bg-white p-5 shadow-sm border-l-4 border-yellow-500">
                        <p className="text-sm font-medium text-gray-500">Pending Payments</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">RM {Number(totals.pending).toFixed(2)}</p>
                    </div>
                </div>

                {/* Filters */}
                <div className="mb-4 flex items-center justify-between">
                    <p className="text-sm text-gray-500">{payments.data.length} payment(s)</p>
                    <div className="flex gap-2">
                        {['', 'pending', 'success', 'failed', 'refunded'].map((s) => (
                            <Link
                                key={s}
                                href={route('admin.payments.index', s ? { status: s } : {})}
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
                </div>

                {/* Table */}
                <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">ID</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Parent</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Commission</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                            {payments.data.map((payment) => (
                                <tr key={payment.id} className="hover:bg-gray-50">
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">#{payment.id}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {payment.session?.session_date ?? payment.created_at.split('T')[0]}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{payment.parent.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{payment.booking.tutor.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{payment.booking.subject.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">RM {Number(payment.amount).toFixed(2)}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">RM {Number(payment.commission_amount).toFixed(2)}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[payment.status] ?? 'bg-gray-100 text-gray-800'}`}>
                                            {payment.status}
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        <Link href={route('admin.payments.show', payment.id)} className="text-indigo-600 hover:text-indigo-900">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {payments.data.length === 0 && (
                                <tr>
                                    <td colSpan={9} className="px-6 py-4 text-center text-sm text-gray-500">No payments found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex justify-center space-x-1">
                    {payments.links.map((link: any, i: number) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            className={`rounded px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-gray-800 text-white'
                                    : link.url
                                      ? 'bg-white text-gray-700 hover:bg-gray-100'
                                      : 'cursor-not-allowed bg-gray-100 text-gray-400'
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
