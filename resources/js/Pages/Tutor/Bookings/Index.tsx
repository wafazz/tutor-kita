import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

interface BookingItem {
    id: number;
    parent: { name: string };
    student: { name: string };
    subject: { name: string };
    schedule_day: string;
    schedule_time: string;
    duration_hours: number;
    hourly_rate: string;
    location_type: string;
    status: string;
}

interface Props {
    bookings: BookingItem[];
}

function StatusBadge({ status }: { status: string }) {
    const styles: Record<string, string> = {
        active: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-blue-100 text-blue-800',
        cancelled: 'bg-red-100 text-red-800',
    };

    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${styles[status] || 'bg-gray-100 text-gray-800'}`}>
            {status.charAt(0).toUpperCase() + status.slice(1)}
        </span>
    );
}

export default function Index({ bookings }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    My Bookings
                </h2>
            }
        >
            <Head title="Bookings" />

            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {bookings.length === 0 ? (
                            <div className="p-6 text-center text-gray-500">
                                No bookings yet.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Day</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rate (RM)</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Location</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {bookings.map((booking) => (
                                            <tr key={booking.id}>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{booking.student.name}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{booking.subject.name}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600 capitalize">{booking.schedule_day}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{booking.schedule_time}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{booking.duration_hours}h</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{Number(booking.hourly_rate).toFixed(2)}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600 capitalize">{booking.location_type}</td>
                                                <td className="whitespace-nowrap px-6 py-4"><StatusBadge status={booking.status} /></td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <Link
                                                        href={route('tutor.bookings.show', booking.id)}
                                                        className="text-sm font-medium text-indigo-600 hover:text-indigo-900"
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
        </AuthenticatedLayout>
    );
}
