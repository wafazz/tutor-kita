import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Student = {
    id: number;
    name: string;
    age: number | null;
    school: string | null;
    education_level: string | null;
    notes: string | null;
};

const EDUCATION_LEVELS = ['UPSR', 'PT3', 'SPM', 'STPM', 'Diploma', 'Degree', 'Other'];

export default function StudentsEdit({ student }: { student: Student }) {
    const { data, setData, put, processing, errors } = useForm({
        name: student.name,
        age: student.age !== null ? String(student.age) : '',
        school: student.school ?? '',
        education_level: student.education_level ?? '',
        notes: student.notes ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('parent.students.update', student.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Edit Child
                </h2>
            }
        >
            <Head title="Edit Child" />

            <div className="mx-auto max-w-2xl">
                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6 p-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Name <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Age</label>
                                <input
                                    type="number"
                                    value={data.age}
                                    onChange={(e) => setData('age', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    min="1"
                                    max="99"
                                />
                                {errors.age && <p className="mt-1 text-sm text-red-600">{errors.age}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">School</label>
                                <input
                                    type="text"
                                    value={data.school}
                                    onChange={(e) => setData('school', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                {errors.school && <p className="mt-1 text-sm text-red-600">{errors.school}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Education Level</label>
                                <select
                                    value={data.education_level}
                                    onChange={(e) => setData('education_level', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select level</option>
                                    {EDUCATION_LEVELS.map((level) => (
                                        <option key={level} value={level}>{level}</option>
                                    ))}
                                </select>
                                {errors.education_level && <p className="mt-1 text-sm text-red-600">{errors.education_level}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    rows={3}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                {errors.notes && <p className="mt-1 text-sm text-red-600">{errors.notes}</p>}
                            </div>

                            <div className="flex items-center gap-4">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Update
                                </button>
                                <Link
                                    href={route('parent.students.index')}
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Cancel
                                </Link>
                            </div>
                        </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
