import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

type Tutor = {
    id: number;
    name: string;
    subjects: string[];
    education_level: string | null;
    experience_years: number | null;
    hourly_rate: string;
    location_area: string | null;
    location_state: string | null;
    rating_avg: string | null;
    total_sessions: number | null;
    bio: string | null;
};

type Props = {
    tutors: { data: Tutor[]; links: any[]; current_page: number; last_page: number };
    subjects: string[];
    states: string[];
    filters: { subject?: string; area?: string; state?: string; sort?: string };
};

function StarDisplay({ rating }: { rating: number }) {
    return (
        <div className="flex gap-0.5">
            {[1, 2, 3, 4, 5].map((star) => (
                <svg
                    key={star}
                    className={`h-4 w-4 ${star <= rating ? 'text-yellow-400' : 'text-gray-300'}`}
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
            ))}
        </div>
    );
}

export default function TutorsBrowse({ tutors, subjects, states, filters }: Props) {
    const [subject, setSubject] = useState(filters.subject ?? '');
    const [area, setArea] = useState(filters.area ?? '');
    const [state, setState] = useState(filters.state ?? '');
    const [sort, setSort] = useState(filters.sort ?? 'rating');

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (subject) params.subject = subject;
        if (area) params.area = area;
        if (state) params.state = state;
        if (sort) params.sort = sort;
        router.get(route('tutors.browse'), params, { preserveState: true });
    };

    const clearFilters = () => {
        setSubject('');
        setArea('');
        setState('');
        setSort('rating');
        router.get(route('tutors.browse'));
    };

    return (
        <>
            <Head title="Browse Tutors - TutorHUB" />

            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <div className="bg-gradient-to-r from-indigo-600 to-purple-600 py-12">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <Link href="/" className="text-2xl font-bold text-white">TutorHUB</Link>
                                <h1 className="mt-2 text-3xl font-bold text-white">Find Your Perfect Tutor</h1>
                                <p className="mt-1 text-indigo-100">Browse verified tutors in your area</p>
                            </div>
                            <div className="flex gap-3">
                                <Link
                                    href={route('login')}
                                    className="rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white hover:bg-white/20"
                                >
                                    Login
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="rounded-lg bg-white px-4 py-2 text-sm font-medium text-indigo-600 hover:bg-gray-100"
                                >
                                    Register
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="mb-8 rounded-xl bg-white p-6 shadow-sm">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Subject</label>
                                <select
                                    value={subject}
                                    onChange={(e) => setSubject(e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="">All Subjects</option>
                                    {subjects.map((s) => (
                                        <option key={s} value={s}>{s}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Area</label>
                                <input
                                    type="text"
                                    value={area}
                                    onChange={(e) => setArea(e.target.value)}
                                    placeholder="e.g. Petaling Jaya"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">State</label>
                                <select
                                    value={state}
                                    onChange={(e) => setState(e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="">All States</option>
                                    {states.map((s) => (
                                        <option key={s} value={s}>{s}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Sort By</label>
                                <select
                                    value={sort}
                                    onChange={(e) => setSort(e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="rating">Highest Rating</option>
                                    <option value="rate_low">Lowest Rate</option>
                                    <option value="rate_high">Highest Rate</option>
                                    <option value="experience">Most Experience</option>
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <button
                                    onClick={applyFilters}
                                    className="flex-1 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                >
                                    Search
                                </button>
                                <button
                                    onClick={clearFilters}
                                    className="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Results */}
                    {tutors.data.length === 0 ? (
                        <div className="rounded-xl bg-white p-12 text-center shadow-sm">
                            <svg className="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                            </svg>
                            <p className="mt-4 text-lg font-medium text-gray-600">No tutors found</p>
                            <p className="text-sm text-gray-400">Try adjusting your filters</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {tutors.data.map((tutor) => {
                                const rating = Number(tutor.rating_avg) || 0;
                                return (
                                    <div key={tutor.id} className="overflow-hidden rounded-xl bg-white shadow-sm transition-all hover:shadow-md">
                                        <div className="p-6">
                                            <div className="flex items-start justify-between">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-lg font-bold text-indigo-600">
                                                        {tutor.name.charAt(0)}
                                                    </div>
                                                    <div>
                                                        <h3 className="font-semibold text-gray-900">{tutor.name}</h3>
                                                        <p className="text-xs text-gray-500">{tutor.education_level ?? '-'}</p>
                                                    </div>
                                                </div>
                                                <span className="rounded-full bg-indigo-100 px-2.5 py-0.5 text-sm font-bold text-indigo-700">
                                                    RM{Number(tutor.hourly_rate).toFixed(0)}/hr
                                                </span>
                                            </div>

                                            <div className="mt-4 flex items-center gap-2">
                                                <StarDisplay rating={Math.round(rating)} />
                                                <span className="text-sm font-medium text-gray-600">
                                                    {rating > 0 ? rating.toFixed(1) : 'New'}
                                                </span>
                                                {(tutor.total_sessions ?? 0) > 0 && (
                                                    <span className="text-xs text-gray-400">
                                                        ({tutor.total_sessions} sessions)
                                                    </span>
                                                )}
                                            </div>

                                            {tutor.bio && (
                                                <p className="mt-3 line-clamp-2 text-sm text-gray-600">{tutor.bio}</p>
                                            )}

                                            <div className="mt-4 flex flex-wrap gap-1.5">
                                                {tutor.subjects.slice(0, 4).map((s) => (
                                                    <span key={s} className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                                        {s}
                                                    </span>
                                                ))}
                                                {tutor.subjects.length > 4 && (
                                                    <span className="text-xs text-gray-400">+{tutor.subjects.length - 4} more</span>
                                                )}
                                            </div>

                                            <div className="mt-4 flex items-center gap-4 text-xs text-gray-500">
                                                {tutor.location_area && (
                                                    <span className="flex items-center gap-1">
                                                        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 0115 0z" />
                                                        </svg>
                                                        {tutor.location_area}{tutor.location_state ? `, ${tutor.location_state}` : ''}
                                                    </span>
                                                )}
                                                {(tutor.experience_years ?? 0) > 0 && (
                                                    <span>{tutor.experience_years} yrs exp</span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
