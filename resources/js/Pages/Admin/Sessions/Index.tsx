import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Session = {
    id: number;
    session_date: string;
    start_time: string | null;
    status: string;
    check_in_method: string | null;
    duration_minutes: number | null;
    parent_confirmed: boolean;
    booking: {
        tutor: { name: string };
        parent: { name: string };
        student: { name: string };
        subject: { name: string };
    };
};

type Props = {
    sessions: { data: Session[]; links: any[] };
    filters: { status?: string };
};

const statusColors: Record<string, string> = {
    scheduled: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    missed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
};

export default function SessionsIndex({ sessions, filters }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Sessions
                </h2>
            }
        >
            <Head title="Sessions" />

            <>
                <div className="mb-4 flex items-center justify-between">
                    <p className="text-sm text-gray-500">{sessions.data.length} session(s)</p>
                    <div className="flex gap-2">
                        {['', 'scheduled', 'checked_in', 'completed', 'missed', 'cancelled'].map((s) => (
                            <Link
                                key={s}
                                href={route('admin.sessions.index', s ? { status: s } : {})}
                                className={`rounded-md px-3 py-1 text-xs font-medium ${
                                    (filters.status ?? '') === s
                                        ? 'bg-gray-800 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-100'
                                }`}
                                preserveScroll
                            >
                                {s ? s.replace('_', ' ') : 'All'}
                            </Link>
                        ))}
                    </div>
                </div>

                <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Method</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Confirmed</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                            {sessions.data.map((session) => (
                                <tr key={session.id} className="hover:bg-gray-50">
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{session.session_date}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{session.booking.tutor.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{session.booking.student.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{session.booking.subject.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[session.status] ?? 'bg-gray-100 text-gray-800'}`}>
                                            {session.status.replace('_', ' ')}
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500 capitalize">{session.check_in_method ?? '-'}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {session.duration_minutes ? `${session.duration_minutes} min` : '-'}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        {session.parent_confirmed ? (
                                            <span className="text-green-600 font-medium">Yes</span>
                                        ) : (
                                            <span className="text-gray-400">No</span>
                                        )}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        <Link
                                            href={route('admin.sessions.show', session.id)}
                                            className="text-indigo-600 hover:text-indigo-900"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {sessions.data.length === 0 && (
                                <tr>
                                    <td colSpan={9} className="px-6 py-4 text-center text-sm text-gray-500">No sessions found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex justify-center space-x-1">
                    {sessions.links.map((link: any, i: number) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            className={`rounded px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-gray-800 text-white'
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
