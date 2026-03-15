import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

type Subject = { id: number; name: string; category: string };
type Props = { subjects: Subject[] };

const states = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis',
    'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu',
    'WP Kuala Lumpur', 'WP Putrajaya', 'WP Labuan',
];

export default function Create({ subjects }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        ic_number: '',
        subjects: [] as string[],
        education_level: '',
        experience_years: 0,
        bio: '',
        hourly_rate: 0,
        commission_rate: 20,
        location_area: '',
        location_state: '',
    });

    const grouped = subjects.reduce<Record<string, Subject[]>>((acc, s) => {
        (acc[s.category] = acc[s.category] || []).push(s);
        return acc;
    }, {});

    const toggleSubject = (name: string) => {
        setData('subjects', data.subjects.includes(name)
            ? data.subjects.filter(s => s !== name)
            : [...data.subjects, name]
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.tutors.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Add Tutor</h2>
                    <Link href={route('admin.tutors.index')} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title="Add Tutor" />

            <div className="mx-auto max-w-4xl">
                <form onSubmit={submit} className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Name *</label>
                                <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Email *</label>
                                <input type="email" value={data.email} onChange={e => setData('email', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Phone</label>
                                <input type="text" value={data.phone} onChange={e => setData('phone', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Password *</label>
                                <input type="password" value={data.password} onChange={e => setData('password', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">IC Number</label>
                                <input type="text" value={data.ic_number} onChange={e => setData('ic_number', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Education Level</label>
                                <select value={data.education_level} onChange={e => setData('education_level', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select</option>
                                    <option value="SPM">SPM</option>
                                    <option value="STPM">STPM</option>
                                    <option value="Diploma">Diploma</option>
                                    <option value="Degree">Degree</option>
                                    <option value="Masters">Masters</option>
                                    <option value="PhD">PhD</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Experience (Years)</label>
                                <input type="number" min="0" value={data.experience_years} onChange={e => setData('experience_years', parseInt(e.target.value) || 0)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Hourly Rate (RM) *</label>
                                <input type="number" min="0" step="0.50" value={data.hourly_rate} onChange={e => setData('hourly_rate', parseFloat(e.target.value) || 0)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                {errors.hourly_rate && <p className="mt-1 text-sm text-red-600">{errors.hourly_rate}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Commission Rate (%)</label>
                                <input type="number" min="0" max="100" step="0.5" value={data.commission_rate} onChange={e => setData('commission_rate', parseFloat(e.target.value) || 0)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                {errors.commission_rate && <p className="mt-1 text-sm text-red-600">{errors.commission_rate}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Location Area</label>
                                <input type="text" value={data.location_area} onChange={e => setData('location_area', e.target.value)} placeholder="e.g. Petaling Jaya"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">State</label>
                                <select value={data.location_state} onChange={e => setData('location_state', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select State</option>
                                    {states.map(s => <option key={s} value={s}>{s}</option>)}
                                </select>
                            </div>
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-medium text-gray-700">Bio</label>
                                <textarea rows={3} value={data.bio} onChange={e => setData('bio', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 mb-2">Subjects</label>
                                {Object.entries(grouped).map(([category, items]) => (
                                    <div key={category} className="mb-3">
                                        <p className="text-xs font-semibold uppercase text-gray-400 mb-1">{category}</p>
                                        <div className="flex flex-wrap gap-2">
                                            {items.map(s => (
                                                <label key={s.id} className="inline-flex items-center gap-1.5 text-sm">
                                                    <input type="checkbox" checked={data.subjects.includes(s.name)} onChange={() => toggleSubject(s.name)}
                                                        className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                                    {s.name}
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end">
                            <button type="submit" disabled={processing}
                                className="rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                {processing ? 'Saving...' : 'Add Tutor'}
                            </button>
                        </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
