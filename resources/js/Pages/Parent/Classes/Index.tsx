import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

type ClassCard = {
    id: number; title: string | null; tutor_name: string | null; subject_name: string | null;
    centre_name: string | null; centre_area: string | null; is_online: boolean;
    schedule_day: string | null; schedule_time: string | null; total_sessions: number;
    seats_left: number; price: number;
};

export default function ParentClasses({ classes }: { classes: ClassCard[] }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Group classes</h2>}>
            <Head title="Group classes" />

            <div className="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {classes.map((c) => (
                        <Link key={c.id} href={`/parent/classes/${c.id}`}
                            className="block rounded-xl bg-white p-5 shadow-sm transition hover:shadow-md">
                            <div className="flex items-start justify-between">
                                <p className="font-semibold text-gray-900">{c.title ?? c.subject_name}</p>
                                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${c.is_online ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700'}`}>
                                    {c.is_online ? 'Online' : 'Centre'}
                                </span>
                            </div>
                            <p className="mt-1 text-sm text-gray-500">{c.subject_name} · {c.tutor_name}</p>
                            {!c.is_online && c.centre_name && (
                                <p className="text-xs text-gray-400">{c.centre_name}{c.centre_area ? `, ${c.centre_area}` : ''}</p>
                            )}
                            <p className="mt-2 text-xs text-gray-400 capitalize">
                                {c.schedule_day} {(c.schedule_time ?? '').slice(0, 5)} · {c.total_sessions} session{c.total_sessions > 1 ? 's' : ''}
                            </p>
                            <div className="mt-3 flex items-end justify-between">
                                <p className="text-lg font-bold text-gray-900">RM {Number(c.price).toFixed(2)}</p>
                                <p className={`text-sm ${c.seats_left === 0 ? 'text-red-600' : 'text-gray-500'}`}>
                                    {c.seats_left === 0 ? 'Full' : `${c.seats_left} seat${c.seats_left > 1 ? 's' : ''} left`}
                                </p>
                            </div>
                        </Link>
                    ))}
                    {classes.length === 0 && (
                        <div className="col-span-full rounded-xl bg-white p-8 text-center text-sm text-gray-400 shadow-sm">
                            No group classes are open right now.
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
