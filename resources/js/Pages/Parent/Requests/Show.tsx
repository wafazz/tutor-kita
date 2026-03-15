import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type RequestFull = {
    id: number;
    student: { name: string };
    subject: { name: string };
    package: { name: string; total_sessions: number; duration_hours: number; price: number } | null;
    preferred_area: string;
    preferred_schedule: string | null;
    budget_min: number | null;
    budget_max: number | null;
    status: string;
    tutor_accepted: boolean;
    matched_tutor: { name: string } | null;
    notes: string | null;
    created_at: string;
    payment: {
        id: number;
        amount: number;
        status: string;
        paid_at: string | null;
    } | null;
};

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        open: 'bg-blue-100 text-blue-800',
        matched: 'bg-yellow-100 text-yellow-800',
        closed: 'bg-green-100 text-green-800',
        cancelled: 'bg-gray-100 text-gray-800',
    };
    return map[status] ?? 'bg-gray-100 text-gray-800';
};

const statusLabel = (status: string, tutorAccepted: boolean) => {
    if (status === 'matched' && !tutorAccepted) return 'Awaiting Tutor Confirmation';
    if (status === 'matched' && tutorAccepted) return 'Awaiting Payment';
    const map: Record<string, string> = {
        open: 'Finding Tutor',
        closed: 'Active',
        cancelled: 'Cancelled',
    };
    return map[status] ?? status;
};

