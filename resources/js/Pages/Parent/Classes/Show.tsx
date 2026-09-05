import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

type Props = {
    classSession: {
        id: number; title: string | null; tutor_name: string | null; subject_name: string | null;
        centre_name: string | null; is_online: boolean; schedule_day: string | null;
        schedule_time: string | null; total_sessions: number; seats_left: number;
        price: number; status: string;
    };
    myEnrolments: {
        id: number; student_name: string | null; status: string;
        payment_id: number | null; payment_status: string | null; amount: string | null;
    }[];
    students: { id: number; name: string }[];
};

export default function ParentClassShow({ classSession, myEnrolments, students }: Props) {
    const { data, setData, post, processing, errors } = useForm({ student_id: '' as string | number });

    const enrolled = new Set(myEnrolments.map((e) => e.student_name));
    const available = students.filter((s) => !enrolled.has(s.name));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/parent/classes/${classSession.id}/enrol`);
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">{classSession.title ?? 'Class'}</h2>}>
            <Head title={classSession.title ?? 'Class'} />

            <div className="mx-auto max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">
                <div className="rounded-xl bg-white p-6 shadow-sm">
                    <p className="text-sm text-gray-500">{classSession.subject_name} · {classSession.tutor_name}</p>
                    <p className="mt-1 text-sm text-gray-500">
                        {classSession.is_online ? 'Online' : classSession.centre_name ?? 'At a centre'}
                        {' · '}<span className="capitalize">{classSession.schedule_day}</span> {(classSession.schedule_time ?? '').slice(0, 5)}
                    </p>
                    <div className="mt-4 flex items-end justify-between">
                        <div>
                            <p className="text-xs text-gray-500">Total for {classSession.total_sessions} session{classSession.total_sessions > 1 ? 's' : ''}</p>
                            <p className="text-2xl font-bold text-gray-900">RM {Number(classSession.price).toFixed(2)}</p>
                        </div>
                        <p className={`text-sm ${classSession.seats_left === 0 ? 'text-red-600' : 'text-gray-500'}`}>
                            {classSession.seats_left === 0 ? 'Full' : `${classSession.seats_left} seat${classSession.seats_left > 1 ? 's' : ''} left`}
                        </p>
                    </div>
                </div>

                {myEnrolments.length > 0 && (
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="border-b px-6 py-4"><h3 className="font-semibold text-gray-900">Your seats</h3></div>
                        <div className="divide-y">
                            {myEnrolments.map((e) => (
                                <div key={e.id} className="flex items-center justify-between px-6 py-3">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{e.student_name}</p>
                                        <p className="text-xs text-gray-500 capitalize">{e.status}</p>
                                    </div>
                                    {e.payment_status === 'success'
                                        ? <span className="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700">Paid</span>
                                        : e.payment_id && (
                                            <Link href={`/parent/payments/${e.payment_id}`}
                                                className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                                                Pay RM {Number(e.amount ?? 0).toFixed(2)}
                                            </Link>
                                        )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {classSession.status === 'open' && classSession.seats_left > 0 && available.length > 0 && (
                    <form onSubmit={submit} className="rounded-xl bg-white p-6 shadow-sm">
                        <h3 className="font-semibold text-gray-900">Enrol a student</h3>
                        <p className="mt-1 text-sm text-gray-500">The seat is held once you enrol, and confirmed when you pay.</p>
                        <div className="mt-4 flex gap-3">
                            <select value={data.student_id} onChange={(e) => setData('student_id', e.target.value)} required
                                className="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Choose a student…</option>
                                {available.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                            <button type="submit" disabled={processing}
                                className="whitespace-nowrap rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                Hold a seat
                            </button>
                        </div>
                        {errors.student_id && <p className="mt-1 text-sm text-red-600">{errors.student_id}</p>}
                    </form>
                )}

                {classSession.seats_left === 0 && (
                    <div className="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">This class is full.</div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
