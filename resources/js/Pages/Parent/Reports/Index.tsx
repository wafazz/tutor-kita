import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Student = { id: number; name: string };
type Report = {
    id: number;
    exam_type: string;
    term: string | null;
    score: string;
    total_marks: string;
    grade: string | null;
    exam_date: string | null;
    created_at: string;
    student: Student;
    subject: { name: string };
    tutor: { name: string };
};

type Props = {
    reports: { data: Report[]; links: any[] };
    students: Student[];
};

export default function ReportsIndex({ reports, students }: Props) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Report Cards & Performance</h2>}
        >
            <Head title="Reports" />

            <>
                {/* Student cards with link to detailed report */}
                <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {students.map((s) => {
                        const studentReports = reports.data.filter((r) => r.student.id === s.id);
                        const avgPct = studentReports.length > 0
                            ? studentReports.reduce((sum, r) => sum + (Number(r.score) / Number(r.total_marks)) * 100, 0) / studentReports.length
                            : 0;
                        return (
                            <Link
                                key={s.id}
                                href={route('parent.reports.show', s.id)}
                                className="rounded-xl border border-gray-200 bg-white p-5 transition hover:border-indigo-300 hover:shadow-md"
                            >
                                <div className="flex items-center justify-between">
                                    <h3 className="text-base font-semibold text-gray-900">{s.name}</h3>
                                    <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </div>
                                <div className="mt-3 flex items-center gap-4">
                                    <div>
                                        <p className="text-2xl font-bold text-indigo-600">{studentReports.length}</p>
                                        <p className="text-xs text-gray-500">Reports</p>
                                    </div>
                                    {studentReports.length > 0 && (
                                        <div>
                                            <p className={`text-2xl font-bold ${avgPct >= 80 ? 'text-green-600' : avgPct >= 50 ? 'text-amber-600' : 'text-red-600'}`}>
                                                {avgPct.toFixed(0)}%
                                            </p>
                                            <p className="text-xs text-gray-500">Avg Score</p>
                                        </div>
                                    )}
                                </div>
                            </Link>
                        );
                    })}
                    {students.length === 0 && (
                        <div className="col-span-full rounded-lg bg-white p-8 text-center text-sm text-gray-500">
                            No children added yet.
                        </div>
                    )}
                </div>

                {/* Recent reports table */}
                <h3 className="mb-3 text-lg font-semibold text-gray-800">Recent Reports</h3>
                <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Exam</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Score</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Grade</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                            {reports.data.map((r) => {
                                const pct = (Number(r.score) / Number(r.total_marks)) * 100;
                                return (
                                    <tr key={r.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{r.student.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{r.subject.name}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{r.exam_type}{r.term ? ` (${r.term})` : ''}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            <span className="font-semibold">{Number(r.score).toFixed(0)}</span>
                                            <span className="text-gray-400">/{Number(r.total_marks).toFixed(0)}</span>
                                            <span className={`ml-2 text-xs font-medium ${pct >= 80 ? 'text-green-600' : pct >= 50 ? 'text-amber-600' : 'text-red-600'}`}>
                                                ({pct.toFixed(0)}%)
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            {r.grade ? <span className="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800">{r.grade}</span> : '-'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{r.tutor.name}</td>
                                    </tr>
                                );
                            })}
                            {reports.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-6 py-8 text-center text-sm text-gray-500">No reports yet. Reports will appear here once your tutor adds them.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </>
        </AuthenticatedLayout>
    );
}
