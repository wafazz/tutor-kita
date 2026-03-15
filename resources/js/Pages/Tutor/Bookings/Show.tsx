import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

type SessionItem = {
    id: number;
    session_date: string;
    start_time: string | null;
    status: string;
    checked_in_at: string | null;
    checked_out_at: string | null;
    duration_minutes: number | null;
    tutor_notes: string | null;
    parent_confirmed: boolean;
};

type BookingFull = {
    id: number;
    parent: { name: string; email: string; phone: string | null };
    student: { name: string };
    subject: { name: string };
    schedule_day: string;
    schedule_time: string;
    duration_hours: string;
    hourly_rate: string;
    commission_rate: string;
    location_type: string;
    location_address: string | null;
    status: string;
    notes: string | null;
    sessions: SessionItem[];
};

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    active: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
    scheduled: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-yellow-100 text-yellow-800',
    missed: 'bg-red-100 text-red-800',
};

export default function Show({ booking }: { booking: BookingFull }) {
    const [weeks, setWeeks] = useState(4);

    const generateSessions = () => {
        router.post(route('tutor.bookings.generate-sessions', booking.id), { weeks });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Booking #{booking.id}
                    </h2>
                    <Link href={route('tutor.bookings.index')} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title={`Booking #${booking.id}`} />

            <div className="mx-auto max-w-4xl space-y-6">
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <div className="mb-4 flex items-center justify-between">
                        <h3 className="text-lg font-medium text-gray-900">Booking Info</h3>
                        <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[booking.status] ?? 'bg-gray-100 text-gray-800'}`}>
                            {booking.status}
                        </span>
                    </div>

                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Student</dt>
                            <dd className="mt-1 text-sm text-gray-900">{booking.student.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Subject</dt>
                            <dd className="mt-1 text-sm text-gray-900">{booking.subject.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Parent</dt>
                            <dd className="mt-1 text-sm text-gray-900">{booking.parent.name}</dd>
                            <dd className="text-sm text-gray-500">{booking.parent.email}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Schedule</dt>
                            <dd className="mt-1 text-sm text-gray-900 capitalize">{booking.schedule_day} at {booking.schedule_time}</dd>
                            <dd className="text-sm text-gray-500">{Number(booking.duration_hours).toFixed(1)} hours</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Rate</dt>
                            <dd className="mt-1 text-sm text-gray-900">RM {Number(booking.hourly_rate).toFixed(2)} / hr</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Location</dt>
                            <dd className="mt-1 text-sm text-gray-900 capitalize">{booking.location_type}</dd>
                            {booking.location_address && <dd className="text-sm text-gray-500">{booking.location_address}</dd>}
                        </div>
                        {booking.notes && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Notes</dt>
                                <dd className="mt-1 text-sm text-gray-900">{booking.notes}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                {/* Generate Sessions */}
                {['confirmed', 'active'].includes(booking.status) && (
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900">Generate Sessions</h3>
                        <p className="mt-1 text-sm text-gray-600">Create scheduled sessions for upcoming weeks based on the booking schedule.</p>
                        <div className="mt-4 flex items-end gap-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Weeks</label>
                                <select
                                    value={weeks}
                                    onChange={(e) => setWeeks(Number(e.target.value))}
                                    className="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    {[1, 2, 4, 8, 12].map((w) => (
                                        <option key={w} value={w}>{w} week{w > 1 ? 's' : ''}</option>
                                    ))}
                                </select>
                            </div>
                            <button
                                onClick={generateSessions}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Generate
                            </button>
                        </div>
                    </div>
                )}

                {/* Sessions */}
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 className="mb-4 text-lg font-medium text-gray-900">Sessions ({booking.sessions.length})</h3>

                    {booking.sessions.length === 0 ? (
                        <p className="text-sm text-gray-500">No sessions yet. Generate sessions above to get started.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Confirmed</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {booking.sessions.map((session) => (
                                        <tr key={session.id} className="hover:bg-gray-50">
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{session.session_date}</td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{session.start_time ?? '-'}</td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm">
                                                <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[session.status] ?? 'bg-gray-100 text-gray-800'}`}>
                                                    {session.status.replace('_', ' ')}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                                {session.duration_minutes ? `${session.duration_minutes} min` : '-'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm">
                                                {session.parent_confirmed ? (
                                                    <span className="text-green-600">Yes</span>
                                                ) : (
                                                    <span className="text-gray-400">No</span>
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm">
                                                <Link
                                                    href={route('tutor.sessions.show', session.id)}
                                                    className="text-indigo-600 hover:text-indigo-900"
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
        </AuthenticatedLayout>
    );
}
