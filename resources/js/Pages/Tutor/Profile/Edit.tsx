import { usePostcodeLookup } from '@/hooks/usePostcodeLookup';
import LocationPicker from '@/Components/LocationPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface TutorProfile {
    ic_number: string | null;
    subjects: string[];
    education_level: string | null;
    experience_years: number;
    bio: string | null;
    hourly_rate: string;
    location_area: string;
    location_state: string;
    availability: string[] | null;
    verification_status: string;
    address: string | null;
    postcode: string | null;
    travel_radius_km: number | null;
    latitude: string | null;
    longitude: string | null;
    bank_name: string | null;
    bank_account_number: string | null;
    bank_account_name: string | null;
}

interface SubjectOption {
    id: number;
    name: string;
    category: string;
}

interface Props {
    profile: TutorProfile;
    subjects: SubjectOption[];
}

const MALAYSIAN_STATES = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang',
    'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor',
    'Terengganu', 'WP Kuala Lumpur', 'WP Putrajaya', 'WP Labuan',
];

const EDUCATION_LEVELS = [
    'SPM', 'STPM', 'Diploma', 'Degree', 'Masters', 'PhD',
];

const DAYS_OF_WEEK = [
    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
];

function VerificationBadge({ status }: { status: string }) {
    const styles: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        verified: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    };

    return (
        <span className={`inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ${styles[status] || 'bg-gray-100 text-gray-800'}`}>
            {status.charAt(0).toUpperCase() + status.slice(1)}
        </span>
    );
}

