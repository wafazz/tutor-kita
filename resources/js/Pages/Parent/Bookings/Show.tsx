import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type SessionItem = {
    id: number;
    session_date: string;
    status: string;
    checked_in_at: string | null;
    checked_out_at: string | null;
    duration_minutes: number | null;
    parent_confirmed: boolean;
};

type ReviewData = {
    id: number;
    rating: number;
    comment: string | null;
    created_at: string;
};

type BookingFull = {
    id: number;
    tutor: { name: string };
    student: { name: string };
    subject: { name: string };
    schedule_day: string;
    schedule_time: string;
    duration_hours: number;
    hourly_rate: number;
    status: string;
    location_type: string;
    location_address: string | null;
    commission_rate: number;
    notes: string | null;
    sessions: SessionItem[];
    review: ReviewData | null;
};

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        active: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-blue-100 text-blue-800',
        cancelled: 'bg-red-100 text-red-800',
        scheduled: 'bg-indigo-100 text-indigo-800',
    };
    return map[status] ?? 'bg-gray-100 text-gray-800';
};

export default function BookingsShow({ booking }: { booking: BookingFull }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Booking Details
                    </h2>
                    <Link
                        href={route('parent.bookings.index')}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title="Booking Details" />

            <div className="mx-auto max-w-4xl space-y-6">
                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-6 flex items-center justify-between">
                                <h3 className="text-lg font-medium text-gray-900">Booking Info</h3>
                                <span className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold ${statusBadge(booking.status)}`}>
                                    {booking.status}
                                </span>
                            </div>

                            <dl className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Tutor</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{booking.tutor.name}</dd>
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
                                    <dd className="mt-1 text-sm text-gray-900">{booking.schedule_day} at {booking.schedule_time}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Duration</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{booking.duration_hours} hour(s)</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Rate</dt>
                                    <dd className="mt-1 text-sm text-gray-900">RM {booking.hourly_rate}/hr</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Location Type</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{booking.location_type}</dd>
                                </div>
                                {booking.location_address && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Location Address</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{booking.location_address}</dd>
                                    </div>
                                )}
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Commission Rate</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{booking.commission_rate}%</dd>
                                </div>
                                {booking.notes && (
                                    <div className="sm:col-span-2">
                                        <dt className="text-sm font-medium text-gray-500">Notes</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{booking.notes}</dd>
                                    </div>
                                )}
                            </dl>
                        </div>
                    </div>

                {/* Review Section */}
                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div className="p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-medium text-gray-900">Review</h3>
                            {!booking.review && ['active', 'completed'].includes(booking.status) && (
                                <Link
                                    href={route('parent.reviews.create', booking.id)}
                                    className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                                >
                                    Leave a Review
                                </Link>
                            )}
                        </div>
                        {booking.review ? (
                            <div>
                                <div className="flex items-center gap-2">
                                    <div className="flex gap-0.5">
                                        {[1, 2, 3, 4, 5].map((star) => (
                                            <svg
                                                key={star}
                                                className={`h-5 w-5 ${star <= booking.review!.rating ? 'text-yellow-400' : 'text-gray-300'}`}
                                                fill="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                        ))}
                                    </div>
                                    <span className="text-sm text-gray-500">
                                        {new Date(booking.review.created_at).toLocaleDateString()}
                                    </span>
                                </div>
                                {booking.review.comment && (
                                    <p className="mt-2 text-sm text-gray-700">{booking.review.comment}</p>
                                )}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">No review yet.</p>
                        )}
                    </div>
                </div>

                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div className="p-6">
                        <h3 className="mb-4 text-lg font-medium text-gray-900">Sessions</h3>

                        {booking.sessions.length === 0 ? (
                            <p className="text-gray-500">No sessions recorded yet.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Check In</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Check Out</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Confirmed</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {booking.sessions.map((session) => (
                                            <tr key={session.id}>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                    {new Date(session.session_date).toLocaleDateString()}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusBadge(session.status)}`}>
                                                        {session.status}
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                    {session.checked_in_at
                                                        ? new Date(session.checked_in_at).toLocaleTimeString()
                                                        : '-'}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                    {session.checked_out_at
                                                        ? new Date(session.checked_out_at).toLocaleTimeString()
                                                        : '-'}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                    {session.duration_minutes !== null ? `${session.duration_minutes} min` : '-'}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                    {session.parent_confirmed ? (
                                                        <span className="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">
                                                            Yes
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800">
                                                            No
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                    <Link
                                                        href={route('parent.sessions.show', session.id)}
                                                        className="text-emerald-600 hover:text-emerald-900"
                                                    >
                                                        View
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
