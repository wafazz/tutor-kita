import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

type Subject = {
    id: number;
    name: string;
    category: string;
    education_level: string | null;
    hourly_rate: string;
    is_active: boolean;
};

type Props = {
    subjects: {
        data: Subject[];
        links: any[];
    };
};

export default function SubjectsIndex({ subjects }: Props) {
    const { flash } = usePage<{ flash: { success?: string } }>().props;

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this subject?')) {
            router.delete(route('admin.subjects.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Subjects
                </h2>
            }
        >
            <Head title="Subjects" />

            <>
                {flash.success && (
                    <div className="mb-4 rounded-md bg-green-50 border border-green-200 p-4">
                        <p className="text-sm font-medium text-green-800">{flash.success}</p>
                    </div>
                )}
                <div className="mb-4 flex items-center justify-between">
                    <p className="text-sm text-gray-500">{subjects.data.length} subject(s)</p>
                    <Link
                        href={route('admin.subjects.create')}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        + Add Subject
                    </Link>
                </div>
                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">ID</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Category</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Education Level</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rate (RM/hr)</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Active</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {subjects.data.map((subject) => (
                                    <tr key={subject.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{subject.id}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{subject.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500 capitalize">{subject.category}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{subject.education_level ?? '-'}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900 font-medium">
                                            {Number(subject.hourly_rate).toFixed(2)}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            {subject.is_active ? (
                                                <span className="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Yes</span>
                                            ) : (
                                                <span className="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">No</span>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm space-x-2">
                                            <Link
                                                href={route('admin.subjects.edit', subject.id)}
                                                className="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(subject.id)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                                {subjects.data.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-6 py-4 text-center text-sm text-gray-500">No subjects found.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                </div>

                <div className="mt-4 flex justify-center space-x-1">
                    {subjects.links.map((link: any, i: number) => (
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
