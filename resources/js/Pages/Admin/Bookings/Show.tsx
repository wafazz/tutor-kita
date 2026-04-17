import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Session = {
    id: number;
    date: string;
    status: string;
    check_in: string | null;
    check_out: string | null;
    duration_minutes: number | null;
};

type BookingFull = {
    id: number;
    tutor: { id: number; name: string; email: string };
    parent: { id: number; name: string; email: string };
    student: { id: number; name: string };
    subject: { id: number; name: string };
    schedule_day: string;
    schedule_time: string;
    hourly_rate: number;
    status: string;
    notes: string | null;
    created_at: string;
    sessions: Session[];
};

type Props = {
    booking: BookingFull;
};

function statusBadge(status: string) {
    const map: Record<string, string> = {
        active: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-blue-100 text-blue-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    const cls = map[status] ?? 'bg-gray-100 text-gray-800';
    return <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${cls}`}>{status}</span>;
}

function sessionStatusBadge(status: string) {
    const map: Record<string, string> = {
        scheduled: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
        'no-show': 'bg-yellow-100 text-yellow-800',
    };
    const cls = map[status] ?? 'bg-gray-100 text-gray-800';
    return <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${cls}`}>{status}</span>;
}

export default function BookingsShow({ booking }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Booking #{booking.id}
                    </h2>
                    <Link
                        href={route('admin.bookings.index')}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title={`Booking #${booking.id}`} />

            <div className="mx-auto max-w-4xl">
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Tutor</dt>
                                <dd className="mt-1 text-sm text-gray-900">{booking.tutor.name}</dd>
                                <dd className="text-sm text-gray-500">{booking.tutor.email}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Parent</dt>
                                <dd className="mt-1 text-sm text-gray-900">{booking.parent.name}</dd>
                                <dd className="text-sm text-gray-500">{booking.parent.email}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Student</dt>
                                <dd className="mt-1 text-sm text-gray-900">{booking.student.name}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Subject</dt>
                                <dd className="mt-1 text-sm text-gray-900">{booking.subject.name}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Schedule</dt>
                                <dd className="mt-1 text-sm text-gray-900 capitalize">{booking.schedule_day}, {booking.schedule_time}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Hourly Rate (RM)</dt>
                                <dd className="mt-1 text-sm text-gray-900">{booking.hourly_rate.toFixed(2)}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Status</dt>
                                <dd className="mt-1">{statusBadge(booking.status)}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Created</dt>
                                <dd className="mt-1 text-sm text-gray-900">{new Date(booking.created_at).toLocaleDateString()}</dd>
                            </div>
                            {booking.notes && (
                                <div className="sm:col-span-2">
                                    <dt className="text-sm font-medium text-gray-500">Notes</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{booking.notes}</dd>
                                </div>
                            )}
                        </dl>
                    </div>

                {booking.sessions && booking.sessions.length > 0 && (
                    <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900">Sessions</h3>
                        <div className="mt-4 overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Check-in</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Check-out</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration (min)</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {booking.sessions.map((session) => (
                                        <tr key={session.id} className="hover:bg-gray-50">
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{session.date}</td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm">{sessionStatusBadge(session.status)}</td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{session.check_in ?? '-'}</td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{session.check_out ?? '-'}</td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{session.duration_minutes ?? '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