export default function Edit({ profile, subjects }: Props) {
    const initialSubjectIds = subjects
        .filter(s => (profile.subjects || []).includes(s.name))
        .map(s => String(s.id));

    const { data, setData, put, processing, errors } = useForm({
        ic_number: profile.ic_number || '',
        subjects: initialSubjectIds,
        education_level: profile.education_level || '',
        experience_years: profile.experience_years || 0,
        bio: profile.bio || '',
        hourly_rate: profile.hourly_rate || 0,
        location_area: profile.location_area || '',
        location_state: profile.location_state || '',
        availability: profile.availability || [],
        address: profile.address || '',
        postcode: profile.postcode || '',
        travel_radius_km: profile.travel_radius_km ?? '',
        latitude: (profile.latitude ?? '') as string,
        longitude: (profile.longitude ?? '') as string,
        bank_name: profile.bank_name || '',
        bank_account_number: profile.bank_account_number || '',
        bank_account_name: profile.bank_account_name || '',
    });

    const { lookup, status: postcodeStatus } = usePostcodeLookup();

    const onPostcode = (value: string) => {
        setData('postcode', value);
        lookup(value, ({ city, state }) =>
            setData((current: typeof data) => ({ ...current, location_area: city, location_state: state })));
    };

    const grouped = subjects.reduce<Record<string, SubjectOption[]>>((acc, s) => {
        if (!acc[s.category]) acc[s.category] = [];
        acc[s.category].push(s);
        return acc;
    }, {});

    const toggleSubject = (id: string) => {
        const current = [...data.subjects];
        const idx = current.indexOf(id);
        if (idx === -1) {
            current.push(id);
        } else {
            current.splice(idx, 1);
        }
        setData('subjects', current);
    };

    const toggleDay = (day: string) => {
        const current = [...data.availability];
        const idx = current.indexOf(day);
        if (idx === -1) {
            current.push(day);
        } else {
            current.splice(idx, 1);
        }
        setData('availability', current);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('tutor.profile.update'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Tutor Profile
                    </h2>
                    <VerificationBadge status={profile.verification_status} />
                </div>
            }
        >
            <Head title="Edit Tutor Profile" />

            <div className="mx-auto max-w-3xl">
                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="p-6 space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">IC Number</label>
                                <input
                                    type="text"
                                    value={data.ic_number}
                                    onChange={(e) => setData('ic_number', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="e.g. 901234-56-7890"
                                />
                                {errors.ic_number && <p className="mt-1 text-sm text-red-600">{errors.ic_number}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Subjects</label>
                                {Object.entries(grouped).map(([category, items]) => (
                                    <div key={category} className="mb-4">
                                        <p className="text-sm font-semibold text-gray-600 mb-1">{category}</p>
                                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                            {items.map((s) => (
                                                <label key={s.id} className="flex items-center gap-2 text-sm">
                                                    <input
                                                        type="checkbox"
                                                        checked={data.subjects.includes(String(s.id))}
                                                        onChange={() => toggleSubject(String(s.id))}
                                                        className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                    />
                                                    {s.name}
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                                {errors.subjects && <p className="mt-1 text-sm text-red-600">{errors.subjects}</p>}
                            </div>

                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Education Level</label>
                                    <select
                                        value={data.education_level}
                                        onChange={(e) => setData('education_level', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Select level</option>
                                        {EDUCATION_LEVELS.map((l) => (
                                            <option key={l} value={l}>{l}</option>
                                        ))}
                                    </select>
                                    {errors.education_level && <p className="mt-1 text-sm text-red-600">{errors.education_level}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Experience (Years)</label>
                                    <input
                                        type="number"
                                        min={0}
                                        value={data.experience_years}
                                        onChange={(e) => setData('experience_years', Number(e.target.value))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    {errors.experience_years && <p className="mt-1 text-sm text-red-600">{errors.experience_years}</p>}
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Bio</label>
                                <textarea
                                    value={data.bio}
                                    onChange={(e) => setData('bio', e.target.value)}
                                    rows={4}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Tell parents about yourself..."
                                />
                                {errors.bio && <p className="mt-1 text-sm text-red-600">{errors.bio}</p>}
                            </div>

                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Hourly Rate (RM)</label>
                                    <input
                                        type="number"
                                        min={0}
                                        step={5}
                                        value={data.hourly_rate}
                                        onChange={(e) => setData('hourly_rate', Number(e.target.value))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    {errors.hourly_rate && <p className="mt-1 text-sm text-red-600">{errors.hourly_rate}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Area</label>
                                    <input
                                        type="text"
                                        value={data.location_area}
                                        onChange={(e) => setData('location_area', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="e.g. Petaling Jaya"
                                    />
                                    {errors.location_area && <p className="mt-1 text-sm text-red-600">{errors.location_area}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">State</label>
                                    <select
                                        value={data.location_state}
                                        onChange={(e) => setData('location_state', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Select state</option>
                                        {MALAYSIAN_STATES.map((s) => (
                                            <option key={s} value={s}>{s}</option>
                                        ))}
                                    </select>
                                    {errors.location_state && <p className="mt-1 text-sm text-red-600">{errors.location_state}</p>}
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Availability</label>
                                <div className="flex flex-wrap gap-3">
                                    {DAYS_OF_WEEK.map((day) => (
                                        <label key={day} className="flex items-center gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                checked={data.availability.includes(day)}
                                                onChange={() => toggleDay(day)}
                                                className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            {day}
                                        </label>
                                    ))}
                                </div>
                                {errors.availability && <p className="mt-1 text-sm text-red-600">{errors.availability}</p>}
                            </div>

                            <div className="border-t border-gray-200 pt-6">
                                <h3 className="text-base font-semibold text-gray-900">Where you teach</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Your address places you on the map. The travel radius is how far you are
                                    willing to go to a student's home — leave it blank if you would rather
                                    not travel, and students can still come to you or learn online.
                                </p>

                                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700">Address</label>
                                        <input
                                            type="text"
                                            value={data.address}
                                            onChange={(e) => setData('address', e.target.value)}
                                            placeholder="e.g. 8 Jalan SS2/24"
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.address && <p className="mt-1 text-sm text-red-600">{errors.address}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Postcode</label>
                                        <input
                                            type="text"
                                            value={data.postcode}
                                            onChange={(e) => onPostcode(e.target.value)}
                                            placeholder="e.g. 47300"
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.postcode && <p className="mt-1 text-sm text-red-600">{errors.postcode}</p>}
                                        {postcodeStatus === 'looking' && <p className="mt-1 text-xs text-gray-400">Looking up…</p>}
                                        {postcodeStatus === 'found' && <p className="mt-1 text-xs text-green-600">Area and state filled in for you.</p>}
                                        {postcodeStatus === 'unknown' && <p className="mt-1 text-xs text-amber-600">We do not recognise that postcode.</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Travel radius (km)</label>
                                        <input
                                            type="number"
                                            min={0}
                                            max={200}
                                            value={data.travel_radius_km}
                                            onChange={(e) => setData('travel_radius_km', e.target.value === '' ? '' : Number(e.target.value))}
                                            placeholder="e.g. 15"
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.travel_radius_km && <p className="mt-1 text-sm text-red-600">{errors.travel_radius_km}</p>}
                                    </div>

                                    <div className="sm:col-span-2">
                                        <LocationPicker
                                            latitude={data.latitude}
                                            longitude={data.longitude}
                                            onChange={(lat, lng) => setData((c: typeof data) => ({ ...c, latitude: lat, longitude: lng }))}
                                            subject="you"
                                            error={errors.latitude ?? errors.longitude}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="border-t border-gray-200 pt-6">
                                <h3 className="text-base font-semibold text-gray-900">Payout details</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Where your earnings are sent. All three fields are needed before you can be paid.
                                </p>

                                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Bank</label>
                                        <input
                                            type="text"
                                            value={data.bank_name}
                                            onChange={(e) => setData('bank_name', e.target.value)}
                                            placeholder="e.g. Maybank"
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.bank_name && <p className="mt-1 text-sm text-red-600">{errors.bank_name}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Account number</label>
                                        <input
                                            type="text"
                                            value={data.bank_account_number}
                                            onChange={(e) => setData('bank_account_number', e.target.value)}
                                            placeholder="Digits only"
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.bank_account_number && <p className="mt-1 text-sm text-red-600">{errors.bank_account_number}</p>}
                                    </div>

                                    <div className="sm:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700">Account holder name</label>
                                        <input
                                            type="text"
                                            value={data.bank_account_name}
                                            onChange={(e) => setData('bank_account_name', e.target.value)}
                                            placeholder="Exactly as it appears on your bank account"
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.bank_account_name && <p className="mt-1 text-sm text-red-600">{errors.bank_account_name}</p>}
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                >
                                    Save Profile
                                </button>
                            </div>
                        </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
