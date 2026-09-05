import { usePostcodeLookup } from '@/hooks/usePostcodeLookup';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const EDUCATION_LEVELS = ['UPSR', 'PT3', 'SPM', 'STPM', 'Diploma', 'Degree', 'Other'];

export default function StudentsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        age: '',
        school: '',
        education_level: '',
        notes: '',
        address: '',
        area: '',
        state: '',
        postcode: '',
    });

    const { lookup, status: postcodeStatus } = usePostcodeLookup();

    const onPostcode = (value: string) => {
        setData('postcode', value);
        // Fills the area and state so they do not have to be typed, and are
        // spelled the same way every time.
        lookup(value, ({ city, state }) => setData((current: typeof data) => ({ ...current, area: city, state })));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('parent.students.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Add Child
                </h2>
            }
        >
            <Head title="Add Child" />

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

                            <div className="border-t border-gray-200 pt-6">
                                <h3 className="text-base font-semibold text-gray-900">Home address</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Used to find tutors near you when the tutor travels to your home, or to
                                    show which centres are within reach. Optional, but without it we cannot
                                    match by distance.
                                </p>

                                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700">Address</label>
                                        <input
                                            type="text"
                                            value={data.address}
                                            onChange={(e) => setData('address', e.target.value)}
                                            placeholder="e.g. 12 Jalan Mawar 3"
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.address && <p className="mt-1 text-sm text-red-600">{errors.address}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Area</label>
                                        <input
                                            type="text"
                                            value={data.area}
                                            onChange={(e) => setData('area', e.target.value)}
                                            placeholder="e.g. Petaling Jaya"
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.area && <p className="mt-1 text-sm text-red-600">{errors.area}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Postcode</label>
                                        <input
                                            type="text"
                                            value={data.postcode}
                                            onChange={(e) => onPostcode(e.target.value)}
                                            placeholder="e.g. 46000"
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.postcode && <p className="mt-1 text-sm text-red-600">{errors.postcode}</p>}
                                        {postcodeStatus === 'looking' && <p className="mt-1 text-xs text-gray-400">Looking up…</p>}
                                        {postcodeStatus === 'found' && <p className="mt-1 text-xs text-green-600">Area and state filled in for you.</p>}
                                        {postcodeStatus === 'unknown' && <p className="mt-1 text-xs text-amber-600">We do not recognise that postcode — fill the area and state yourself.</p>}

                                    </div>

                                    <div className="sm:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700">State</label>
                                        <input
                                            type="text"
                                            value={data.state}
                                            onChange={(e) => setData('state', e.target.value)}
                                            placeholder="e.g. Selangor"
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.state && <p className="mt-1 text-sm text-red-600">{errors.state}</p>}
                                    </div>
                                </div>
                            </div>


                            <div className="flex items-center gap-4">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Save
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
