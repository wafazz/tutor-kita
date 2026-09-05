import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

type Payout = {
    id: number;
    amount: string;
    sessions_count: number;
    period_start: string;
    period_end: string;
    status: string;
    paid_at: string | null;
    reference: string | null;
    tutor: { name: string; email: string };
};

type PayoutBooking = {
    id: number;
    amount: string;
    tutor_payout: string;
    commission_amount: string;
    student: { name: string } | null;
    subject: { name: string } | null;
    payment: { paid_at: string | null } | null;
    sessions: { id: number; session_date: string; duration_minutes: number | null }[];
    pivot: { amount: string } | null;
};

type Props = {
    payout: Payout;
    bookings: PayoutBooking[];
    bankDetails: {
        bank_name: string;
        bank_account_number: string;
        bank_account_name: string;
    } | null;
};

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
};

export default function PayoutShow({ payout, bookings, bankDetails }: Props) {
    const { data, setData, post, processing } = useForm({ reference: '' });

    const handleMarkProcessing = () => {
        router.post(route('admin.payouts.processing', payout.id));
    };

    const handleMarkPaid = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.payouts.paid', payout.id));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Payout #{payout.id}</h2>}
        >
            <Head title={`Payout #${payout.id}`} />

            <>
                <div className="mb-4">
                    <Link href={route('admin.payouts.index')} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Payout Details */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Payout Details</h3>
                        </div>
                        <div className="divide-y px-6">
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Tutor</span>
                                <span className="text-sm text-gray-900">{payout.tutor.name}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Email</span>
                                <span className="text-sm text-gray-900">{payout.tutor.email}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Period</span>
                                <span className="text-sm text-gray-900">{payout.period_start} — {payout.period_end}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Sessions</span>
                                <span className="text-sm text-gray-900">{payout.sessions_count}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Amount</span>
                                <span className="text-sm font-bold text-gray-900">RM {Number(payout.amount).toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Status</span>
                                <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[payout.status]}`}>
                                    {payout.status}
                                </span>
                            </div>
                            {payout.reference && (
                                <div className="flex justify-between py-3">
                                    <span className="text-sm text-gray-500">Reference</span>
                                    <span className="text-sm text-gray-900">{payout.reference}</span>
                                </div>
                            )}
                            {payout.paid_at && (
                                <div className="flex justify-between py-3">
                                    <span className="text-sm text-gray-500">Paid At</span>
                                    <span className="text-sm text-gray-900">{payout.paid_at}</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Where the money goes */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Payout destination</h3>
                        </div>
                        <div className="px-6 py-4">
                            {bankDetails ? (
                                <dl className="divide-y divide-gray-200">
                                    <div className="flex justify-between py-3">
                                        <dt className="text-sm text-gray-500">Bank</dt>
                                        <dd className="text-sm text-gray-900">{bankDetails.bank_name}</dd>
                                    </div>
                                    <div className="flex justify-between py-3">
                                        <dt className="text-sm text-gray-500">Account number</dt>
                                        <dd className="font-mono text-sm text-gray-900">{bankDetails.bank_account_number}</dd>
                                    </div>
                                    <div className="flex justify-between py-3">
                                        <dt className="text-sm text-gray-500">Account holder</dt>
                                        <dd className="text-sm text-gray-900">{bankDetails.bank_account_name}</dd>
                                    </div>
                                </dl>
                            ) : (
                                <div className="rounded-lg bg-amber-50 p-4">
                                    <p className="text-sm font-medium text-amber-800">No bank details on file</p>
                                    <p className="mt-1 text-sm text-amber-700">
                                        This tutor has not completed their payout details, so this payout cannot be
                                        transferred yet. Ask them to fill in the payout section of their profile.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Actions */}
                    {payout.status !== 'paid' && (
                        <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                            <div className="border-b px-6 py-4">
                                <h3 className="font-semibold text-gray-900">Actions</h3>
                            </div>
                            <div className="px-6 py-4 space-y-4">
                                {payout.status === 'pending' && (
                                    <button
                                        onClick={handleMarkProcessing}
                                        className="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                    >
                                        Mark as Processing
                                    </button>
                                )}

                                <form onSubmit={handleMarkPaid} className="space-y-3">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Payment Reference</label>
                                        <input
                                            type="text"
                                            value={data.reference}
                                            onChange={(e) => setData('reference', e.target.value)}
                                            placeholder="e.g. Bank transfer ref"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                                    >
                                        Mark as Paid
                                    </button>
                                </form>
                            </div>
                        </div>
                    )}
                </div>

                {/* Session Breakdown */}
                <div className="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
                    <div className="border-b px-6 py-4">
                        <h3 className="font-semibold text-gray-900">Booking Breakdown ({bookings.length})</h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Commission</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor Share</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {bookings.map((s) => (
                                    <tr key={s.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{s.sessions.length > 0 ? s.sessions[0].session_date : '-'}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{s.student?.name ?? '-'}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-500">{s.subject?.name ?? '-'}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-500">{s.sessions.length > 0 ? `${s.sessions.length} session(s)` : '-'}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">RM {Number(s.amount).toFixed(2)}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-500">RM {Number(s.commission_amount).toFixed(2)}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm font-medium text-gray-900">RM {Number(s.pivot?.amount ?? s.tutor_payout).toFixed(2)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </>
        </AuthenticatedLayout>
    );
}
