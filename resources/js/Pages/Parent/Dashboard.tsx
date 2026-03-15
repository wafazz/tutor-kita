import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type Stats = {
    children: number;
    activeBookings: number;
    totalBookings: number;
    openRequests: number;
    upcomingSessions: number;
    completedSessions: number;
    pendingConfirm: number;
    activeTutors: number;
};

type UpcomingSession = {
    id: number;
    date: string;
    time: string | null;
    tutor: string;
    student: string;
    subject: string;
};

type ActiveRequest = {
    id: number;
    subject_name: string;
    student_name: string;
    area: string;
    status: string;
};

type SessionToConfirm = {
    id: number;
    date: string;
    tutor: string;
    student: string;
    subject: string;
    duration: number | null;
};

type ChildProgress = {
    id: number;
    name: string;
    school: string | null;
    education_level: string | null;
    subjects: string[];
    totalBookings: number;
    completedSessions: number;
    totalHours: number;
};

type Props = {
    stats: Stats;
    upcomingSessions: UpcomingSession[];
    activeRequests: ActiveRequest[];
    sessionsToConfirm: SessionToConfirm[];
    childProgress: ChildProgress[];
};

function StatCard({ label, value, icon, color, sub, href }: { label: string; value: string | number; icon: string; color: string; sub?: string; href?: string }) {
    const content = (
        <div className={`relative overflow-hidden rounded-xl p-5 text-white shadow-lg transition-all hover:shadow-xl ${color}`}>
            <div className="relative z-10 flex items-center justify-between">
                <div>
                    <p className="text-sm font-medium text-white/80">{label}</p>
                    <p className="mt-1 text-2xl font-bold">{value}</p>
                    {sub && <p className="mt-0.5 text-xs text-white/60">{sub}</p>}
                </div>
                <div className="rounded-lg bg-white/15 p-3">
                    <svg className="h-6 w-6 text-white/70" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" d={icon} />
                    </svg>
                </div>
            </div>
        </div>
    );
    return href ? <Link href={href}>{content}</Link> : content;
}

