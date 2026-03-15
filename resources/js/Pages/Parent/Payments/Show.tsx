import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type Payment = {
    id: number;
    amount: string;
    commission_amount: string;
    tutor_payout: string;
    payment_method: string | null;
    transaction_id: string | null;
    status: string;
    paid_at: string | null;
    created_at: string;
    booking: {
        id: number;
        hourly_rate: string;
        duration_hours: string;
        schedule_day: string;
        tutor: { name: string };
        student: { name: string };
        subject: { name: string };
    };
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
    const handlePay = () => {
        if (confirm('Confirm payment of RM ' + Number(payment.amount).toFixed(2) + '?')) {
            router.post(route('parent.payments.pay', payment.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Payment Details</h2>}
        >
            <Head title="Payment Details" />

            <>
                <div className="mb-4">
                    <Link href={route('parent.payments.index')} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>

                <div className="mx-auto max-w-2xl">
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <div className="flex items-center justify-between">
                                <h3 className="font-semibold text-gray-900">Payment #{payment.id}</h3>
                                <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[payment.status]}`}>
                                    {payment.status}
                                </span>
                            </div>
                        </div>
                        <div className="divide-y px-6">
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
                            {payment.session && (
                                <div className="flex justify-between py-3">
                                    <span className="text-sm text-gray-500">Session Date</span>
                                    <span className="text-sm text-gray-900">{payment.session.session_date}</span>
                                </div>
                            )}
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Rate</span>
                                <span className="text-sm text-gray-900">RM {Number(payment.booking.hourly_rate).toFixed(2)}/hr × {Number(payment.booking.duration_hours).toFixed(1)}hr</span>
                            </div>
                            <div className="flex justify-between py-3">
                                <span className="text-sm text-gray-500">Total Amount</span>
                                <span className="text-lg font-bold text-gray-900">RM {Number(payment.amount).toFixed(2)}</span>
                            </div>
                            {payment.payment_method && (
                                <div className="flex justify-between py-3">
                                    <span className="text-sm text-gray-500">Payment Method</span>
                                    <span className="text-sm text-gray-900 uppercase">{payment.payment_method}</span>
                                </div>
                            )}
                            {payment.paid_at && (
                                <div className="flex justify-between py-3">
                                    <span className="text-sm text-gray-500">Paid At</span>
                                    <span className="text-sm text-gray-900">{payment.paid_at}</span>
                                </div>
                            )}
                        </div>

                        {payment.status === 'pending' && (
                            <div className="border-t px-6 py-4">
                                <button
                                    onClick={handlePay}
                                    className="w-full rounded-md bg-green-600 px-4 py-3 text-sm font-medium text-white hover:bg-green-700"
                                >
                                    Pay RM {Number(payment.amount).toFixed(2)}
                                </button>
                                <p className="mt-2 text-center text-xs text-gray-400">Payment will be processed via FPX</p>
                            </div>
                        )}

                        {payment.status === 'success' && (
                            <div className="border-t px-6 py-4">
                                <div className="flex items-center justify-center gap-2 text-green-600">
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span className="text-sm font-medium">Payment Completed</span>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </>
        </AuthenticatedLayout>
    );
}
