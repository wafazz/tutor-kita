import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

type TutorRequestFull = {
    id: number;
    parent: { name: string; email: string; phone: string | null };
    student: { name: string; age: number | null; education_level: string | null };
    subject: { name: string };
    package: { name: string; total_sessions: number; duration_hours: number; price: number } | null;
    preferred_area: string;
    preferred_schedule: string | null;
    budget_min: number | null;
    budget_max: number | null;
    notes: string | null;
    status: string;
    matched_tutor: { id: number; name: string } | null;
};

type TutorItem = {
    id: number;
    name: string;
    tutor_profile: {
        hourly_rate: number | null;
        location_area: string | null;
        location_state: string | null;
        rating_avg: number | null;
        experience_years: number | null;
        subjects: string[] | null;
    };
};

type GroupRequest = {
    id: number;
    subject: { name: string };
    status: string;
    matched_tutor: { name: string } | null;
};

type Props = {
    tutorRequest: TutorRequestFull;
    allTutors: TutorItem[];
    groupRequests: GroupRequest[];
};

function statusBadge(status: string) {
    const map: Record<string, string> = {
        open: 'bg-blue-100 text-blue-800',
        matched: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    const cls = map[status] ?? 'bg-gray-100 text-gray-800';
    return <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${cls}`}>{status}</span>;
}

export default function RequestsShow({ tutorRequest, allTutors }: Props) {
    const [search, setSearch] = useState('');
    const [assigning, setAssigning] = useState<number | null>(null);

    const filteredTutors = allTutors.filter((tutor) => {
        if (!search) return true;
        const q = search.toLowerCase();
        return (
            tutor.name.toLowerCase().includes(q) ||
            (tutor.tutor_profile.location_area ?? '').toLowerCase().includes(q) ||
            (tutor.tutor_profile.location_state ?? '').toLowerCase().includes(q) ||
            (tutor.tutor_profile.subjects ?? []).some((s) => s.toLowerCase().includes(q))
        );
    });

    const handleAssign = (tutorId: number) => {
        setAssigning(tutorId);
        router.post(route('admin.requests.match', tutorRequest.id), {
            matched_tutor_id: tutorId,
        }, {
            onFinish: () => setAssigning(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Request #{tutorRequest.id}
                    </h2>
                    <Link
                        href={route('admin.requests.index')}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title={`Request #${tutorRequest.id}`} />

            <div className="mx-auto max-w-4xl">
                {/* Request Details */}
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Parent</dt>
                            <dd className="mt-1 text-sm text-gray-900">{tutorRequest.parent.name}</dd>
                            <dd className="text-sm text-gray-500">{tutorRequest.parent.email}</dd>
                            {tutorRequest.parent.phone && <dd className="text-sm text-gray-500">{tutorRequest.parent.phone}</dd>}
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Student</dt>
                            <dd className="mt-1 text-sm text-gray-900">{tutorRequest.student.name}</dd>
                            {tutorRequest.student.age && <dd className="text-sm text-gray-500">Age: {tutorRequest.student.age}</dd>}
                            {tutorRequest.student.education_level && <dd className="text-sm text-gray-500">Level: {tutorRequest.student.education_level}</dd>}
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Subject</dt>
                            <dd className="mt-1 text-sm text-gray-900">{tutorRequest.subject.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Package</dt>
                            <dd className="mt-1 text-sm text-gray-900">
                                {tutorRequest.package ? (
                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                                        {tutorRequest.package.name} — {tutorRequest.package.total_sessions} sessions x {Number(tutorRequest.package.duration_hours)}h · RM {Number(tutorRequest.package.price).toFixed(2)}
                                    </span>
                                ) : '-'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Preferred Area</dt>
                            <dd className="mt-1 text-sm text-gray-900">{tutorRequest.preferred_area}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Preferred Schedule</dt>
                            <dd className="mt-1 text-sm text-gray-900">{tutorRequest.preferred_schedule ?? '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Budget (RM)</dt>
                            <dd className="mt-1 text-sm text-gray-900">
                                {tutorRequest.budget_min != null && tutorRequest.budget_max != null
                                    ? `${tutorRequest.budget_min} - ${tutorRequest.budget_max}`
                                    : tutorRequest.budget_min ?? tutorRequest.budget_max ?? '-'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Status</dt>
                            <dd className="mt-1">{statusBadge(tutorRequest.status)}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Matched Tutor</dt>
                            <dd className="mt-1 text-sm text-gray-900">
                                {tutorRequest.matched_tutor ? (
                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-0.5 text-sm font-medium text-green-700">
                                        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        {tutorRequest.matched_tutor.name}
                                    </span>
                                ) : '-'}
                            </dd>
                        </div>
                        {tutorRequest.notes && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Notes</dt>
                                <dd className="mt-1 text-sm text-gray-900">{tutorRequest.notes}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                {/* Assign Tutor Section */}
                <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 className="text-lg font-medium text-gray-900">
                                {tutorRequest.matched_tutor ? 'Reassign Tutor' : 'Assign Tutor'}
                            </h3>
                            <p className="mt-1 text-sm text-gray-500">
                                {tutorRequest.matched_tutor
                                    ? `Currently assigned to ${tutorRequest.matched_tutor.name}. Select a different tutor to reassign.`
                                    : 'Select any verified tutor to assign to this request.'}
                            </p>
                        </div>
                        <div className="relative w-full sm:w-72">
                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg className="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by name, area, subject..."
                                className="block w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    {filteredTutors.length === 0 ? (
                        <p className="mt-4 text-sm text-gray-500">No tutors found matching your search.</p>
                    ) : (
                        <div className="mt-4 overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subjects</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rate (RM/hr)</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Area</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rating</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Exp</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {filteredTutors.map((tutor) => {
                                        const isCurrentMatch = tutorRequest.matched_tutor?.id === tutor.id;
                                        return (
                                            <tr key={tutor.id} className={`hover:bg-gray-50 ${isCurrentMatch ? 'bg-green-50' : ''}`}>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                                    <div className="flex items-center gap-2">
                                                        {tutor.name}
                                                        {isCurrentMatch && (
                                                            <span className="inline-flex rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-semibold text-green-700">Current</span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    <div className="flex max-w-[200px] flex-wrap gap-1">
                                                        {(tutor.tutor_profile.subjects ?? []).slice(0, 3).map((s) => (
                                                            <span key={s} className="inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-[11px] text-gray-600">{s}</span>
                                                        ))}
                                                        {(tutor.tutor_profile.subjects ?? []).length > 3 && (
                                                            <span className="text-[11px] text-gray-400">+{(tutor.tutor_profile.subjects ?? []).length - 3}</span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                                    {tutor.tutor_profile.hourly_rate ? Number(tutor.tutor_profile.hourly_rate).toFixed(2) : '-'}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                                    {tutor.tutor_profile.location_area ?? '-'}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                                    {tutor.tutor_profile.rating_avg ? Number(tutor.tutor_profile.rating_avg).toFixed(1) : '-'}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                                    {tutor.tutor_profile.experience_years ? `${tutor.tutor_profile.experience_years} yrs` : '-'}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm">
                                                    {isCurrentMatch ? (
                                                        <span className="text-xs text-green-600 font-medium">Assigned</span>
                                                    ) : tutorRequest.matched_tutor ? (
                                                        <button
                                                            disabled
                                                            className="rounded-md bg-gray-300 px-3 py-1 text-sm font-medium text-gray-500 cursor-not-allowed"
                                                        >
                                                            Assign
                                                        </button>
                                                    ) : (
                                                        <button
                                                            onClick={() => handleAssign(tutor.id)}
                                                            disabled={assigning !== null}
                                                            className="rounded-md bg-indigo-600 px-3 py-1 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                                        >
                                                            {assigning === tutor.id ? 'Assigning...' : 'Assign'}
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <p className="mt-3 text-xs text-gray-400">{filteredTutors.length} of {allTutors.length} verified tutors</p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
