import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { usePostcodeLookup } from '@/hooks/usePostcodeLookup';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

type Centre = {
    id: number;
    name: string;
    address: string;
    area: string | null;
    state: string | null;
    postcode: string | null;
    capacity: number;
    is_active: boolean;
    owner_user_id: number | null;
    owner_name: string | null;
    latitude: number | null;
    longitude: number | null;
    is_placed: boolean;
};

type Props = {
    centres: Centre[];
    tutors: { id: number; name: string }[];
    geocodingDriver: string;
};

export default function CentresIndex({ centres, tutors, geocodingDriver }: Props) {
    const [editing, setEditing] = useState<Centre | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        address: '',
        area: '',
        state: '',
        postcode: '',
        capacity: 12,
        is_active: true,
        owner_user_id: '' as string | number,
        latitude: '' as string | number,
        longitude: '' as string | number,
    });

    const { lookup, status: postcodeStatus } = usePostcodeLookup();

    const onPostcode = (value: string) => {
        setData('postcode', value);
        lookup(value, ({ city, state }) =>
            setData((current: typeof data) => ({ ...current, area: city, state })));
    };

    const startEdit = (centre: Centre) => {
        setEditing(centre);
        setData({
            name: centre.name,
            address: centre.address,
            area: centre.area ?? '',
            state: centre.state ?? '',
            postcode: centre.postcode ?? '',
            capacity: centre.capacity,
            is_active: centre.is_active,
            owner_user_id: centre.owner_user_id ?? '',
            latitude: centre.latitude ?? '',
            longitude: centre.longitude ?? '',
        });
    };

    const cancel = () => {
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const done = { onSuccess: () => cancel() };
        editing ? put(`/admin/centres/${editing.id}`, done) : post('/admin/centres', done);
    };

    const remove = (centre: Centre) => {
        router.delete(`/admin/centres/${centre.id}`, { preserveScroll: true });
    };

    const unplaced = centres.filter((c) => !c.is_placed).length;

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Centres</h2>}>
            <Head title="Centres" />

            <div className="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
                {unplaced > 0 && (
                    <div className="rounded-lg bg-amber-50 p-4">
                        <p className="text-sm font-medium text-amber-800">
                            {unplaced} centre{unplaced > 1 ? 's have' : ' has'} no map position
                        </p>
                        <p className="mt-1 text-sm text-amber-700">
                            A centre without coordinates never appears in distance searches. Geocoding is set to
                            &ldquo;{geocodingDriver}&rdquo;
                            {geocodingDriver === 'manual'
                                ? ' — enter latitude and longitude directly, or switch the driver in Settings.'
                                : ' — run `php artisan geocode:backfill` to place existing centres.'}
                        </p>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <form onSubmit={submit} className="space-y-4 rounded-xl bg-white p-6 shadow-sm lg:col-span-1">
                        <h3 className="font-semibold text-gray-900">{editing ? `Edit ${editing.name}` : 'Add a centre'}</h3>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)}
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                            {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Address</label>
                            <input type="text" value={data.address} onChange={(e) => setData('address', e.target.value)}
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                            {errors.address && <p className="mt-1 text-sm text-red-600">{errors.address}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Area</label>
                                <input type="text" value={data.area} onChange={(e) => setData('area', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Postcode</label>
                                <input type="text" value={data.postcode} onChange={(e) => onPostcode(e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                {postcodeStatus === 'found' && <p className="mt-1 text-xs text-green-600">Area and state filled in.</p>}
                                {postcodeStatus === 'unknown' && <p className="mt-1 text-xs text-amber-600">Postcode not recognised.</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">State</label>
                            <input type="text" value={data.state} onChange={(e) => setData('state', e.target.value)}
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Capacity</label>
                                <input type="number" min={1} max={500} value={data.capacity}
                                    onChange={(e) => setData('capacity', Number(e.target.value))}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                {errors.capacity && <p className="mt-1 text-sm text-red-600">{errors.capacity}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Run by</label>
                                <select value={data.owner_user_id} onChange={(e) => setData('owner_user_id', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">The platform (HQ)</option>
                                    {tutors.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                                </select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Latitude</label>
                                <input type="number" step="any" value={data.latitude}
                                    onChange={(e) => setData('latitude', e.target.value)}
                                    placeholder="3.1390"
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                {errors.latitude && <p className="mt-1 text-sm text-red-600">{errors.latitude}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Longitude</label>
                                <input type="number" step="any" value={data.longitude}
                                    onChange={(e) => setData('longitude', e.target.value)}
                                    placeholder="101.6869"
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                {errors.longitude && <p className="mt-1 text-sm text-red-600">{errors.longitude}</p>}
                            </div>
                        </div>
                        <p className="text-xs text-gray-500">
                            Leave coordinates blank to let geocoding resolve the address. With the manual driver
                            they must be entered here.
                        </p>

                        <label className="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            Active
                        </label>

                        <div className="flex gap-2">
                            <button type="submit" disabled={processing}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50">
                                {editing ? 'Save changes' : 'Add centre'}
                            </button>
                            {editing && (
                                <button type="button" onClick={cancel}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                            )}
                        </div>
                    </form>

                    <div className="overflow-hidden rounded-xl bg-white shadow-sm lg:col-span-2">
                        <div className="border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">All centres ({centres.length})</h3>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        {['Name', 'Where', 'Run by', 'Seats', 'On map', ''].map((h) => (
                                            <th key={h} className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {centres.map((c) => (
                                        <tr key={c.id} className={c.is_active ? '' : 'opacity-50'}>
                                            <td className="px-6 py-3 text-sm font-medium text-gray-900">{c.name}</td>
                                            <td className="px-6 py-3 text-sm text-gray-500">{[c.area, c.state].filter(Boolean).join(', ') || c.address}</td>
                                            <td className="px-6 py-3 text-sm text-gray-500">{c.owner_name ?? 'Platform'}</td>
                                            <td className="px-6 py-3 text-sm text-gray-500">{c.capacity}</td>
                                            <td className="px-6 py-3 text-sm">
                                                {c.is_placed
                                                    ? <span className="inline-flex rounded-full bg-green-50 px-2 text-xs font-semibold text-green-700">placed</span>
                                                    : <span className="inline-flex rounded-full bg-amber-50 px-2 text-xs font-semibold text-amber-700">no position</span>}
                                            </td>
                                            <td className="px-6 py-3 text-right text-sm">
                                                <button onClick={() => startEdit(c)} className="text-indigo-600 hover:text-indigo-800">Edit</button>
                                                <button onClick={() => remove(c)} className="ml-3 text-red-600 hover:text-red-800">Remove</button>
                                            </td>
                                        </tr>
                                    ))}
                                    {centres.length === 0 && (
                                        <tr><td colSpan={6} className="px-6 py-8 text-center text-sm text-gray-400">No centres yet.</td></tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
