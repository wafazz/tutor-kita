import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';

type SubjectItem = {
    id: number;
    name: string;
};

type PackageItem = {
    id: number;
    name: string;
    package_type: 'all' | 'specific';
    description: string | null;
    total_sessions: number;
    duration_hours: number;
    price: number;
    is_active: boolean;
    sort_order: number;
    subjects: SubjectItem[];
};

type Props = {
    packages: PackageItem[];
    subjects: SubjectItem[];
};

function PackageForm({ pkg, subjects, onClose }: { pkg?: PackageItem; subjects: SubjectItem[]; onClose: () => void }) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: pkg?.name ?? '',
        package_type: 'specific' as 'all' | 'specific',
        subject_ids: pkg?.subjects?.map(s => s.id) ?? subjects.map(s => s.id) as number[],
        description: pkg?.description ?? '',
        total_sessions: pkg?.total_sessions ?? 4,
        duration_hours: pkg?.duration_hours ?? 1,
        price: pkg?.price ?? 0,
        is_active: pkg?.is_active ?? true,
        sort_order: pkg?.sort_order ?? 0,
    });

    const toggleSubject = (id: number) => {
        setData('subject_ids', data.subject_ids.includes(id)
            ? data.subject_ids.filter(s => s !== id)
            : [...data.subject_ids, id]
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (pkg) {
            put(route('admin.packages.update', pkg.id), {
                onSuccess: () => { reset(); onClose(); },
            });
        } else {
            post(route('admin.packages.store'), {
                onSuccess: () => { reset(); onClose(); },
            });
        }
    };

    return (
        <div className="rounded-xl border-2 border-indigo-200 bg-indigo-50/50 p-6">
            <h3 className="text-base font-semibold text-gray-900 mb-4">
                {pkg ? 'Edit Package' : 'New Package'}
            </h3>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div className="sm:col-span-2">
                        <label className="block text-sm font-medium text-gray-700">Package Name</label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g. Basic Package, Premium Package"
                            required
                        />
                        {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                    </div>

                    <div className="sm:col-span-2">
                        <label className="block text-sm font-medium text-gray-700 mb-2">Applies To</label>
                        <div className="flex flex-wrap gap-2">
                            {subjects.map(sub => (
                                <button
                                    key={sub.id}
                                    type="button"
                                    onClick={() => toggleSubject(sub.id)}
                                    className={`rounded-full px-3 py-1.5 text-sm font-medium transition-colors ${
                                        data.subject_ids.includes(sub.id)
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    {sub.name}
                                </button>
                            ))}
                        </div>
                        {subjects.length === 0 && (
                            <p className="mt-1 text-sm text-gray-400">No subjects available. Create subjects first.</p>
                        )}
                        {errors.subject_ids && <p className="mt-1 text-sm text-red-600">{errors.subject_ids}</p>}
                    </div>

                    <div className="sm:col-span-2">
                        <label className="block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={2}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Brief description of this package"
                        />
                        {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Total Sessions</label>
                        <input
                            type="number"
                            value={data.total_sessions}
                            onChange={(e) => setData('total_sessions', Number(e.target.value))}
                            min={1}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                        {errors.total_sessions && <p className="mt-1 text-sm text-red-600">{errors.total_sessions}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Duration per Session (Hours)</label>
                        <input
                            type="number"
                            value={data.duration_hours}
                            onChange={(e) => setData('duration_hours', Number(e.target.value))}
                            min={0.5}
                            step={0.5}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                        {errors.duration_hours && <p className="mt-1 text-sm text-red-600">{errors.duration_hours}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Price (RM)</label>
                        <input
                            type="number"
                            value={data.price}
                            onChange={(e) => setData('price', Number(e.target.value))}
                            min={0}
                            step={0.01}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                        {errors.price && <p className="mt-1 text-sm text-red-600">{errors.price}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Sort Order</label>
                        <input
                            type="number"
                            value={data.sort_order}
                            onChange={(e) => setData('sort_order', Number(e.target.value))}
                            min={0}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        {errors.sort_order && <p className="mt-1 text-sm text-red-600">{errors.sort_order}</p>}
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="is_active"
                        checked={data.is_active}
                        onChange={(e) => setData('is_active', e.target.checked)}
                        className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label htmlFor="is_active" className="text-sm text-gray-700">Active (visible to parents)</label>
                </div>

                <div className="flex items-center gap-3 border-t pt-4">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {processing ? 'Saving...' : (pkg ? 'Update Package' : 'Create Package')}
                    </button>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    );
}

export default function PackagesIndex({ packages, subjects }: Props) {
    const [showForm, setShowForm] = useState(false);
    const [editingPkg, setEditingPkg] = useState<PackageItem | undefined>(undefined);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const handleEdit = (pkg: PackageItem) => {
        setEditingPkg(pkg);
        setShowForm(true);
    };

    const handleDelete = (id: number) => {
        router.delete(route('admin.packages.destroy', id), {
            onSuccess: () => setDeletingId(null),
        });
    };

    const handleClose = () => {
        setShowForm(false);
        setEditingPkg(undefined);
    };

    const handleNew = () => {
        setEditingPkg(undefined);
        setShowForm(true);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Packages</h2>
                    {!showForm && (
                        <button
                            onClick={handleNew}
                            className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Package
                        </button>
                    )}
                </div>
            }
        >
            <Head title="Packages" />

            <div className="space-y-6">
                {showForm && (
                    <PackageForm pkg={editingPkg} subjects={subjects} onClose={handleClose} />
                )}

                {packages.length === 0 && !showForm ? (
                    <div className="rounded-xl bg-white p-12 text-center shadow-sm">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100">
                            <svg className="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                        <h3 className="mt-4 text-base font-semibold text-gray-900">No packages yet</h3>
                        <p className="mt-1 text-sm text-gray-500">Create your first learning session package for parents to choose from.</p>
                        <button
                            onClick={handleNew}
                            className="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Create Package
                        </button>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {packages.map((pkg) => (
                            <div key={pkg.id} className={`relative overflow-hidden rounded-xl bg-white shadow-sm transition-all hover:shadow-md ${!pkg.is_active ? 'opacity-60' : ''}`}>
                                <div className="p-6">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <h3 className="text-lg font-semibold text-gray-900">{pkg.name}</h3>
                                            {pkg.description && (
                                                <p className="mt-1 text-sm text-gray-500 line-clamp-2">{pkg.description}</p>
                                            )}
                                        </div>
                                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${pkg.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                            {pkg.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>

                                    {/* Subject badges */}
                                    <div className="mt-3 flex flex-wrap gap-1">
                                        {pkg.subjects.map(sub => (
                                            <span key={sub.id} className="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">
                                                {sub.name}
                                            </span>
                                        ))}
                                        {pkg.subjects.length === 0 && (
                                            <span className="text-xs text-gray-400">No subjects assigned</span>
                                        )}
                                    </div>

                                    <div className="mt-4 flex items-baseline gap-1">
                                        <span className="text-3xl font-bold text-gray-900">RM {Number(pkg.price).toFixed(2)}</span>
                                    </div>

                                    <div className="mt-4 space-y-2">
                                        <div className="flex items-center gap-2 text-sm text-gray-600">
                                            <svg className="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            {pkg.total_sessions} session{pkg.total_sessions > 1 ? 's' : ''}
                                        </div>
                                        <div className="flex items-center gap-2 text-sm text-gray-600">
                                            <svg className="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {Number(pkg.duration_hours)} hour{Number(pkg.duration_hours) > 1 ? 's' : ''} per session
                                        </div>
                                    </div>

                                    <div className="mt-5 flex items-center gap-2 border-t pt-4">
                                        <button
                                            onClick={() => handleEdit(pkg)}
                                            className="flex-1 rounded-lg bg-indigo-50 px-3 py-2 text-center text-sm font-medium text-indigo-600 hover:bg-indigo-100"
                                        >
                                            Edit
                                        </button>
                                        {deletingId === pkg.id ? (
                                            <div className="flex flex-1 items-center gap-1">
                                                <button
                                                    onClick={() => handleDelete(pkg.id)}
                                                    className="flex-1 rounded-lg bg-red-600 px-2 py-2 text-center text-xs font-medium text-white hover:bg-red-700"
                                                >
                                                    Confirm
                                                </button>
                                                <button
                                                    onClick={() => setDeletingId(null)}
                                                    className="flex-1 rounded-lg bg-gray-100 px-2 py-2 text-center text-xs font-medium text-gray-600 hover:bg-gray-200"
                                                >
                                                    No
                                                </button>
                                            </div>
                                        ) : (
                                            <button
                                                onClick={() => setDeletingId(pkg.id)}
                                                className="flex-1 rounded-lg bg-red-50 px-3 py-2 text-center text-sm font-medium text-red-600 hover:bg-red-100"
                                            >
                                                Delete
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
