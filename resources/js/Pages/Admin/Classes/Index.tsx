import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

type ClassRow = {
    id: number; title: string | null; tutor_id: number; tutor_name: string | null;
    subject_id: number; subject_name: string | null; centre_id: number | null; centre_name: string | null;
    delivery_mode: string; schedule_day: string | null; schedule_time: string | null;
    duration_hours: string; total_sessions: number; capacity: number; price_per_student: string;
    payout_model: string; payout_base: string | null; payout_per_head: string | null;
    payout_head_threshold: number | null; status: string;
    seats_taken: number; seats_left: number; revenue: number; tutor_payout: number;
    platform_share: number; is_underwater: boolean;
};

type Option = { value: string; label: string };

type Props = {
    classes: ClassRow[];
    tutors: { id: number; name: string }[];
    subjects: { id: number; name: string }[];
    centres: { id: number; name: string; capacity: number }[];
    payoutModels: Option[];
    deliveryModes: Option[];
};

export default function AdminClasses({ classes, tutors, subjects, centres, payoutModels, deliveryModes }: Props) {
    const [editing, setEditing] = useState<ClassRow | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        tutor_id: '' as string | number, subject_id: '' as string | number, centre_id: '' as string | number,
        delivery_mode: 'centre_group', title: '', schedule_day: 'saturday', schedule_time: '10:00',
        duration_hours: 1.5, total_sessions: 1, capacity: 8, price_per_student: 30,
        payout_model: 'per_student', payout_base: '' as string | number,
        payout_per_head: '' as string | number, payout_head_threshold: '' as string | number,
        status: 'draft',
    });

    const startEdit = (c: ClassRow) => {
        setEditing(c);
        setData({
            tutor_id: c.tutor_id, subject_id: c.subject_id, centre_id: c.centre_id ?? '',
            delivery_mode: c.delivery_mode, title: c.title ?? '',
            schedule_day: c.schedule_day ?? 'saturday', schedule_time: (c.schedule_time ?? '10:00').slice(0, 5),
            duration_hours: Number(c.duration_hours), total_sessions: c.total_sessions,
            capacity: c.capacity, price_per_student: Number(c.price_per_student),
            payout_model: c.payout_model, payout_base: c.payout_base ?? '',
            payout_per_head: c.payout_per_head ?? '', payout_head_threshold: c.payout_head_threshold ?? '',
            status: c.status,
        });
    };

    const cancel = () => { setEditing(null); reset(); };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const done = { onSuccess: () => cancel() };
        editing ? put(`/admin/classes/${editing.id}`, done) : post('/admin/classes', done);
    };

    const isOnline = data.delivery_mode === 'online_group';
    const needsBase = data.payout_model !== 'per_student';
    const needsHead = data.payout_model === 'flat_plus_head';

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Group classes</h2>}>
            <Head title="Group classes" />

            <div className="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
                <div className="grid gap-6 lg:grid-cols-3">
                    <form onSubmit={submit} className="space-y-4 rounded-xl bg-white p-6 shadow-sm lg:col-span-1">
                        <h3 className="font-semibold text-gray-900">{editing ? `Edit class #${editing.id}` : 'Create a class'}</h3>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" value={data.title} onChange={(e) => setData('title', e.target.value)}
                                placeholder="Saturday SPM Maths"
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Tutor</label>
                                <select value={data.tutor_id} onChange={(e) => setData('tutor_id', e.target.value)} required
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Choose…</option>
                                    {tutors.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                                </select>
                                {errors.tutor_id && <p className="mt-1 text-sm text-red-600">{errors.tutor_id}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Subject</label>
                                <select value={data.subject_id} onChange={(e) => setData('subject_id', e.target.value)} required
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Choose…</option>
                                    {subjects.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                                {errors.subject_id && <p className="mt-1 text-sm text-red-600">{errors.subject_id}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Where</label>
                            <select value={data.delivery_mode} onChange={(e) => setData('delivery_mode', e.target.value)}
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                {deliveryModes.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                            </select>
                        </div>

                        {!isOnline && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Centre</label>
                                <select value={data.centre_id} onChange={(e) => setData('centre_id', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Not set</option>
                                    {centres.map((c) => <option key={c.id} value={c.id}>{c.name} ({c.capacity} seats)</option>)}
                                </select>
                                <p className="mt-1 text-xs text-gray-500">A centre smaller than the class caps the seats.</p>
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Day</label>
                                <select value={data.schedule_day} onChange={(e) => setData('schedule_day', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    {['monday','tuesday','wednesday','thursday','friday','saturday','sunday'].map((d) =>
                                        <option key={d} value={d}>{d[0].toUpperCase() + d.slice(1)}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Time</label>
                                <input type="time" value={data.schedule_time} onChange={(e) => setData('schedule_time', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                {errors.schedule_time && <p className="mt-1 text-sm text-red-600">{errors.schedule_time}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Hours</label>
                                <input type="number" step={0.5} min={0.5} max={8} value={data.duration_hours}
                                    onChange={(e) => setData('duration_hours', Number(e.target.value))}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Sessions</label>
                                <input type="number" min={1} value={data.total_sessions}
                                    onChange={(e) => setData('total_sessions', Number(e.target.value))}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Seats</label>
                                <input type="number" min={1} value={data.capacity}
                                    onChange={(e) => setData('capacity', Number(e.target.value))}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Price per student (RM, per session)</label>
                            <input type="number" step={0.01} min={0.01} value={data.price_per_student}
                                onChange={(e) => setData('price_per_student', Number(e.target.value))}
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                            {errors.price_per_student && <p className="mt-1 text-sm text-red-600">{errors.price_per_student}</p>}
                        </div>

                        <div className="rounded-lg bg-gray-50 p-3">
                            <label className="block text-sm font-medium text-gray-700">How the tutor is paid</label>
                            <select value={data.payout_model} onChange={(e) => setData('payout_model', e.target.value)}
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                {payoutModels.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                            </select>

                            {needsBase && (
                                <div className="mt-3 grid grid-cols-3 gap-2">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-600">Base (RM)</label>
                                        <input type="number" step={0.01} min={0} value={data.payout_base}
                                            onChange={(e) => setData('payout_base', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm" />
                                        {errors.payout_base && <p className="mt-1 text-xs text-red-600">{errors.payout_base}</p>}
                                    </div>
                                    {needsHead && (
                                        <>
                                            <div>
                                                <label className="block text-xs font-medium text-gray-600">Per head</label>
                                                <input type="number" step={0.01} min={0} value={data.payout_per_head}
                                                    onChange={(e) => setData('payout_per_head', e.target.value)}
                                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm" />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-gray-600">After</label>
                                                <input type="number" min={0} value={data.payout_head_threshold}
                                                    onChange={(e) => setData('payout_head_threshold', e.target.value)}
                                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm" />
                                            </div>
                                        </>
                                    )}
                                </div>
                            )}
                            <p className="mt-2 text-xs text-gray-500">
                                {data.payout_model === 'per_student' && 'The tutor keeps their commission share of what each student pays.'}
                                {data.payout_model === 'flat' && 'The tutor is paid the same whatever the headcount; the platform keeps the rest.'}
                                {data.payout_model === 'flat_plus_head' && 'A guaranteed floor, plus the per-head amount for each student past the threshold.'}
                            </p>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Status</label>
                            <select value={data.status} onChange={(e) => setData('status', e.target.value)}
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                {['draft','open','closed','completed','cancelled'].map((s) =>
                                    <option key={s} value={s}>{s[0].toUpperCase() + s.slice(1)}</option>)}
                            </select>
                            <p className="mt-1 text-xs text-gray-500">Only an open class can be enrolled in.</p>
                        </div>

                        <div className="flex gap-2">
                            <button type="submit" disabled={processing}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50">
                                {editing ? 'Save changes' : 'Create class'}
                            </button>
                            {editing && <button type="button" onClick={cancel}
                                className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>}
                        </div>
                    </form>

                    <div className="space-y-4 lg:col-span-2">
                        {classes.map((c) => (
                            <div key={c.id} className="rounded-xl bg-white p-5 shadow-sm">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="font-semibold text-gray-900">{c.title ?? `Class #${c.id}`}</p>
                                        <p className="text-sm text-gray-500">
                                            {c.subject_name} · {c.tutor_name} · {c.centre_name ?? (c.delivery_mode === 'online_group' ? 'Online' : 'No centre')}
                                        </p>
                                        <p className="mt-1 text-xs text-gray-400">
                                            {c.schedule_day} {(c.schedule_time ?? '').slice(0, 5)} · {c.total_sessions} session{c.total_sessions > 1 ? 's' : ''} · RM {Number(c.price_per_student).toFixed(2)}/student
                                        </p>
                                    </div>
                                    <span className="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold capitalize text-gray-700">{c.status}</span>
                                </div>

                                {c.is_underwater && (
                                    <div className="mt-3 rounded-lg bg-red-50 p-3">
                                        <p className="text-sm font-medium text-red-800">Paying out more than this class collects</p>
                                        <p className="mt-1 text-sm text-red-700">
                                            The tutor is owed RM {c.tutor_payout.toFixed(2)} but the students are only paying
                                            RM {c.revenue.toFixed(2)}. Fill more seats, or lower the fixed payout.
                                        </p>
                                    </div>
                                )}

                                <div className="mt-3 grid grid-cols-4 gap-3 text-sm">
                                    <div><p className="text-xs text-gray-500">Seats</p><p className="font-medium text-gray-900">{c.seats_taken} / {c.seats_taken + c.seats_left}</p></div>
                                    <div><p className="text-xs text-gray-500">Collected</p><p className="font-medium text-gray-900">RM {c.revenue.toFixed(2)}</p></div>
                                    <div><p className="text-xs text-gray-500">Tutor</p><p className="font-medium text-gray-900">RM {c.tutor_payout.toFixed(2)}</p></div>
                                    <div><p className="text-xs text-gray-500">Platform</p><p className={`font-medium ${c.platform_share < 0 ? 'text-red-600' : 'text-gray-900'}`}>RM {c.platform_share.toFixed(2)}</p></div>
                                </div>

                                <div className="mt-3 flex gap-3 text-sm">
                                    <button onClick={() => startEdit(c)} className="text-indigo-600 hover:text-indigo-800">Edit</button>
                                    {c.seats_taken === 0 && (
                                        <button onClick={() => router.delete(`/admin/classes/${c.id}`, { preserveScroll: true })}
                                            className="text-red-600 hover:text-red-800">Remove</button>
                                    )}
                                </div>
                            </div>
                        ))}
                        {classes.length === 0 && (
                            <div className="rounded-xl bg-white p-8 text-center text-sm text-gray-400 shadow-sm">No classes yet.</div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
