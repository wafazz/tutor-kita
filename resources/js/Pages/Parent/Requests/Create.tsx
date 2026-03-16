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

type Subject = {
    id: number;
    name: string;
    category: string;
};

type PackageItem = {
    id: number;
    name: string;
    package_type: 'all' | 'specific';
    description: string | null;
    total_sessions: number;
    duration_hours: number;
    price: number;
    subjects: { id: number; name: string }[];
};

export default function RequestsCreate({ students, subjects, packages }: { students: Student[]; subjects: Subject[]; packages: PackageItem[] }) {
    const { data, setData, post, processing, errors } = useForm({
        student_id: '',
        subject_id: '',
        package_id: '',
        preferred_area: '',
        preferred_location: '',
        preferred_schedule: '',
        preferred_time: '',
        preferred_tutor_gender: '',
        notes: '',
    });

    const selectedPackage = packages.find((p) => String(p.id) === data.package_id);
    const isMultiSubject = selectedPackage?.package_type === 'specific' && selectedPackage.subjects.length > 1;
    const selectedSubjectId = data.subject_id ? Number(data.subject_id) : null;

    const filteredPackages = packages.filter(pkg => {
        if (pkg.package_type === 'all') return true;
        if (pkg.package_type === 'specific' && pkg.subjects.length > 1) return true; // multi-subject always visible
        if (!selectedSubjectId) return true;
        return pkg.subjects.some(s => s.id === selectedSubjectId);
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('parent.requests.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    New Tutor Request
                </h2>
            }
        >
            <Head title="New Request" />

            <div className="mx-auto max-w-2xl">
                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form onSubmit={submit} className="space-y-6 p-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                Student <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={data.student_id}
                                onChange={(e) => setData('student_id', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="">Select student</option>
                                {students.map((s) => (
                                    <option key={s.id} value={s.id}>{s.name}</option>
                                ))}
                            </select>
                            {errors.student_id && <p className="mt-1 text-sm text-red-600">{errors.student_id}</p>}
                        </div>

                        {!isMultiSubject && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Subject <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.subject_id}
                                    onChange={(e) => { setData('subject_id', e.target.value); setData('package_id', ''); }}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select subject</option>
                                    {subjects.map((s) => (
                                        <option key={s.id} value={s.id}>{s.name} ({s.category})</option>
                                    ))}
                                </select>
                                {errors.subject_id && <p className="mt-1 text-sm text-red-600">{errors.subject_id}</p>}
                            </div>
                        )}

                        {isMultiSubject && selectedPackage && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Subjects Included</label>
                                <div className="flex flex-wrap gap-2">
                                    {selectedPackage.subjects.map(sub => (
                                        <span key={sub.id} className="inline-flex items-center gap-1 rounded-full bg-purple-100 px-3 py-1 text-sm font-medium text-purple-700">
                                            <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            {sub.name}
                                        </span>
                                    ))}
                                </div>
                                <p className="mt-2 text-xs text-gray-500">A separate tutor request will be created for each subject. Different tutors can be assigned.</p>
                            </div>
                        )}

                        {/* Package Selection */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-3">
                                Learning Package <span className="text-red-500">*</span>
                            </label>
                            {filteredPackages.length === 0 ? (
                                <p className="text-sm text-gray-500">{data.subject_id ? 'No packages available for this subject.' : 'Select a subject first to see available packages.'}</p>
                            ) : (
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    {filteredPackages.map((pkg) => {
                                        const isSelected = data.package_id === String(pkg.id);
                                        return (
                                            <button
                                                key={pkg.id}
                                                type="button"
                                                onClick={() => setData('package_id', String(pkg.id))}
                                                className={`relative rounded-xl border-2 p-4 text-left transition-all ${
                                                    isSelected
                                                        ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500'
                                                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                                }`}
                                            >
                                                {isSelected && (
                                                    <div className="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600">
                                                        <svg className="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="3" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                        </svg>
                                                    </div>
                                                )}
                                                <p className="text-base font-semibold text-gray-900">{pkg.name}</p>
                                                {pkg.description && (
                                                    <p className="mt-0.5 text-xs text-gray-500">{pkg.description}</p>
                                                )}
                                                <div className="mt-3 flex items-baseline gap-1">
                                                    <span className="text-xl font-bold text-indigo-600">RM {Number(pkg.price).toFixed(2)}</span>
                                                </div>
                                                <div className="mt-2 flex items-center gap-3 text-xs text-gray-500">
                                                    <span className="flex items-center gap-1">
                                                        <svg className="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                                        </svg>
                                                        {pkg.total_sessions} session{pkg.total_sessions > 1 ? 's' : ''}
                                                    </span>
                                                    <span className="flex items-center gap-1">
                                                        <svg className="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        {Number(pkg.duration_hours)}h each
                                                    </span>
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                            {errors.package_id && <p className="mt-1 text-sm text-red-600">{errors.package_id}</p>}
                        </div>

                        {/* Selected package summary */}
                        {selectedPackage && (
                            <div className="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm">
                                        <span className="font-medium text-indigo-800">Selected: </span>
                                        <span className="text-indigo-700">{selectedPackage.name} — {selectedPackage.total_sessions} sessions x {Number(selectedPackage.duration_hours)}h</span>
                                    </div>
                                    <span className="text-sm font-bold text-indigo-800">RM {Number(selectedPackage.price).toFixed(2)}</span>
                                </div>
                            </div>
                        )}

                        {/* Preferred Location */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-3">
                                Preferred Location <span className="text-red-500">*</span>
                            </label>
                            <div className="grid grid-cols-2 gap-3">
                                {[
                                    { value: 'home', label: 'Home Tutor', icon: 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25' },
                                    { value: 'online', label: 'Online Class', icon: 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25' },
                                ].map((opt) => (
                                    <button
                                        key={opt.value}
                                        type="button"
                                        onClick={() => setData('preferred_location', opt.value)}
                                        className={`relative flex items-center gap-3 rounded-xl border-2 p-4 text-left transition-all ${
                                            data.preferred_location === opt.value
                                                ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500'
                                                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                        }`}
                                    >
                                        {data.preferred_location === opt.value && (
                                            <div className="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600">
                                                <svg className="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="3" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        )}
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50">
                                            <svg className="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d={opt.icon} />
                                            </svg>
                                        </div>
                                        <span className="text-sm font-semibold text-gray-900">{opt.label}</span>
                                    </button>
                                ))}
                            </div>
                            {errors.preferred_location && <p className="mt-1 text-sm text-red-600">{errors.preferred_location}</p>}
                        </div>

                        {/* Preferred Area — only show for home tutor */}
                        {data.preferred_location === 'home' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Preferred Area <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.preferred_area}
                                    onChange={(e) => setData('preferred_area', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="e.g. Shah Alam, Subang Jaya"
                                    required
                                />
                                {errors.preferred_area && <p className="mt-1 text-sm text-red-600">{errors.preferred_area}</p>}
                            </div>
                        )}

                        {/* Preferred Time */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                Preferred Time <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.preferred_time}
                                onChange={(e) => setData('preferred_time', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="e.g. Weekdays after 3pm, Saturday morning"
                                required
                            />
                            {errors.preferred_time && <p className="mt-1 text-sm text-red-600">{errors.preferred_time}</p>}
                        </div>

                        {/* Tutor Gender Preference */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-3">
                                Preferred Tutor Gender <span className="text-red-500">*</span>
                            </label>
                            <div className="grid grid-cols-3 gap-3">
                                {[
                                    { value: 'male', label: 'Male' },
                                    { value: 'female', label: 'Female' },
                                    { value: 'both', label: 'Both' },
                                ].map((opt) => (
                                    <button
                                        key={opt.value}
                                        type="button"
                                        onClick={() => setData('preferred_tutor_gender', opt.value)}
                                        className={`relative rounded-xl border-2 px-4 py-3 text-center text-sm font-semibold transition-all ${
                                            data.preferred_tutor_gender === opt.value
                                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500'
                                                : 'border-gray-200 text-gray-700 hover:border-gray-300 hover:bg-gray-50'
                                        }`}
                                    >
                                        {data.preferred_tutor_gender === opt.value && (
                                            <div className="absolute right-2 top-2 flex h-4 w-4 items-center justify-center rounded-full bg-indigo-600">
                                                <svg className="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="3" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        )}
                                        {opt.label}
                                    </button>
                                ))}
                            </div>
                            {errors.preferred_tutor_gender && <p className="mt-1 text-sm text-red-600">{errors.preferred_tutor_gender}</p>}
                        </div>

                        {/* Preferred Schedule */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Preferred Schedule</label>
                            <textarea
                                value={data.preferred_schedule}
                                onChange={(e) => setData('preferred_schedule', e.target.value)}
                                rows={2}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="e.g. Monday & Wednesday, 2 times per week"
                            />
                            {errors.preferred_schedule && <p className="mt-1 text-sm text-red-600">{errors.preferred_schedule}</p>}
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
                                Submit Request
                            </button>
                            <Link
                                href={route('parent.requests.index')}
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
