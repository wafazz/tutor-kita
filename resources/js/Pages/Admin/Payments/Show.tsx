import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type Payment = {
    id: number;
    amount: string;
    commission_amount: string;
    tutor_payout: string;
    payment_method: string | null;
    gateway: string | null;
    transaction_id: string | null;
    status: string;
    paid_at: string | null;
    created_at: string;
    booking: {
        id: number;
        hourly_rate: string;
        commission_rate: string;
        duration_hours: string;
        tutor: { name: string };
        student: { name: string };
        subject: { name: string };
    };
    parent: { name: string };
    session: { id: number; session_date: string; duration_minutes: number | null } | null;
};

type Props = { payment: Payment };

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    success: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
};

export default function PaymentShow({ payment }: Props) {
    const handleMarkPaid = () => {
        router.post(route('admin.payments.mark-paid', payment.id));
    };

    const handleMarkFailed = () => {
        if (confirm('Mark this payment as failed?')) {
            router.post(route('admin.payments.mark-failed', payment.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Payment #{payment.id}</h2>}
        >
            <Head title={`Payment #${payment.id}`} />

            <>
                <div className="mb-4">
                    <Link href={route('admin.payments.index')} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Payment Details */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Payment Details</h3>
                        </div>
                        <div className="divide-y px-6">
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Status</span>
                                <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[payment.status]}`}>
                                    {payment.status}
                                </span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Amount</span>
                                <span className="text-sm font-bold text-gray-900">RM {Number(payment.amount).toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Commission ({Number(payment.booking.commission_rate).toFixed(0)}%)</span>
                                <span className="text-sm text-gray-900">RM {Number(payment.commission_amount).toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Tutor Payout</span>
                                <span className="text-sm text-gray-900">RM {Number(payment.tutor_payout).toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Method</span>
                                <span className="text-sm text-gray-900 uppercase">{payment.payment_method ?? '-'}</span>
                            </div>
                            {payment.transaction_id && (
                                <div className="flex justify-between py-3">
                                    <span className="text-sm text-gray-500">Transaction ID</span>
                                    <span className="text-sm text-gray-900">{payment.transaction_id}</span>
                                </div>
                            )}
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Paid At</span>
                                <span className="text-sm text-gray-900">{payment.paid_at ?? '-'}</span>
                            </div>
                        </div>
                    </div>

                    {/* Booking Info */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Booking Info</h3>
                        </div>
                        <div className="divide-y px-6">
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Parent</span>
                                <span className="text-sm text-gray-900">{payment.parent.name}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Tutor</span>
                                <span className="text-sm text-gray-900">{payment.booking.tutor.name}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Student</span>
                                <span className="text-sm text-gray-900">{payment.booking.student.name}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Subject</span>
                                <span className="text-sm text-gray-900">{payment.booking.subject.name}</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Rate</span>
                                <span className="text-sm text-gray-900">RM {Number(payment.booking.hourly_rate).toFixed(2)}/hr × {Number(payment.booking.duration_hours).toFixed(1)}hr</span>
                            </div>
                            {payment.session && (
                                <div className="flex justify-between py-3">
                                    <span className="text-sm text-gray-500">Session Date</span>
                                    <span className="text-sm text-gray-900">{payment.session.session_date}</span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Admin Actions */}
                {payment.status === 'pending' && (
                    <div className="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Admin Actions</h3>
                        </div>
                        <div className="flex gap-3 px-6 py-4">
                            <button
                                onClick={handleMarkPaid}
                                className="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                            >
                                Mark as Paid
                            </button>
                            <button
                                onClick={handleMarkFailed}
                                className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                            >
                                Mark as Failed
                            </button>
                        </div>
                    </div>
                )}
            </>
        </AuthenticatedLayout>
    );
}
