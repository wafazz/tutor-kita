import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { QRCodeSVG } from 'qrcode.react';

type SessionFull = {
    id: number;
    session_date: string;
    start_time: string | null;
    end_time: string | null;
    check_in_token: string;
    checked_in_at: string | null;
    checked_out_at: string | null;
    check_in_method: string | null;
    duration_minutes: number | null;
    status: string;
    tutor_notes: string | null;
    parent_confirmed: boolean;
    booking: {
        id: number;
        tutor: { name: string; phone: string | null };
        student: { name: string };
        subject: { name: string };
        location_type: string;
        location_address: string | null;
        hourly_rate: string;
        duration_hours: string;
    };
};

const statusColors: Record<string, string> = {
    scheduled: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    missed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
};

export default function SessionShow({ session }: { session: SessionFull }) {
    const handleConfirm = () => {
        router.post(route('parent.sessions.confirm', session.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Session #{session.id}
                    </h2>
                    <Link href={route('parent.sessions.index')} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title={`Session #${session.id}`} />

            <div className="mx-auto max-w-4xl">
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Date</dt>
                            <dd className="mt-1 text-sm text-gray-900">{session.session_date}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Time</dt>
                            <dd className="mt-1 text-sm text-gray-900">{session.start_time ?? '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Tutor</dt>
                            <dd className="mt-1 text-sm text-gray-900">{session.booking.tutor.name}</dd>
                            {session.booking.tutor.phone && <dd className="text-sm text-gray-500">{session.booking.tutor.phone}</dd>}
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Student</dt>
                            <dd className="mt-1 text-sm text-gray-900">{session.booking.student.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Subject</dt>
                            <dd className="mt-1 text-sm text-gray-900">{session.booking.subject.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Location</dt>
                            <dd className="mt-1 text-sm text-gray-900 capitalize">{session.booking.location_type}</dd>
                            {session.booking.location_address && <dd className="text-sm text-gray-500">{session.booking.location_address}</dd>}
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Status</dt>
                            <dd className="mt-1">
                                <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[session.status] ?? 'bg-gray-100 text-gray-800'}`}>
                                    {session.status.replace('_', ' ')}
                                </span>
                                {session.parent_confirmed && (
                                    <span className="ml-2 inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                        Confirmed
                                    </span>
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Check-in Method</dt>
                            <dd className="mt-1 text-sm text-gray-900 capitalize">{session.check_in_method ?? '-'}</dd>
                        </div>
                        {session.checked_in_at && (
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Checked In</dt>
                                <dd className="mt-1 text-sm text-gray-900">{new Date(session.checked_in_at).toLocaleString()}</dd>
                            </div>
                        )}
                        {session.checked_out_at && (
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Checked Out</dt>
                                <dd className="mt-1 text-sm text-gray-900">{new Date(session.checked_out_at).toLocaleString()}</dd>
                            </div>
                        )}
                        {session.duration_minutes != null && (
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Duration</dt>
                                <dd className="mt-1 text-sm text-gray-900">{session.duration_minutes} minutes</dd>
                            </div>
                        )}
                        {session.tutor_notes && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Tutor Notes</dt>
                                <dd className="mt-1 text-sm text-gray-900">{session.tutor_notes}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                {/* QR Code for tutor to scan */}
                {session.status === 'scheduled' && (
                    <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900">Session QR Code</h3>
                        <p className="mt-1 text-sm text-gray-600">Show this QR code to the tutor for check-in verification.</p>
                        <div className="mt-4 flex justify-center">
                            <div className="rounded-lg border-2 border-gray-200 p-4">
                                <QRCodeSVG value={session.check_in_token} size={200} />
                            </div>
                        </div>
                    </div>
                )}

                {/* Confirm Session */}
                {session.status === 'completed' && !session.parent_confirmed && (
                    <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900">Confirm Session</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Please confirm that this tutoring session took place as recorded.
                        </p>
                        <div className="mt-4">
                            <button
                                onClick={handleConfirm}
                                className="rounded-md bg-green-600 px-6 py-3 text-sm font-medium text-white hover:bg-green-700"
                            >
                                Confirm Session
                            </button>
                        </div>
                    </div>
                )}

                {session.parent_confirmed && (
                    <div className="mt-6 overflow-hidden bg-green-50 p-6 shadow-sm sm:rounded-lg">
                        <p className="text-sm font-medium text-green-800">
                            You have confirmed this session. Thank you!
                        </p>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
