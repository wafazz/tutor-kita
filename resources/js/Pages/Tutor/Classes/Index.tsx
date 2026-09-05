import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

type ClassRow = {
    id: number; title: string | null; subject_name: string | null;
    centre_name: string | null; centre_area: string | null; is_online: boolean;
    schedule_day: string | null; schedule_time: string | null;
    duration_hours: string; total_sessions: number; status: string;
    seats_taken: number; seats_left: number;
    payout_model: string; payout_label: string; earns: number;
    students: { name: string | null; confirmed: boolean }[];
};

const statusColour: Record<string, string> = {
    open: 'bg-green-50 text-green-700',
    closed: 'bg-gray-100 text-gray-700',
    draft: 'bg-amber-50 text-amber-700',
    completed: 'bg-blue-50 text-blue-700',
};

export default function TutorClasses({ classes }: { classes: ClassRow[] }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">My classes</h2>}>
            <Head title="My classes" />

            <div className="mx-auto max-w-5xl space-y-4 p-4 sm:p-6 lg:p-8">
                {classes.map((c) => (
                    <div key={c.id} className="rounded-xl bg-white p-5 shadow-sm">
                        <div className="flex items-start justify-between">
                            <div>
                                <p className="font-semibold text-gray-900">{c.title}</p>
                                <p className="text-sm text-gray-500">
                                    {c.is_online ? 'Online' : (c.centre_name ?? 'At a centre')}
                                    {!c.is_online && c.centre_area ? `, ${c.centre_area}` : ''}
                                </p>
                                <p className="mt-1 text-xs capitalize text-gray-400">
                                    {c.schedule_day} {(c.schedule_time ?? '').slice(0, 5)} · {Number(c.duration_hours)}h · {c.total_sessions} session{c.total_sessions > 1 ? 's' : ''}
                                </p>
                            </div>
                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${statusColour[c.status] ?? 'bg-gray-100 text-gray-700'}`}>
                                {c.status}
                            </span>
                        </div>

                        <div className="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <div>
                                <p className="text-xs text-gray-500">Students</p>
                                <p className="font-medium text-gray-900">{c.seats_taken} / {c.seats_taken + c.seats_left}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500">You earn</p>
                                <p className="font-medium text-green-700">RM {Number(c.earns).toFixed(2)}</p>
                            </div>
                            <div className="col-span-2 sm:col-span-1">
                                <p className="text-xs text-gray-500">Paid</p>
                                <p className="text-sm text-gray-700">{c.payout_label}</p>
                            </div>
                        </div>

                        {c.payout_model !== 'per_student' && (
                            <p className="mt-2 text-xs text-gray-500">
                                This is a fixed arrangement — what you earn does not change with how many students enrol.
                            </p>
                        )}

                        {c.students.length > 0 && (
                            <div className="mt-4 border-t border-gray-100 pt-3">
                                <p className="text-xs text-gray-500">Enrolled</p>
                                <div className="mt-1 flex flex-wrap gap-1.5">
                                    {c.students.map((s, i) => (
                                        <span key={i}
                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs ${s.confirmed ? 'bg-gray-100 text-gray-700' : 'bg-amber-50 text-amber-700'}`}>
                                            {s.name}{!s.confirmed && ' · unpaid'}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                ))}

                {classes.length === 0 && (
                    <div className="rounded-xl bg-white p-8 text-center text-sm text-gray-400 shadow-sm">
                        You are not teaching any group classes yet.
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
