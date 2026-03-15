import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Stats = {
    totalTutors: number;
    verifiedTutors: number;
    pendingTutors: number;
    totalParents: number;
    totalStudents: number;
    totalSubjects: number;
    openRequests: number;
    activeBookings: number;
    totalBookings: number;
    todaySessions: number;
    completedSessions: number;
    monthSessions: number;
    totalSales: number;
    salesThisMonth: number;
    salesLastMonth: number;
    commissionPaid: number;
    commissionPending: number;
};

type RecentTutor = {
    id: number;
    name: string;
    email: string;
    status: string;
    created_at: string;
};

type RecentRequest = {
    id: number;
    parent_name: string;
    subject_name: string;
    area: string;
    status: string;
    created_at: string;
};

type UpcomingSession = {
    id: number;
    date: string;
    time: string | null;
    tutor: string;
    student: string;
    subject: string;
};

type Props = {
    stats: Stats;
    recentTutors: RecentTutor[];
    recentRequests: RecentRequest[];
    upcomingSessions: UpcomingSession[];
};

const statusColors: Record<string, string> = {
    verified: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    rejected: 'bg-red-100 text-red-800',
    open: 'bg-blue-100 text-blue-800',
    matched: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

function StatCard({ label, value, icon, color, href }: { label: string; value: string | number; icon: string; color: string; href?: string }) {
    const content = (
        <div className={`relative overflow-hidden rounded-xl p-5 text-white shadow-lg transition-all hover:shadow-xl ${color}`}>
            <div className="relative z-10 flex items-center justify-between">
                <div>
                    <p className="text-sm font-medium text-white/80">{label}</p>
                    <p className="mt-1 text-2xl font-bold">{value}</p>
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

export default function AdminDashboard({ stats, recentTutors, recentRequests, upcomingSessions }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Admin Dashboard" />

            <>
                {/* Stats Row 1 — People */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total Tutors"
                        value={stats.totalTutors}
                        icon="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                        color="bg-gradient-to-br from-indigo-500 to-indigo-700"
                        href={route('admin.tutors.index')}
                    />
                    <StatCard
                        label="Total Parents"
                        value={stats.totalParents}
                        icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        color="bg-gradient-to-br from-emerald-500 to-emerald-700"
                        href={route('admin.parents.index')}
                    />
                    <StatCard
                        label="Total Students"
                        value={stats.totalStudents}
                        icon="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342"
                        color="bg-gradient-to-br from-cyan-500 to-cyan-700"
                        href={route('admin.students.index')}
                    />
                    <StatCard
                        label="Subjects"
                        value={stats.totalSubjects}
                        icon="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                        color="bg-gradient-to-br from-purple-500 to-purple-700"
                        href={route('admin.subjects.index')}
                    />
                </div>

                {/* Stats Row 2 — Activity */}
                <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Pending Tutors"
                        value={stats.pendingTutors}
                        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        color="bg-gradient-to-br from-yellow-500 to-yellow-700"
                        href={route('admin.tutors.index')}
                    />
                    <StatCard
                        label="Open Requests"
                        value={stats.openRequests}
                        icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        color="bg-gradient-to-br from-orange-500 to-orange-700"
                        href={route('admin.requests.index')}
                    />
                    <StatCard
                        label="Active Bookings"
                        value={stats.activeBookings}
                        icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        color="bg-gradient-to-br from-blue-500 to-blue-700"
                        href={route('admin.bookings.index')}
                    />
                    <StatCard
                        label="Today's Sessions"
                        value={stats.todaySessions}
                        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        color="bg-gradient-to-br from-rose-500 to-rose-700"
                        href={route('admin.sessions.index')}
                    />
                </div>

                {/* Stats Row 3 — Financial */}
                <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow-lg">
                        <div className="relative z-10">
                            <p className="text-sm font-medium text-white/80">Total Sales</p>
                            <p className="mt-1 text-2xl font-bold">RM {(Number(stats.totalSales) || 0).toFixed(2)}</p>
                        </div>
                        <div className="absolute right-4 top-4 rounded-lg bg-white/15 p-3">
                            <svg className="h-6 w-6 text-white/70" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                        </div>
                    </div>

                    <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 p-5 text-white shadow-lg">
                        <div className="relative z-10">
                            <p className="text-sm font-medium text-white/80">Sales This Month</p>
                            <p className="mt-1 text-2xl font-bold">RM {(Number(stats.salesThisMonth) || 0).toFixed(2)}</p>
                            <div className="mt-1 flex items-center gap-1 text-xs">
                                {(() => {
                                    const thisMonth = Number(stats.salesThisMonth) || 0;
                                    const lastMonth = Number(stats.salesLastMonth) || 0;
                                    if (lastMonth === 0) return <span className="text-white/60">vs RM 0.00 last month</span>;
                                    const pct = ((thisMonth - lastMonth) / lastMonth) * 100;
                                    return (
                                        <>
                                            <span className={pct >= 0 ? 'text-green-200' : 'text-red-200'}>
                                                {pct >= 0 ? '↑' : '↓'} {Math.abs(pct).toFixed(1)}%
                                            </span>
                                            <span className="text-white/60">vs last month</span>
                                        </>
                                    );
                                })()}
                            </div>
                        </div>
                        <div className="absolute right-4 top-4 rounded-lg bg-white/15 p-3">
                            <svg className="h-6 w-6 text-white/70" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                    </div>

                    <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-green-500 to-green-700 p-5 text-white shadow-lg">
                        <div className="relative z-10">
                            <p className="text-sm font-medium text-white/80">Commission Paid</p>
                            <p className="mt-1 text-2xl font-bold">RM {(Number(stats.commissionPaid) || 0).toFixed(2)}</p>
                            <p className="mt-1 text-xs text-white/60">Paid out to tutors</p>
                        </div>
                        <div className="absolute right-4 top-4 rounded-lg bg-white/15 p-3">
                            <svg className="h-6 w-6 text-white/70" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-500 to-amber-700 p-5 text-white shadow-lg">
                        <div className="relative z-10">
                            <p className="text-sm font-medium text-white/80">Commission Pending</p>
                            <p className="mt-1 text-2xl font-bold">RM {(Number(stats.commissionPending) || 0).toFixed(2)}</p>
                            <p className="mt-1 text-xs text-white/60">Awaiting payment</p>
                        </div>
                        <div className="absolute right-4 top-4 rounded-lg bg-white/15 p-3">
                            <svg className="h-6 w-6 text-white/70" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {/* Summary mini-cards */}
                <div className="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div className="rounded-lg bg-indigo-50 p-4 text-center">
                        <p className="text-2xl font-bold text-indigo-700">{stats.verifiedTutors}</p>
                        <p className="text-xs text-indigo-600">Verified Tutors</p>
                    </div>
                    <div className="rounded-lg bg-green-50 p-4 text-center">
                        <p className="text-2xl font-bold text-green-700">{stats.totalBookings}</p>
                        <p className="text-xs text-green-600">Total Bookings</p>
                    </div>
                    <div className="rounded-lg bg-blue-50 p-4 text-center">
                        <p className="text-2xl font-bold text-blue-700">{stats.monthSessions}</p>
                        <p className="text-xs text-blue-600">Sessions This Month</p>
                    </div>
                    <div className="rounded-lg bg-emerald-50 p-4 text-center">
                        <p className="text-2xl font-bold text-emerald-700">{stats.completedSessions}</p>
                        <p className="text-xs text-emerald-600">Completed Sessions</p>
                    </div>
                </div>

                {/* Tables */}
                <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Recent Tutors */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Recent Tutors</h3>
                            <Link href={route('admin.tutors.index')} className="text-xs text-indigo-600 hover:text-indigo-800">View all</Link>
                        </div>
                        <div className="divide-y">
                            {recentTutors.length === 0 && (
                                <p className="p-6 text-center text-sm text-gray-400">No tutors yet.</p>
                            )}
                            {recentTutors.map((tutor) => (
                                <Link key={tutor.id} href={route('admin.tutors.show', tutor.id)} className="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{tutor.name}</p>
                                        <p className="text-xs text-gray-500">{tutor.email}</p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[tutor.status] ?? 'bg-gray-100 text-gray-800'}`}>
                                            {tutor.status}
                                        </span>
                                        <span className="text-xs text-gray-400">{tutor.created_at}</span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>

                    {/* Recent Requests */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Recent Requests</h3>
                            <Link href={route('admin.requests.index')} className="text-xs text-indigo-600 hover:text-indigo-800">View all</Link>
                        </div>
                        <div className="divide-y">
                            {recentRequests.length === 0 && (
                                <p className="p-6 text-center text-sm text-gray-400">No requests yet.</p>
                            )}
                            {recentRequests.map((req) => (
                                <Link key={req.id} href={route('admin.requests.show', req.id)} className="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{req.subject_name}</p>
                                        <p className="text-xs text-gray-500">{req.parent_name} &middot; {req.area}</p>
                                    </div>
                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[req.status] ?? 'bg-gray-100 text-gray-800'}`}>
                                        {req.status}
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Upcoming Sessions */}
                <div className="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b px-6 py-4">
                        <h3 className="font-semibold text-gray-900">Upcoming Sessions</h3>
                        <Link href={route('admin.sessions.index')} className="text-xs text-indigo-600 hover:text-indigo-800">View all</Link>
                    </div>
                    {upcomingSessions.length === 0 ? (
                        <p className="p-6 text-center text-sm text-gray-400">No upcoming sessions.</p>
                    ) : (
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {upcomingSessions.map((s) => (
                                    <tr key={s.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{s.date}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-500">{s.time ?? '-'}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{s.tutor}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{s.student}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-500">{s.subject}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm">
                                            <Link href={route('admin.sessions.show', s.id)} className="text-indigo-600 hover:text-indigo-900">View</Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </>
        </AuthenticatedLayout>
    );
}
