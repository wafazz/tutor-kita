import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type TutorWithProfile = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    tutor_profile: {
        subjects: string[];
        hourly_rate: string;
        commission_rate: string;
        verification_status: string;
    } | null;
};

type Props = {
    tutors: {
        data: TutorWithProfile[];
        links: any[];
    };
};

function statusBadge(status: string) {
    switch (status) {
        case 'verified':
            return <span className="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Verified</span>;
        case 'rejected':
            return <span className="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">Rejected</span>;
        default:
            return <span className="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-800">Pending</span>;
    }
}

export default function TutorsIndex({ tutors }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Tutors
                </h2>
            }
        >
            <Head title="Tutors" />

            <>
                <div className="mb-4 flex items-center justify-between">
                    <p className="text-sm text-gray-500">{tutors.data.length} tutor(s)</p>
                    <Link
                        href={route('admin.tutors.create')}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        + Add Tutor
                    </Link>
                </div>
                <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Phone</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subjects</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rate (RM)</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Commission (%)</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Verification</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {tutors.data.map((tutor) => (
                                    <tr key={tutor.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{tutor.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{tutor.email}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{tutor.phone ?? '-'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {tutor.tutor_profile?.subjects?.join(', ') ?? '-'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {tutor.tutor_profile?.hourly_rate ? Number(tutor.tutor_profile.hourly_rate).toFixed(2) : '-'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {tutor.tutor_profile?.commission_rate ? Number(tutor.tutor_profile.commission_rate).toFixed(1) + '%' : '-'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            {statusBadge(tutor.tutor_profile?.verification_status ?? 'pending')}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            <Link
                                                href={route('admin.tutors.show', tutor.id)}
                                                className="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {tutors.data.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="px-6 py-4 text-center text-sm text-gray-500">No tutors found.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                </div>

                <div className="mt-4 flex justify-center space-x-1">
                    {tutors.links.map((link: any, i: number) => (
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
