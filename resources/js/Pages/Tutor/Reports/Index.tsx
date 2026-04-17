import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Report = {
    id: number;
    exam_type: string;
    term: string | null;
    score: string;
    total_marks: string;
    grade: string | null;
    exam_date: string | null;
    created_at: string;
    student: { id: number; name: string };
    subject: { id: number; name: string };
};

type Props = {
    reports: {
        data: Report[];
        links: any[];
    };
};

export default function ReportsIndex({ reports }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Student Reports</h2>
                    <Link
                        href={route('tutor.reports.create')}
                        className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        + Add Report
                    </Link>
                </div>
            }
        >
            <Head title="Student Reports" />

            <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Exam</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Score</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Grade</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {reports.data.map((r) => {
                            const pct = (Number(r.score) / Number(r.total_marks)) * 100;
                            return (
                                <tr key={r.id} className="hover:bg-gray-50">
                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{r.student.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{r.subject.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {r.exam_type}{r.term ? ` (${r.term})` : ''}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        <span className="font-semibold text-gray-900">{Number(r.score).toFixed(0)}</span>
                                        <span className="text-gray-400">/{Number(r.total_marks).toFixed(0)}</span>
                                        <span className={`ml-2 text-xs font-medium ${pct >= 80 ? 'text-green-600' : pct >= 50 ? 'text-amber-600' : 'text-red-600'}`}>
                                            ({pct.toFixed(0)}%)
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        {r.grade ? (
                                            <span className="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800">{r.grade}</span>
                                        ) : '-'}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{r.exam_date ?? '-'}</td>
                                </tr>
                            );
                        })}
                        {reports.data.length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-6 py-8 text-center text-sm text-gray-500">No reports yet. Add your first student report.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {reports.links.length > 3 && (
                <div className="mt-4 flex justify-center space-x-1">
                    {reports.links.map((link: any, i: number) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            className={`rounded px-3 py-1 text-sm ${
                                link.active ? 'bg-indigo-600 text-white' : link.url ? 'bg-white text-gray-700 hover:bg-gray-100' : 'cursor-not-allowed bg-gray-100 text-gray-400'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            preserveScroll
                        />
                    ))}
                </div>
            )}
        </AuthenticatedLayout>
    );
}
