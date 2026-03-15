import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface JobFull {
    id: number;
    parent: { name: string };
    student: {
        name: string;
        age: number | null;
        school: string | null;
        education_level: string | null;
    };
    subject: { name: string };
    package: { name: string; total_sessions: number; duration_hours: number; price: number } | null;
    preferred_area: string;
    preferred_schedule: string | null;
    budget_min: number | null;
    budget_max: number | null;
    notes: string | null;
}

interface Props {
    job: JobFull;
}

const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

export default function Show({ job }: Props) {
    const [showRejectConfirm, setShowRejectConfirm] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        schedule_day: '',
        schedule_time: '',
        duration_hours: 1,
        location_type: 'home',
        location_address: '',
    });

    const submitAccept: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tutor.jobs.accept', job.id));
    };

    const handleReject = () => {
        router.post(route('tutor.jobs.reject', job.id));
    };

    function formatBudget(min: number | null, max: number | null): string {
        if (min && max) return `RM ${min} - RM ${max}`;
        if (min) return `From RM ${min}`;
        if (max) return `Up to RM ${max}`;
        return 'Not specified';
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Job Offer</h2>
                    <Link
                        href={route('tutor.jobs.index')}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title="Job Offer" />

            <div className="mx-auto max-w-3xl space-y-6">
                {/* Hero Card */}
                <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-500 to-purple-700 p-6 text-white shadow-lg">
                    <div className="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white/10" />
                    <div className="absolute -bottom-4 -left-4 h-24 w-24 rounded-full bg-white/10" />
                    <div className="relative z-10">
                        <div className="flex items-start justify-between">
                            <div>
                                <span className="inline-flex rounded-full bg-white/20 px-3 py-1 text-sm font-medium backdrop-blur-sm">
                                    New Job Offer
                                </span>
                                <h3 className="mt-3 text-2xl font-bold">{job.subject.name}</h3>
                                <p className="mt-1 text-indigo-200">for {job.student.name}</p>
                            </div>
                            <div className="text-right">
                                {job.package ? (
                                    <>
                                        <p className="text-sm text-indigo-200">{job.package.name}</p>
                                        <p className="text-xl font-bold">RM {Number(job.package.price).toFixed(2)}</p>
                                        <p className="text-xs text-indigo-200">{job.package.total_sessions} sessions x {Number(job.package.duration_hours)}h</p>
                                    </>
                                ) : (
                                    <>
                                        <p className="text-sm text-indigo-200">Budget</p>
                                        <p className="text-xl font-bold">{formatBudget(job.budget_min, job.budget_max)}</p>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div className="rounded-lg bg-white/10 p-3 backdrop-blur-sm">
                                <div className="flex items-center gap-2">
                                    <svg className="h-4 w-4 text-indigo-200" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                    </svg>
                                    <span className="text-xs text-indigo-200">Area</span>
                                </div>
                                <p className="mt-1 text-sm font-semibold">{job.preferred_area}</p>
                            </div>
                            <div className="rounded-lg bg-white/10 p-3 backdrop-blur-sm">
                                <div className="flex items-center gap-2">
                                    <svg className="h-4 w-4 text-indigo-200" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span className="text-xs text-indigo-200">Schedule</span>
                                </div>
                                <p className="mt-1 text-sm font-semibold">{job.preferred_schedule ?? 'Flexible'}</p>
                            </div>
                            <div className="rounded-lg bg-white/10 p-3 backdrop-blur-sm">
                                <div className="flex items-center gap-2">
                                    <svg className="h-4 w-4 text-indigo-200" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347" />
                                    </svg>
                                    <span className="text-xs text-indigo-200">Level</span>
                                </div>
                                <p className="mt-1 text-sm font-semibold">{job.student.education_level ?? '-'}</p>
                            </div>
                            <div className="rounded-lg bg-white/10 p-3 backdrop-blur-sm">
                                <div className="flex items-center gap-2">
                                    <svg className="h-4 w-4 text-indigo-200" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                    <span className="text-xs text-indigo-200">Parent</span>
                                </div>
                                <p className="mt-1 text-sm font-semibold">{job.parent.name}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Student & Details */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div className="rounded-xl bg-white p-6 shadow-sm">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100">
                                <svg className="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                                </svg>
                            </div>
                            <h3 className="text-base font-semibold text-gray-900">Student Details</h3>
                        </div>
                        <div className="space-y-3">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">Name</span>
                                <span className="font-medium text-gray-900">{job.student.name}</span>
                            </div>
                            {job.student.age && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-500">Age</span>
                                    <span className="font-medium text-gray-900">{job.student.age} years old</span>
                                </div>
                            )}
                            {job.student.school && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-500">School</span>
                                    <span className="font-medium text-gray-900">{job.student.school}</span>
                                </div>
                            )}
                            {job.student.education_level && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-500">Level</span>
                                    <span className="font-medium text-gray-900">{job.student.education_level}</span>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="rounded-xl bg-white p-6 shadow-sm">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100">
                                <svg className="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                                </svg>
                            </div>
                            <h3 className="text-base font-semibold text-gray-900">Job Requirements</h3>
                        </div>
                        <div className="space-y-3">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">Subject</span>
                                <span className="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">{job.subject.name}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">Area</span>
                                <span className="font-medium text-gray-900">{job.preferred_area}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">Schedule</span>
                                <span className="font-medium text-gray-900">{job.preferred_schedule ?? 'Flexible'}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">Budget</span>
                                <span className="font-medium text-gray-900">{formatBudget(job.budget_min, job.budget_max)}</span>
                            </div>
                            {job.package && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-500">Package</span>
                                    <span className="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">{job.package.name}</span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Notes */}
                {job.notes && (
                    <div className="rounded-xl bg-amber-50 border border-amber-200 p-5">
                        <div className="flex items-start gap-3">
                            <svg className="h-5 w-5 mt-0.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                            </svg>
                            <div>
                                <p className="text-sm font-medium text-amber-800">Parent's Notes</p>
                                <p className="mt-1 text-sm text-amber-700">{job.notes}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Accept Job Form */}
                <div className="rounded-xl bg-white shadow-sm">
                    <div className="border-b px-6 py-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                                <svg className="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="text-base font-semibold text-gray-900">Accept This Job</h3>
                                <p className="text-sm text-gray-500">Set your preferred schedule and location</p>
                            </div>
                        </div>
                    </div>
                    <form onSubmit={submitAccept} className="p-6 space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Schedule Day</label>
                                <select
                                    value={data.schedule_day}
                                    onChange={(e) => setData('schedule_day', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select day</option>
                                    {DAYS.map((d) => (
                                        <option key={d} value={d}>{d.charAt(0).toUpperCase() + d.slice(1)}</option>
                                    ))}
                                </select>
                                {errors.schedule_day && <p className="mt-1 text-sm text-red-600">{errors.schedule_day}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Schedule Time</label>
                                <input
                                    type="time"
                                    value={data.schedule_time}
                                    onChange={(e) => setData('schedule_time', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                {errors.schedule_time && <p className="mt-1 text-sm text-red-600">{errors.schedule_time}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Duration (Hours)</label>
                                <input
                                    type="number"
                                    min={0.5}
                                    step={0.5}
                                    value={data.duration_hours}
                                    onChange={(e) => setData('duration_hours', Number(e.target.value))}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                {errors.duration_hours && <p className="mt-1 text-sm text-red-600">{errors.duration_hours}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Location Type</label>
                                <div className="mt-1 grid grid-cols-3 gap-2">
                                    {[
                                        { value: 'home', label: 'Home', icon: 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25' },
                                        { value: 'online', label: 'Online', icon: 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25' },
                                        { value: 'center', label: 'Center', icon: 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21' },
                                    ].map((opt) => (
                                        <button
                                            key={opt.value}
                                            type="button"
                                            onClick={() => setData('location_type', opt.value)}
                                            className={`flex flex-col items-center gap-1 rounded-lg border-2 p-2.5 text-xs font-medium transition-colors ${
                                                data.location_type === opt.value
                                                    ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                                    : 'border-gray-200 text-gray-500 hover:border-gray-300'
                                            }`}
                                        >
                                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d={opt.icon} />
                                            </svg>
                                            {opt.label}
                                        </button>
                                    ))}
                                </div>
                                {errors.location_type && <p className="mt-1 text-sm text-red-600">{errors.location_type}</p>}
                            </div>
                        </div>

                        {data.location_type !== 'online' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Location Address</label>
                                <textarea
                                    value={data.location_address}
                                    onChange={(e) => setData('location_address', e.target.value)}
                                    rows={2}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter the tutoring location address"
                                />
                                {errors.location_address && <p className="mt-1 text-sm text-red-600">{errors.location_address}</p>}
                            </div>
                        )}

                        <div className="flex items-center justify-between gap-4 border-t pt-4">
                            {/* Decline */}
                            {!showRejectConfirm ? (
                                <button
                                    type="button"
                                    onClick={() => setShowRejectConfirm(true)}
                                    className="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-100"
                                >
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Decline
                                </button>
                            ) : (
                                <div className="flex items-center gap-3">
                                    <span className="text-sm text-gray-500">Are you sure?</span>
                                    <button
                                        type="button"
                                        onClick={handleReject}
                                        className="rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                                    >
                                        Yes, Decline
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowRejectConfirm(false)}
                                        className="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            )}

                            {/* Accept */}
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 rounded-lg bg-green-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                {processing ? (
                                    <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                ) : (
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                )}
                                Accept Job
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
