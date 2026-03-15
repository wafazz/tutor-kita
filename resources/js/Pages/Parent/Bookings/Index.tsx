import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type BookingItem = {
    id: number;
    tutor: { name: string };
    student: { name: string };
    subject: { name: string };
    schedule_day: string;
    schedule_time: string;
    duration_hours: number;
    hourly_rate: number;
    status: string;
};

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        active: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-blue-100 text-blue-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return map[status] ?? 'bg-gray-100 text-gray-800';
};

export default function BookingsIndex({ bookings }: { bookings: BookingItem[] }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    My Bookings
                </h2>
            }
        >
            <Head title="My Bookings" />

            <>
                {bookings.length === 0 ? (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6 text-center text-gray-500">
                                No bookings yet.
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Day</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rate (RM/hr)</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {bookings.map((booking) => (
                                            <tr key={booking.id}>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{booking.tutor.name}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{booking.subject.name}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{booking.student.name}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{booking.schedule_day}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{booking.schedule_time}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{booking.duration_hours}h</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{booking.hourly_rate}</td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusBadge(booking.status)}`}>
                                                        {booking.status}
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                    <Link
                                                        href={route('parent.bookings.show', booking.id)}
                                                        className="font-medium text-indigo-600 hover:text-indigo-800"
                                                    >
                                                        View
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                )}
            </>
        </AuthenticatedLayout>
    );
}
