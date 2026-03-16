import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Booking = {
    id: number;
    student: { id: number; name: string };
    subject: { id: number; name: string };
};

export default function ReportsCreate({ bookings }: { bookings: Booking[] }) {
    const { data, setData, post, processing, errors } = useForm({
        booking_id: '',
        exam_type: '',
        term: '',
        score: '',
        total_marks: '100',
        grade: '',
        exam_date: '',
        remarks: '',
        strengths: '',
        improvements: '',
    });

    const selectedBooking = bookings.find((b) => String(b.id) === data.booking_id);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tutor.reports.store'));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Add Student Report</h2>}
        >
            <Head title="Add Report" />

            <div className="mx-auto max-w-2xl">
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                Student & Subject <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={data.booking_id}
                                onChange={(e) => setData('booking_id', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="">Select booking</option>
                                {bookings.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.student.name} — {b.subject.name}
                                    </option>
                                ))}
                            </select>
                            {errors.booking_id && <p className="mt-1 text-sm text-red-600">{errors.booking_id}</p>}
                        </div>

                        {selectedBooking && (
                            <div className="rounded-lg border border-indigo-200 bg-indigo-50 p-3">
                                <p className="text-sm text-indigo-800">
                                    <span className="font-medium">Student:</span> {selectedBooking.student.name} &bull;{' '}
                                    <span className="font-medium">Subject:</span> {selectedBooking.subject.name}
                                </p>
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Exam Type <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.exam_type}
                                    onChange={(e) => setData('exam_type', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select type</option>
                                    <option value="Quiz">Quiz</option>
                                    <option value="Monthly Test">Monthly Test</option>
                                    <option value="Mid-term">Mid-term</option>
                                    <option value="Final Exam">Final Exam</option>
                                    <option value="Assessment">Assessment</option>
                                    <option value="Trial Exam">Trial Exam</option>
                                </select>
                                {errors.exam_type && <p className="mt-1 text-sm text-red-600">{errors.exam_type}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Term</label>
                                <select
                                    value={data.term}
                                    onChange={(e) => setData('term', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select term</option>
                                    <option value="Term 1">Term 1</option>
                                    <option value="Term 2">Term 2</option>
                                    <option value="Term 3">Term 3</option>
                                    <option value="Semester 1">Semester 1</option>
                                    <option value="Semester 2">Semester 2</option>
                                </select>
                                {errors.term && <p className="mt-1 text-sm text-red-600">{errors.term}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Score <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.5"
                                    value={data.score}
                                    onChange={(e) => setData('score', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="85"
                                    required
                                />
                                {errors.score && <p className="mt-1 text-sm text-red-600">{errors.score}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Total Marks <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    step="0.5"
                                    value={data.total_marks}
                                    onChange={(e) => setData('total_marks', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="100"
                                    required
                                />
                                {errors.total_marks && <p className="mt-1 text-sm text-red-600">{errors.total_marks}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Grade</label>
                                <input
                                    type="text"
                                    value={data.grade}
                                    onChange={(e) => setData('grade', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="A+"
                                />
                                {errors.grade && <p className="mt-1 text-sm text-red-600">{errors.grade}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Exam Date</label>
                            <input
                                type="date"
                                value={data.exam_date}
                                onChange={(e) => setData('exam_date', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            {errors.exam_date && <p className="mt-1 text-sm text-red-600">{errors.exam_date}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Strengths</label>
                            <textarea
                                value={data.strengths}
                                onChange={(e) => setData('strengths', e.target.value)}
                                rows={2}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="What the student did well..."
                            />
                            {errors.strengths && <p className="mt-1 text-sm text-red-600">{errors.strengths}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Areas for Improvement</label>
                            <textarea
                                value={data.improvements}
                                onChange={(e) => setData('improvements', e.target.value)}
                                rows={2}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="What needs more work..."
                            />
                            {errors.improvements && <p className="mt-1 text-sm text-red-600">{errors.improvements}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">General Remarks</label>
                            <textarea
                                value={data.remarks}
                                onChange={(e) => setData('remarks', e.target.value)}
                                rows={2}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Overall comments..."
                            />
                            {errors.remarks && <p className="mt-1 text-sm text-red-600">{errors.remarks}</p>}
                        </div>

                        <div className="flex items-center gap-4">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Submit Report
                            </button>
                            <Link href={route('tutor.reports.index')} className="text-sm text-gray-600 hover:text-gray-900">
                                Cancel
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
