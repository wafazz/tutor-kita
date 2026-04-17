import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type BookingItem = {
    id: number;
    tutor: { name: string };
    parent: { name: string };
    student: { name: string };
    subject: { name: string };
    schedule_day: string;
    schedule_time: string;
    hourly_rate: number;
    status: string;
};

type Props = {
    bookings: {
        data: BookingItem[];
        links: any[];
    };
    filters: {
        status: string;
    };
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

export default function BookingsIndex({ bookings, filters }: Props) {
    const handleFilter = (status: string) => {
        router.get(route('admin.bookings.index'), { status: status || undefined }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Bookings
                </h2>
            }
        >
            <Head title="Bookings" />

            <>
                <div className="mb-4">
                        <select
                            value={filters.status ?? ''}
                            onChange={(e) => handleFilter(e.target.value)}
                            className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">ID</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Parent</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Day</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rate (RM)</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {bookings.data.map((booking) => (
                                    <tr key={booking.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{booking.id}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{booking.tutor.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{booking.parent.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{booking.student.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{booking.subject.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500 capitalize">{booking.schedule_day}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{booking.schedule_time}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{booking.hourly_rate.toFixed(2)}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">{statusBadge(booking.status)}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            <Link
                                                href={route('admin.bookings.show', booking.id)}
                                                className="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {bookings.data.length === 0 && (
                                    <tr>
                                        <td colSpan={10} className="px-6 py-4 text-center text-sm text-gray-500">No bookings found.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                <div className="mt-4 flex justify-center space-x-1">
                    {bookings.links.map((link: any, i: number) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            className={`rounded px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : link.url
                                      ? 'bg-white text-gray-700 hover:bg-gray-100'
                                      : 'cursor-not-allowed bg-gray-100 text-gray-400'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            preserveScroll
                        />
                    ))}
                </div>
            </>
        </AuthenticatedLayout>
    );
}
