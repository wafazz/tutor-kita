import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

type Tutor = {
    id: number;
    name: string;
    email: string;
    unpaid_amount: string;
};

type Props = { tutors: Tutor[] };

export default function PayoutCreate({ tutors }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        tutor_id: '',
        period_start: '',
        period_end: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.payouts.store'));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Create Payout</h2>}
        >
            <Head title="Create Payout" />

            <>
                <div className="mb-4">
                    <Link href={route('admin.payouts.index')} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>

                <div className="mx-auto max-w-2xl">
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="font-semibold text-gray-900">Generate Tutor Payout</h3>
                        </div>
                        <form onSubmit={handleSubmit} className="space-y-4 px-6 py-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Tutor</label>
                                <select
                                    value={data.tutor_id}
                                    onChange={(e) => setData('tutor_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="">Select tutor...</option>
                                    {tutors.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.name} ({t.email})
                                        </option>
                                    ))}
                                </select>
                                {errors.tutor_id && <p className="mt-1 text-sm text-red-600">{errors.tutor_id}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Period Start</label>
                                    <input
                                        type="date"
                                        value={data.period_start}
                                        onChange={(e) => setData('period_start', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.period_start && <p className="mt-1 text-sm text-red-600">{errors.period_start}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Period End</label>
                                    <input
                                        type="date"
                                        value={data.period_end}
                                        onChange={(e) => setData('period_end', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.period_end && <p className="mt-1 text-sm text-red-600">{errors.period_end}</p>}
                                </div>
                            </div>

                            <div className="flex justify-end pt-4">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Generate Payout
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </>
        </AuthenticatedLayout>
    );
}
