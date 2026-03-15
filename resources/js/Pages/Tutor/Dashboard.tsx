import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Stats = {
    activeBookings: number;
    totalBookings: number;
    monthSessions: number;
    totalSessions: number;
    pendingOffers: number;
    rating: number;
    totalStudents: number;
    profileComplete: string;
};

type UpcomingSession = {
    id: number;
    date: string;
    time: string | null;
    student: string;
    subject: string;
    parent: string;
    location_type: string;
};

type PendingJob = {
    id: number;
    parent_name: string;
    student_name: string;
    subject_name: string;
    area: string;
};

type RecentSession = {
    id: number;
    date: string;
    student: string;
    subject: string;
    duration: number | null;
    confirmed: boolean;
};

type Commission = {
    total: string;
    available: string;
    paid: string;
};

type Props = {
    stats: Stats;
    commission: Commission;
    upcomingSessions: UpcomingSession[];
    pendingJobs: PendingJob[];
    recentSessions: RecentSession[];
};

function StatCard({ label, value, icon, color, sub }: { label: string; value: string | number; icon: string; color: string; sub?: string }) {
    return (
        <div className={`relative overflow-hidden rounded-xl p-5 text-white shadow-lg ${color}`}>
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
}

export default function TutorDashboard({ stats, commission, upcomingSessions, pendingJobs, recentSessions }: Props) {
    const profileStatusMap: Record<string, { text: string; cls: string }> = {
        verified: { text: 'Verified', cls: 'bg-green-100 text-green-800' },
        pending: { text: 'Pending Verification', cls: 'bg-yellow-100 text-yellow-800' },
        rejected: { text: 'Rejected', cls: 'bg-red-100 text-red-800' },
    };
    const profileStatus = profileStatusMap[stats.profileComplete] ?? profileStatusMap.pending;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Dashboard</h2>
                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${profileStatus.cls}`}>
                        {profileStatus.text}
                    </span>
                </div>
            }
        >
            <Head title="Tutor Dashboard" />

            <>
                {/* Commission Cards */}
                <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 p-5 text-white shadow-lg">
                        <div className="relative z-10">
                            <p className="text-sm font-medium text-indigo-100">My Total Commission</p>
                            <p className="mt-2 text-3xl font-bold">RM {commission.total}</p>
                        </div>
                        <div className="absolute -right-3 -top-3 rounded-full bg-white/10 p-6">
                            <svg className="h-8 w-8 text-white/30" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow-lg">
                        <div className="relative z-10">
                            <p className="text-sm font-medium text-emerald-100">My Available Commission</p>
                            <p className="mt-2 text-3xl font-bold">RM {commission.available}</p>
                        </div>
                        <div className="absolute -right-3 -top-3 rounded-full bg-white/10 p-6">
                            <svg className="h-8 w-8 text-white/30" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                        </div>
                    </div>
                    <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-500 to-amber-700 p-5 text-white shadow-lg">
                        <div className="relative z-10">
                            <p className="text-sm font-medium text-amber-100">My Paid Commission</p>
                            <p className="mt-2 text-3xl font-bold">RM {commission.paid}</p>
                        </div>
                        <div className="absolute -right-3 -top-3 rounded-full bg-white/10 p-6">
                            <svg className="h-8 w-8 text-white/30" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {/* Profile warning */}
                {stats.profileComplete === 'pending' && (
                    <div className="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                        <div className="flex items-center gap-3">
                            <svg className="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <div>
                                <p className="text-sm font-medium text-yellow-800">Your profile is pending verification</p>
                                <p className="text-xs text-yellow-600">Complete your profile to start receiving job offers.</p>
                            </div>
                            <Link href={route('tutor.profile.edit')} className="ml-auto rounded-md bg-yellow-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-yellow-700">
                                Edit Profile
                            </Link>
                        </div>
                    </div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Active Bookings"
                        value={stats.activeBookings}
                        icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        color="bg-gradient-to-br from-blue-500 to-blue-700"
                        sub={`${stats.totalBookings} total`}
                    />
                    <StatCard
                        label="Sessions This Month"
                        value={stats.monthSessions}
                        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        color="bg-gradient-to-br from-green-500 to-green-700"
                        sub={`${stats.totalSessions} all time`}
                    />
                    <StatCard
                        label="My Students"
                        value={stats.totalStudents}
                        icon="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342"
                        color="bg-gradient-to-br from-purple-500 to-purple-700"
                    />
                    <StatCard
                        label="Rating"
                        value={stats.rating > 0 ? stats.rating.toFixed(1) : '-'}
                        icon="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                        color="bg-gradient-to-br from-yellow-500 to-yellow-700"
                    />
                </div>

                {/* Pending Offers alert */}
                {stats.pendingOffers > 0 && (
                    <div className="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100">
                                <span className="text-lg font-bold text-indigo-600">{stats.pendingOffers}</span>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-indigo-800">Pending Job Offer{stats.pendingOffers > 1 ? 's' : ''}</p>
                                <p className="text-xs text-indigo-600">You have new job offers waiting for your response.</p>
                            </div>
                            <Link href={route('tutor.jobs.index')} className="ml-auto rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                View Offers
                            </Link>
                        </div>
                    </div>
                )}

                {/* Tables */}
                <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Upcoming Sessions */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Upcoming Sessions</h3>
                            <Link href={route('tutor.sessions.index')} className="text-xs text-indigo-600 hover:text-indigo-800">View all</Link>
                        </div>
                        <div className="divide-y">
                            {upcomingSessions.length === 0 && (
                                <p className="p-6 text-center text-sm text-gray-400">No upcoming sessions.</p>
                            )}
                            {upcomingSessions.map((s) => (
                                <Link key={s.id} href={route('tutor.sessions.show', s.id)} className="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{s.student} — {s.subject}</p>
                                        <p className="text-xs text-gray-500">{s.parent} &middot; <span className="capitalize">{s.location_type}</span></p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-medium text-gray-900">{s.date}</p>
                                        <p className="text-xs text-gray-500">{s.time ?? '-'}</p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>

                    {/* Pending Jobs */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Pending Job Offers</h3>
                            <Link href={route('tutor.jobs.index')} className="text-xs text-indigo-600 hover:text-indigo-800">View all</Link>
                        </div>
                        <div className="divide-y">
                            {pendingJobs.length === 0 && (
                                <p className="p-6 text-center text-sm text-gray-400">No pending offers.</p>
                            )}
                            {pendingJobs.map((job) => (
                                <Link key={job.id} href={route('tutor.jobs.show', job.id)} className="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{job.subject_name}</p>
                                        <p className="text-xs text-gray-500">{job.student_name} &middot; {job.parent_name}</p>
                                    </div>
                                    <span className="text-xs text-gray-500">{job.area}</span>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Recent Completed Sessions */}
                {recentSessions.length > 0 && (
                    <div className="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Recent Completed Sessions</h3>
                            <Link href={route('tutor.sessions.index', { status: 'completed' })} className="text-xs text-indigo-600 hover:text-indigo-800">View all</Link>
                        </div>
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Confirmed</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {recentSessions.map((s) => (
                                    <tr key={s.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{s.date}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-900">{s.student}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-500">{s.subject}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-500">{s.duration ? `${s.duration} min` : '-'}</td>
                                        <td className="whitespace-nowrap px-6 py-3 text-sm">
                                            {s.confirmed ? (
                                                <span className="text-green-600 font-medium">Yes</span>
                                            ) : (
                                                <span className="text-gray-400">Pending</span>
                                            )}
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
