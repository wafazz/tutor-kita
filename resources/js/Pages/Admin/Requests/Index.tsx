import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type TutorRequestItem = {
    id: number;
    parent: { name: string };
    student: { name: string };
    subject: { name: string };
    preferred_area: string;
    budget_min: number | null;
    budget_max: number | null;
    status: string;
    matched_tutor: { name: string } | null;
};

type Props = {
    requests: {
        data: TutorRequestItem[];
        links: any[];
    };
    filters: {
        status: string;
    };
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

export default function RequestsIndex({ requests, filters }: Props) {
    const handleFilter = (status: string) => {
        router.get(route('admin.requests.index'), { status: status || undefined }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Tutor Requests
                </h2>
            }
        >
            <Head title="Tutor Requests" />

            <>
                <div className="mb-4">
                        <select
                            value={filters.status ?? ''}
                            onChange={(e) => handleFilter(e.target.value)}
                            className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">All Statuses</option>
                            <option value="open">Open</option>
                            <option value="matched">Matched</option>
                            <option value="closed">Closed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">ID</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Parent</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Area</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Budget (RM)</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Matched Tutor</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {requests.data.map((req) => (
                                    <tr key={req.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{req.id}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{req.parent.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{req.student.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{req.subject.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{req.preferred_area}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {req.budget_min != null && req.budget_max != null
                                                ? `${req.budget_min} - ${req.budget_max}`
                                                : req.budget_min ?? req.budget_max ?? '-'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">{statusBadge(req.status)}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{req.matched_tutor?.name ?? '-'}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            <Link
                                                href={route('admin.requests.show', req.id)}
                                                className="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {requests.data.length === 0 && (
                                    <tr>
                                        <td colSpan={9} className="px-6 py-4 text-center text-sm text-gray-500">No requests found.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                <div className="mt-4 flex justify-center space-x-1">
                    {requests.links.map((link: any, i: number) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            className={`rounded px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-indigo-600 text-white'
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
