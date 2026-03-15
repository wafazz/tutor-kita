import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type Student = {
    id: number;
    name: string;
    age: number | null;
    school: string | null;
    education_level: string | null;
    notes: string | null;
};

export default function StudentsIndex({ students }: { students: Student[] }) {
    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to remove this child?')) {
            router.delete(route('parent.students.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        My Children
                    </h2>
                    <Link
                        href={route('parent.students.create')}
                        className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Add Child
                    </Link>
                </div>
            }
        >
            <Head title="My Children" />

            <>
                {students.length === 0 ? (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6 text-center text-gray-500">
                                No children added yet. Click "Add Child" to get started.
                            </div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {students.map((student) => (
                                <div
                                    key={student.id}
                                    className="overflow-hidden rounded-lg bg-white p-6 shadow-sm"
                                >
                                    <h3 className="text-lg font-semibold text-gray-900">
                                        {student.name}
                                    </h3>
                                    <div className="mt-3 space-y-1 text-sm text-gray-600">
                                        {student.age !== null && (
                                            <p>
                                                <span className="font-medium">Age:</span> {student.age}
                                            </p>
                                        )}
                                        {student.school && (
                                            <p>
                                                <span className="font-medium">School:</span> {student.school}
                                            </p>
                                        )}
                                        {student.education_level && (
                                            <p>
                                                <span className="font-medium">Level:</span> {student.education_level}
                                            </p>
                                        )}
                                        {student.notes && (
                                            <p>
                                                <span className="font-medium">Notes:</span> {student.notes}
                                            </p>
                                        )}
                                    </div>
                                    <div className="mt-4 flex gap-3">
                                        <Link
                                            href={route('parent.students.edit', student.id)}
                                            className="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            onClick={() => handleDelete(student.id)}
                                            className="text-sm font-medium text-red-600 hover:text-red-800"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                )}
            </>
        </AuthenticatedLayout>
    );
}
