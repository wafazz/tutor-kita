import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Student = {
    id: number;
    name: string;
    age: number | null;
    school: string | null;
    education_level: string | null;
};

type Booking = {
    id: number;
    tutor: { name: string };
    student: { name: string };
    subject: { name: string };
    schedule_day: string;
    schedule_time: string;
    hourly_rate: string;
    status: string;
};

type ParentFull = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    created_at: string;
    students: Student[];
    parent_bookings: Booking[];
};

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    active: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

export default function ParentsShow({ parent }: { parent: ParentFull }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Parent Details
                    </h2>
                    <Link href={route('admin.parents.index')} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title={`Parent - ${parent.name}`} />

            <div className="mx-auto max-w-4xl">
                {/* Profile */}
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Name</dt>
                            <dd className="mt-1 text-sm text-gray-900">{parent.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Email</dt>
                            <dd className="mt-1 text-sm text-gray-900">{parent.email}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Phone</dt>
                            <dd className="mt-1 text-sm text-gray-900">{parent.phone ?? '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Joined</dt>
                            <dd className="mt-1 text-sm text-gray-900">{new Date(parent.created_at).toLocaleDateString()}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Status</dt>
                            <dd className="mt-1">
                                {parent.is_active ? (
                                    <span className="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Active</span>
                                ) : (
                                    <span className="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">Inactive</span>
                                )}
                            </dd>
                        </div>
                    </dl>
                </div>

                {/* Children */}
                <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 className="text-lg font-medium text-gray-900">Children ({parent.students.length})</h3>
                    {parent.students.length === 0 ? (
                        <p className="mt-2 text-sm text-gray-500">No children registered.</p>
                    ) : (
                        <table className="mt-4 min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Age</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">School</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Level</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {parent.students.map((student) => (
                                    <tr key={student.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{student.name}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{student.age ?? '-'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{student.school ?? '-'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{student.education_level ?? '-'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            <Link
                                                href={route('admin.students.show', student.id)}
                                                className="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* Recent Bookings */}
                <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 className="text-lg font-medium text-gray-900">Recent Bookings ({parent.parent_bookings.length})</h3>
                    {parent.parent_bookings.length === 0 ? (
                        <p className="mt-2 text-sm text-gray-500">No bookings yet.</p>
                    ) : (
                        <table className="mt-4 min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Schedule</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rate (RM)</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {parent.parent_bookings.map((booking) => (
                                    <tr key={booking.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{booking.tutor.name}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{booking.student.name}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{booking.subject.name}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 capitalize">{booking.schedule_day} {booking.schedule_time}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{Number(booking.hourly_rate).toFixed(2)}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[booking.status] ?? 'bg-gray-100 text-gray-800'}`}>
                                                {booking.status}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            <Link
                                                href={route('admin.bookings.show', booking.id)}
                                                className="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
