import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function SubjectsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        category: 'academic',
        education_level: '',
        hourly_rate_home: 0,
        hourly_rate_online: 0,
        is_active: true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.subjects.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Add Subject
                </h2>
            }
        >
            <Head title="Add Subject" />

            <div className="mx-auto max-w-2xl">
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Name</label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Category</label>
                                <select
                                    value={data.category}
                                    onChange={(e) => setData('category', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="academic">Academic</option>
                                    <option value="language">Language</option>
                                    <option value="quran">Quran</option>
                                    <option value="music">Music</option>
                                    <option value="other">Other</option>
                                </select>
                                {errors.category && <p className="mt-1 text-sm text-red-600">{errors.category}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Education Level</label>
                                <input
                                    type="text"
                                    value={data.education_level}
                                    onChange={(e) => setData('education_level', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    placeholder="e.g. SPM, STPM, Degree"
                                />
                                {errors.education_level && <p className="mt-1 text-sm text-red-600">{errors.education_level}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Home Tutor Rate (RM/hr)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.50"
                                        value={data.hourly_rate_home}
                                        onChange={(e) => setData('hourly_rate_home', parseFloat(e.target.value) || 0)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="e.g. 50.00"
                                    />
                                    {errors.hourly_rate_home && <p className="mt-1 text-sm text-red-600">{errors.hourly_rate_home}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Online Class Rate (RM/hr)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.50"
                                        value={data.hourly_rate_online}
                                        onChange={(e) => setData('hourly_rate_online', parseFloat(e.target.value) || 0)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="e.g. 40.00"
                                    />
                                    {errors.hourly_rate_online && <p className="mt-1 text-sm text-red-600">{errors.hourly_rate_online}</p>}
                                </div>
                            </div>

                            <div className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label className="ml-2 block text-sm text-gray-700">Active</label>
                            </div>

                            <div className="flex items-center gap-4">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Create Subject
                                </button>
                                <Link
                                    href={route('admin.subjects.index')}
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