export default function ParentDashboard({ stats, upcomingSessions, activeRequests, sessionsToConfirm, childProgress }: Props) {
    const handleConfirm = (sessionId: number) => {
        router.post(route('parent.sessions.confirm', sessionId));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Parent Dashboard" />

            <>
                {/* Stats */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="My Children"
                        value={stats.children}
                        icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        color="bg-gradient-to-br from-emerald-500 to-emerald-700"
                        href={route('parent.students.index')}
                    />
                    <StatCard
                        label="Active Bookings"
                        value={stats.activeBookings}
                        icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        color="bg-gradient-to-br from-blue-500 to-blue-700"
                        sub={`${stats.totalBookings} total`}
                        href={route('parent.bookings.index')}
                    />
                    <StatCard
                        label="Upcoming Sessions"
                        value={stats.upcomingSessions}
                        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        color="bg-gradient-to-br from-indigo-500 to-indigo-700"
                        sub={`${stats.completedSessions} completed`}
                        href={route('parent.sessions.index')}
                    />
                    <StatCard
                        label="Active Tutors"
                        value={stats.activeTutors}
                        icon="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                        color="bg-gradient-to-br from-purple-500 to-purple-700"
                    />
                </div>

                {/* Pending confirmations alert */}
                {stats.pendingConfirm > 0 && (
                    <div className="mt-4 rounded-lg border border-orange-200 bg-orange-50 p-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100">
                                <span className="text-lg font-bold text-orange-600">{stats.pendingConfirm}</span>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-orange-800">Session{stats.pendingConfirm > 1 ? 's' : ''} Awaiting Confirmation</p>
                                <p className="text-xs text-orange-600">Please confirm completed sessions to help us track tutor performance.</p>
                            </div>
                            <Link href={route('parent.sessions.index', { status: 'completed' })} className="ml-auto rounded-md bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700">
                                Review
                            </Link>
                        </div>
                    </div>
                )}

                {/* Open requests alert */}
                {stats.openRequests > 0 && (
                    <div className="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <div className="flex items-center gap-3">
                            <svg className="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <p className="text-sm text-blue-800">You have <strong>{stats.openRequests}</strong> open tutor request{stats.openRequests > 1 ? 's' : ''}. We're finding the best match!</p>
                        </div>
                    </div>
                )}

                {/* Quick Actions */}
                <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Link
                        href={route('parent.requests.create')}
                        className="flex items-center gap-3 rounded-xl bg-emerald-600 p-4 text-white shadow-sm transition-all hover:bg-emerald-700 hover:shadow-md"
                    >
                        <div className="rounded-lg bg-white/20 p-2">
                            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        <div>
                            <p className="font-semibold">Find a Tutor</p>
                            <p className="text-xs text-emerald-100">Create a new tutor request</p>
                        </div>
                    </Link>
                    <Link
                        href={route('parent.students.create')}
                        className="flex items-center gap-3 rounded-xl bg-blue-600 p-4 text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                    >
                        <div className="rounded-lg bg-white/20 p-2">
                            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                            </svg>
                        </div>
                        <div>
                            <p className="font-semibold">Add Child</p>
                            <p className="text-xs text-blue-100">Register a new student</p>
                        </div>
                    </Link>
                    <Link
                        href={route('parent.sessions.index')}
                        className="flex items-center gap-3 rounded-xl bg-indigo-600 p-4 text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md"
                    >
                        <div className="rounded-lg bg-white/20 p-2">
                            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p className="font-semibold">View Sessions</p>
                            <p className="text-xs text-indigo-100">Track all tutoring sessions</p>
                        </div>
                    </Link>
                </div>

                <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Upcoming Sessions */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Upcoming Sessions</h3>
                            <Link href={route('parent.sessions.index')} className="text-xs text-emerald-600 hover:text-emerald-800">View all</Link>
                        </div>
                        <div className="divide-y">
                            {upcomingSessions.length === 0 && (
                                <p className="p-6 text-center text-sm text-gray-400">No upcoming sessions.</p>
                            )}
                            {upcomingSessions.map((s) => (
                                <Link key={s.id} href={route('parent.sessions.show', s.id)} className="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{s.student} — {s.subject}</p>
                                        <p className="text-xs text-gray-500">with {s.tutor}</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-medium text-gray-900">{s.date}</p>
                                        <p className="text-xs text-gray-500">{s.time ?? '-'}</p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>

                    {/* Active Requests */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">My Requests</h3>
                            <Link href={route('parent.requests.index')} className="text-xs text-emerald-600 hover:text-emerald-800">View all</Link>
                        </div>
                        <div className="divide-y">
                            {activeRequests.length === 0 && (
                                <p className="p-6 text-center text-sm text-gray-400">No active requests.</p>
                            )}
                            {activeRequests.map((r) => (
                                <Link key={r.id} href={route('parent.requests.show', r.id)} className="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{r.subject_name}</p>
                                        <p className="text-xs text-gray-500">{r.student_name} &middot; {r.area}</p>
                                    </div>
                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${
                                        r.status === 'open' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
                                    }`}>
                                        {r.status}
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Child Progress */}
                {childProgress.length > 0 && (
                    <div className="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Child Progress</h3>
                            <Link href={route('parent.students.index')} className="text-xs text-emerald-600 hover:text-emerald-800">Manage</Link>
                        </div>
                        <div className="grid grid-cols-1 divide-y sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                            {childProgress.map((child) => (
                                <div key={child.id} className="p-6">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-600">
                                            {child.name.charAt(0)}
                                        </div>
                                        <div>
                                            <p className="font-medium text-gray-900">{child.name}</p>
                                            <p className="text-xs text-gray-500">
                                                {[child.school, child.education_level].filter(Boolean).join(' · ') || '-'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-4 grid grid-cols-3 gap-4 text-center">
                                        <div>
                                            <p className="text-xl font-bold text-gray-900">{child.totalBookings}</p>
                                            <p className="text-xs text-gray-500">Bookings</p>
                                        </div>
                                        <div>
                                            <p className="text-xl font-bold text-gray-900">{child.completedSessions}</p>
                                            <p className="text-xs text-gray-500">Sessions</p>
                                        </div>
                                        <div>
                                            <p className="text-xl font-bold text-gray-900">{child.totalHours}</p>
                                            <p className="text-xs text-gray-500">Hours</p>
                                        </div>
                                    </div>
                                    {child.subjects.length > 0 && (
                                        <div className="mt-3 flex flex-wrap gap-1">
                                            {child.subjects.map((s) => (
                                                <span key={s} className="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{s}</span>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Sessions to Confirm */}
                {sessionsToConfirm.length > 0 && (
                    <div className="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Sessions Awaiting Confirmation</h3>
                        </div>
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {sessionsToConfirm.map((s) => (
                                    <tr key={s.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{s.date}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{s.tutor}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{s.student}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-500">{s.subject}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-500">{s.duration ? `${s.duration} min` : '-'}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm">
                                            <button
                                                onClick={() => handleConfirm(s.id)}
                                                className="rounded-md bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700"
                                            >
                                                Confirm
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </>
        </AuthenticatedLayout>
    );
}
