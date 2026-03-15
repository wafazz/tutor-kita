import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Booking = {
    id: number;
    tutor: { name: string };
    subject: { name: string };
    schedule_day: string;
    schedule_time: string;
    hourly_rate: string;
    status: string;
};

type StudentFull = {
    id: number;
    name: string;
    age: number | null;
    school: string | null;
    education_level: string | null;
    notes: string | null;
    parent: { id: number; name: string; email: string; phone: string | null };
    bookings: Booking[];
};

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    active: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

export default function StudentsShow({ student }: { student: StudentFull }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Student Details
                    </h2>
                    <Link href={route('admin.students.index')} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title={`Student - ${student.name}`} />

            <div className="mx-auto max-w-4xl">
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Name</dt>
                            <dd className="mt-1 text-sm text-gray-900">{student.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Age</dt>
                            <dd className="mt-1 text-sm text-gray-900">{student.age ?? '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">School</dt>
                            <dd className="mt-1 text-sm text-gray-900">{student.school ?? '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Education Level</dt>
                            <dd className="mt-1 text-sm text-gray-900">{student.education_level ?? '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Parent</dt>
                            <dd className="mt-1 text-sm">
                                <Link
                                    href={route('admin.parents.show', student.parent.id)}
                                    className="text-indigo-600 hover:text-indigo-900"
                                >
                                    {student.parent.name}
                                </Link>
                            </dd>
                            <dd className="text-sm text-gray-500">{student.parent.email}</dd>
                            {student.parent.phone && <dd className="text-sm text-gray-500">{student.parent.phone}</dd>}
                        </div>
                        {student.notes && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Notes</dt>
                                <dd className="mt-1 text-sm text-gray-900">{student.notes}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                {/* Bookings */}
                <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 className="text-lg font-medium text-gray-900">Bookings ({student.bookings.length})</h3>
                    {student.bookings.length === 0 ? (
                        <p className="mt-2 text-sm text-gray-500">No bookings yet.</p>
                    ) : (
                        <table className="mt-4 min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Schedule</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rate (RM)</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {student.bookings.map((booking) => (
                                    <tr key={booking.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{booking.tutor.name}</td>
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
