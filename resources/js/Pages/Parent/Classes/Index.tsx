import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

type ClassCard = {
    id: number; title: string | null; tutor_name: string | null; subject_name: string | null;
    centre_name: string | null; centre_area: string | null; is_online: boolean;
    schedule_day: string | null; schedule_time: string | null; total_sessions: number;
    seats_left: number; price: number;
    distance_km: number | null; distance_known: boolean;
};

type Filters = { radius: number; hasLocation: boolean; hidden: number };

export default function ParentClasses({ classes, filters }: { classes: ClassCard[]; filters: Filters }) {
    const setRadius = (radius: number) =>
        router.get('/parent/classes', { radius }, { preserveState: true, preserveScroll: true });

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Group classes</h2>}>
            <Head title="Group classes" />

            <div className="mx-auto max-w-7xl space-y-4 p-4 sm:p-6 lg:p-8">
                {filters.hasLocation ? (
                    <div className="flex flex-wrap items-center gap-3 rounded-xl bg-white p-4 shadow-sm">
                        <span className="text-sm text-gray-700">Centres within</span>
                        <select
                            value={filters.radius}
                            onChange={(e) => setRadius(Number(e.target.value))}
                            className="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            {[5, 10, 25, 50, 100].map((km) => (
                                <option key={km} value={km}>{km} km</option>
                            ))}
                        </select>
                        <span className="text-sm text-gray-500">of your home. Online classes always show.</span>
                        {filters.hidden > 0 && (
                            <span className="text-sm text-gray-400">
                                {filters.hidden} further away {filters.hidden === 1 ? 'is' : 'are'} hidden.
                            </span>
                        )}
                    </div>
                ) : (
                    <div className="rounded-xl bg-amber-50 p-4">
                        <p className="text-sm font-medium text-amber-800">We do not know where you are</p>
                        <p className="mt-1 text-sm text-amber-700">
                            Add a home address to a student and we can show which centres are actually near you.
                            Until then, every open class is listed.
                        </p>
                    </div>
                )}

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
                            {!c.is_online && (
                                <p className="mt-1 text-xs text-gray-500">
                                    {c.distance_km !== null
                                        ? `about ${c.distance_km < 1 ? '<1' : Math.round(c.distance_km)} km away`
                                        : 'distance unknown'}
                                </p>
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
                            {filters.hasLocation && filters.hidden > 0
                                ? 'No classes within that distance. Try widening the radius.'
                                : 'No group classes are open right now.'}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
