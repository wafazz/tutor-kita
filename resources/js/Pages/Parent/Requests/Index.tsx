import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type RequestItem = {
    id: number;
    student: { name: string };
    subject: { name: string };
    package: { name: string; price: number } | null;
    preferred_area: string;
    status: string;
    tutor_accepted: boolean;
    matched_tutor: { name: string } | null;
    payment: { id: number; amount: number; status: string } | null;
};

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        open: 'bg-blue-100 text-blue-800',
        matched: 'bg-yellow-100 text-yellow-800',
        closed: 'bg-green-100 text-green-800',
        cancelled: 'bg-gray-100 text-gray-800',
    };
    return map[status] ?? 'bg-gray-100 text-gray-800';
};

const statusLabel = (status: string, tutorAccepted: boolean) => {
    if (status === 'matched' && !tutorAccepted) return 'Awaiting Tutor';
    if (status === 'matched' && tutorAccepted) return 'Awaiting Payment';
    const map: Record<string, string> = {
        open: 'Finding Tutor',
        closed: 'Active',
        cancelled: 'Cancelled',
    };
    return map[status] ?? status;
};

export default function RequestsIndex({ requests }: { requests: RequestItem[] }) {
    const handleCancel = (id: number) => {
        if (confirm('Are you sure you want to cancel this request?')) {
            router.post(route('parent.requests.cancel', id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">My Requests</h2>
                    <Link
                        href={route('parent.requests.create')}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Request
                    </Link>
                </div>
            }
        >
            <Head title="My Requests" />

            <>
                {requests.length === 0 ? (
                    <div className="rounded-xl bg-white p-12 text-center shadow-sm">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100">
                            <svg className="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <h3 className="mt-4 text-base font-semibold text-gray-900">No requests yet</h3>
                        <p className="mt-1 text-sm text-gray-500">Click "New Request" to find a tutor for your child.</p>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Package</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Area</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {requests.map((req) => {
                                        const needsPayment = req.status === 'matched' && req.tutor_accepted && req.payment?.status === 'pending';
                                        return (
                                            <tr key={req.id} className={needsPayment ? 'bg-yellow-50' : ''}>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{req.subject.name}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{req.student.name}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                    {req.package ? (
                                                        <span className="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                                            {req.package.name}
                                                        </span>
                                                    ) : '-'}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{req.preferred_area}</td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusBadge(req.status)}`}>
                                                        {statusLabel(req.status, req.tutor_accepted)}
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                    {req.matched_tutor?.name ?? '-'}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                    <div className="flex items-center gap-3">
                                                        <Link
                                                            href={route('parent.requests.show', req.id)}
                                                            className="font-medium text-indigo-600 hover:text-indigo-800"
                                                        >
                                                            View
                                                        </Link>
                                                        {needsPayment && (
                                                            <Link
                                                                href={route('parent.requests.show', req.id)}
                                                                className="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-indigo-700"
                                                            >
                                                                Pay Now
                                                            </Link>
                                                        )}
                                                        {req.status === 'open' && (
                                                            <button
                                                                onClick={() => handleCancel(req.id)}
                                                                className="font-medium text-red-600 hover:text-red-800"
                                                            >
                                                                Cancel
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </>
        </AuthenticatedLayout>
    );
}
