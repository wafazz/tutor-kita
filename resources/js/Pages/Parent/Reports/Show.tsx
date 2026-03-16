import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type ReportItem = {
    id: number;
    exam_type: string;
    term: string | null;
    score: string;
    total_marks: string;
    percentage: number;
    grade: string | null;
    exam_date: string | null;
    remarks: string | null;
    strengths: string | null;
    improvements: string | null;
    tutor: string;
    created_at: string;
};

type SubjectGroup = {
    subject: string;
    reports: ReportItem[];
};

type Props = {
    student: { id: number; name: string; school: string | null; education_level: string | null };
    bySubject: SubjectGroup[];
};

function PercentageBar({ value }: { value: number }) {
    const color = value >= 80 ? 'bg-green-500' : value >= 60 ? 'bg-amber-500' : value >= 40 ? 'bg-orange-500' : 'bg-red-500';
    return (
        <div className="flex items-center gap-2">
            <div className="h-2.5 w-full max-w-[120px] rounded-full bg-gray-200">
                <div className={`h-2.5 rounded-full ${color}`} style={{ width: `${Math.min(value, 100)}%` }} />
            </div>
            <span className={`text-xs font-semibold ${value >= 80 ? 'text-green-600' : value >= 60 ? 'text-amber-600' : value >= 40 ? 'text-orange-600' : 'text-red-600'}`}>
                {value.toFixed(0)}%
            </span>
        </div>
    );
}

export default function ReportsShow({ student, bySubject }: Props) {
    const allReports = bySubject.flatMap((s) => s.reports);
    const overallAvg = allReports.length > 0
        ? allReports.reduce((sum, r) => sum + r.percentage, 0) / allReports.length
        : 0;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3">
                    <Link href={route('parent.reports.index')} className="text-gray-400 hover:text-gray-600">
                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </Link>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">{student.name}'s Report Card</h2>
                </div>
            }
        >
            <Head title={`${student.name} - Reports`} />

            <>
                {/* Overview cards */}
                <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div className="rounded-xl bg-white p-5 shadow-sm">
                        <p className="text-xs font-medium text-gray-500">Total Reports</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">{allReports.length}</p>
                    </div>
                    <div className="rounded-xl bg-white p-5 shadow-sm">
                        <p className="text-xs font-medium text-gray-500">Subjects</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">{bySubject.length}</p>
                    </div>
                    <div className="rounded-xl bg-white p-5 shadow-sm">
                        <p className="text-xs font-medium text-gray-500">Overall Average</p>
                        <p className={`mt-1 text-2xl font-bold ${overallAvg >= 80 ? 'text-green-600' : overallAvg >= 50 ? 'text-amber-600' : 'text-red-600'}`}>
                            {overallAvg.toFixed(0)}%
                        </p>
                    </div>
                    <div className="rounded-xl bg-white p-5 shadow-sm">
                        <p className="text-xs font-medium text-gray-500">Best Score</p>
                        <p className="mt-1 text-2xl font-bold text-indigo-600">
                            {allReports.length > 0 ? Math.max(...allReports.map((r) => r.percentage)).toFixed(0) + '%' : '-'}
                        </p>
                    </div>
                </div>

                {/* Subject averages visual */}
                {bySubject.length > 0 && (
                    <div className="mb-8 rounded-xl bg-white p-6 shadow-sm">
                        <h3 className="mb-4 text-base font-semibold text-gray-900">Performance by Subject</h3>
                        <div className="space-y-3">
                            {bySubject.map((sg) => {
                                const avg = sg.reports.reduce((s, r) => s + r.percentage, 0) / sg.reports.length;
                                return (
                                    <div key={sg.subject} className="flex items-center gap-4">
                                        <span className="w-32 shrink-0 text-sm font-medium text-gray-700">{sg.subject}</span>
                                        <div className="flex-1">
                                            <div className="h-6 w-full rounded-full bg-gray-100">
                                                <div
                                                    className={`h-6 rounded-full ${avg >= 80 ? 'bg-green-500' : avg >= 60 ? 'bg-amber-500' : avg >= 40 ? 'bg-orange-500' : 'bg-red-500'}`}
                                                    style={{ width: `${Math.min(avg, 100)}%` }}
                                                >
                                                    <span className="flex h-full items-center justify-end pr-2 text-xs font-bold text-white">
                                                        {avg.toFixed(0)}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <span className="w-16 text-right text-xs text-gray-500">{sg.reports.length} test{sg.reports.length > 1 ? 's' : ''}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Detailed reports per subject */}
                {bySubject.map((sg) => (
                    <div key={sg.subject} className="mb-6">
                        <h3 className="mb-3 text-base font-semibold text-gray-800">{sg.subject}</h3>
                        <div className="space-y-3">
                            {sg.reports.map((r) => (
                                <div key={r.id} className="rounded-lg border border-gray-200 bg-white p-5">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <p className="text-sm font-semibold text-gray-900">
                                                {r.exam_type}{r.term ? ` — ${r.term}` : ''}
                                            </p>
                                            <p className="mt-0.5 text-xs text-gray-500">
                                                {r.exam_date ?? r.created_at} &bull; by {r.tutor}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-lg font-bold text-gray-900">
                                                {Number(r.score).toFixed(0)}<span className="text-sm text-gray-400">/{Number(r.total_marks).toFixed(0)}</span>
                                            </p>
                                            {r.grade && (
                                                <span className="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">{r.grade}</span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="mt-3">
                                        <PercentageBar value={r.percentage} />
                                    </div>
                                    {(r.strengths || r.improvements || r.remarks) && (
                                        <div className="mt-3 space-y-2 border-t border-gray-100 pt-3">
                                            {r.strengths && (
                                                <div>
                                                    <p className="text-xs font-semibold text-green-700">Strengths</p>
                                                    <p className="text-sm text-gray-600">{r.strengths}</p>
                                                </div>
                                            )}
                                            {r.improvements && (
                                                <div>
                                                    <p className="text-xs font-semibold text-amber-700">Areas for Improvement</p>
                                                    <p className="text-sm text-gray-600">{r.improvements}</p>
                                                </div>
                                            )}
                                            {r.remarks && (
                                                <div>
                                                    <p className="text-xs font-semibold text-gray-700">Remarks</p>
                                                    <p className="text-sm text-gray-600">{r.remarks}</p>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                ))}

                {bySubject.length === 0 && (
                    <div className="rounded-lg bg-white p-8 text-center text-sm text-gray-500">
                        No reports yet for {student.name}. Reports will appear here once the tutor adds them.
                    </div>
                )}
            </>
        </AuthenticatedLayout>
    );
}