export default function RequestsShow({ tutorRequest }: { tutorRequest: RequestFull }) {
    const handleCancel = () => {
        if (confirm('Are you sure you want to cancel this request?')) {
            router.post(route('parent.requests.cancel', tutorRequest.id));
        }
    };

    const handlePay = () => {
        if (tutorRequest.payment) {
            router.post(route('parent.payments.pay', tutorRequest.payment.id));
        }
    };

    const waitingTutor = tutorRequest.status === 'matched' && !tutorRequest.tutor_accepted;
    const showPayment = tutorRequest.status === 'matched' && tutorRequest.tutor_accepted && tutorRequest.payment && tutorRequest.payment.status === 'pending';
    const isPaid = tutorRequest.payment?.status === 'success';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Request Details</h2>
                    <Link
                        href={route('parent.requests.index')}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title="Request Details" />

            <div className="mx-auto max-w-3xl space-y-6">
                {/* Status Banner */}
                <div className={`rounded-xl p-4 ${
                    showPayment ? 'bg-yellow-50 border border-yellow-200' :
                    isPaid ? 'bg-green-50 border border-green-200' :
                    'bg-white shadow-sm'
                }`}>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <span className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold ${statusBadge(tutorRequest.status)}`}>
                                {statusLabel(tutorRequest.status, tutorRequest.tutor_accepted)}
                            </span>
                            <span className="text-sm text-gray-500">
                                Created {new Date(tutorRequest.created_at).toLocaleDateString()}
                            </span>
                        </div>
                        {isPaid && (
                            <span className="inline-flex items-center gap-1 text-sm font-medium text-green-700">
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Paid
                            </span>
                        )}
                    </div>
                </div>

                {/* Waiting for Tutor */}
                {waitingTutor && (
                    <div className="rounded-xl border border-yellow-200 bg-yellow-50 p-6">
                        <div className="flex items-start gap-4">
                            <div className="rounded-full bg-yellow-100 p-3">
                                <svg className="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-yellow-800">Waiting for Tutor Confirmation</h3>
                                <p className="mt-1 text-sm text-yellow-700">
                                    A tutor has been assigned to this request. We're waiting for them to accept the job.
                                    Once accepted, you'll be able to make payment.
                                </p>
                                {tutorRequest.matched_tutor && (
                                    <p className="mt-2 text-sm text-yellow-700">
                                        Assigned Tutor: <span className="font-semibold">{tutorRequest.matched_tutor.name}</span>
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Payment Alert */}
                {showPayment && (
                    <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-500 to-purple-700 p-6 text-white shadow-lg">
                        <div className="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white/10" />
                        <div className="relative z-10">
                            <div className="flex items-start justify-between">
                                <div>
                                    <h3 className="text-lg font-bold">Payment Required</h3>
                                    <p className="mt-1 text-sm text-indigo-200">
                                        A tutor has been assigned. Complete the payment to activate your booking.
                                    </p>
                                    {tutorRequest.matched_tutor && (
                                        <p className="mt-2 text-sm text-indigo-100">
                                            Assigned Tutor: <span className="font-semibold text-white">{tutorRequest.matched_tutor.name}</span>
                                        </p>
                                    )}
                                </div>
                                <div className="text-right">
                                    <p className="text-sm text-indigo-200">Amount</p>
                                    <p className="text-3xl font-bold">RM {Number(tutorRequest.payment!.amount).toFixed(2)}</p>
                                </div>
                            </div>

                            {tutorRequest.package && (
                                <div className="mt-4 flex gap-4">
                                    <div className="rounded-lg bg-white/10 px-3 py-2 backdrop-blur-sm">
                                        <span className="text-xs text-indigo-200">Package</span>
                                        <p className="text-sm font-semibold">{tutorRequest.package.name}</p>
                                    </div>
                                    <div className="rounded-lg bg-white/10 px-3 py-2 backdrop-blur-sm">
                                        <span className="text-xs text-indigo-200">Sessions</span>
                                        <p className="text-sm font-semibold">{tutorRequest.package.total_sessions}</p>
                                    </div>
                                    <div className="rounded-lg bg-white/10 px-3 py-2 backdrop-blur-sm">
                                        <span className="text-xs text-indigo-200">Duration</span>
                                        <p className="text-sm font-semibold">{Number(tutorRequest.package.duration_hours)}h each</p>
                                    </div>
                                </div>
                            )}

                            <button
                                onClick={handlePay}
                                className="mt-5 inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-bold text-indigo-700 shadow-sm transition-all hover:bg-indigo-50"
                            >
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                                Pay Now with FPX
                            </button>
                        </div>
                    </div>
                )}

                {/* Request Details */}
                <div className="rounded-xl bg-white p-6 shadow-sm">
                    <dl className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Student</dt>
                            <dd className="mt-1 text-sm text-gray-900">{tutorRequest.student.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Subject</dt>
                            <dd className="mt-1 text-sm text-gray-900">{tutorRequest.subject.name}</dd>
                        </div>
                        {tutorRequest.package && (
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Package</dt>
                                <dd className="mt-1">
                                    <span className="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                                        {tutorRequest.package.name} — {tutorRequest.package.total_sessions} sessions
                                    </span>
                                </dd>
                            </div>
                        )}
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Preferred Area</dt>
                            <dd className="mt-1 text-sm text-gray-900">{tutorRequest.preferred_area}</dd>
                        </div>
                        {tutorRequest.preferred_schedule && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Preferred Schedule</dt>
                                <dd className="mt-1 text-sm text-gray-900">{tutorRequest.preferred_schedule}</dd>
                            </div>
                        )}
                        {tutorRequest.notes && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Notes</dt>
                                <dd className="mt-1 text-sm text-gray-900">{tutorRequest.notes}</dd>
                            </div>
                        )}
                        {tutorRequest.matched_tutor && !showPayment && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Assigned Tutor</dt>
                                <dd className="mt-1 text-sm text-gray-900">{tutorRequest.matched_tutor.name}</dd>
                            </div>
                        )}
                    </dl>

                    {tutorRequest.status === 'open' && (
                        <div className="mt-6 border-t pt-6">
                            <button
                                onClick={handleCancel}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-100"
                            >
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Cancel Request
                            </button>
                        </div>
                    )}
                </div>

                {/* Payment History */}
                {isPaid && tutorRequest.payment && (
                    <div className="rounded-xl bg-white p-6 shadow-sm">
                        <h3 className="text-base font-semibold text-gray-900 mb-4">Payment Details</h3>
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span className="text-gray-500">Amount</span>
                                <p className="font-medium text-gray-900">RM {Number(tutorRequest.payment.amount).toFixed(2)}</p>
                            </div>
                            <div>
                                <span className="text-gray-500">Status</span>
                                <p><span className="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Paid</span></p>
                            </div>
                            {tutorRequest.payment.paid_at && (
                                <div>
                                    <span className="text-gray-500">Paid On</span>
                                    <p className="font-medium text-gray-900">{new Date(tutorRequest.payment.paid_at).toLocaleString()}</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
