import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

interface JobItem {
    id: number;
    parent: { name: string };
    student: { name: string };
    subject: { name: string };
    preferred_area: string;
    preferred_schedule: string | null;
    budget_min: string | null;
    budget_max: string | null;
    notes: string | null;
}

interface Props {
    jobs: JobItem[];
}

// Budgets arrive as decimal strings, so they are formatted rather than
// interpolated raw — otherwise a budget of 50.00 reads as "RM50.00".
function formatBudget(min: string | null, max: string | null): string {
    const rm = (v: string) => `RM${Number(v).toFixed(0)}`;
    if (min && max) return `${rm(min)} - ${rm(max)}`;
    if (min) return `From ${rm(min)}`;
    if (max) return `Up to ${rm(max)}`;
    return 'Not specified';
}

export default function Index({ jobs }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Job Offers
                </h2>
            }
        >
            <Head title="Job Offers" />

            <>
                {jobs.length === 0 ? (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6 text-center text-gray-500">
                                No job offers at the moment.
                            </div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {jobs.map((job) => (
                                <div key={job.id} className="overflow-hidden rounded-lg bg-white shadow-sm">
                                    <div className="p-6">
                                        <div className="mb-3">
                                            <span className="inline-block rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800">
                                                {job.subject.name}
                                            </span>
                                        </div>

                                        <div className="space-y-2 text-sm text-gray-600">
                                            <div className="flex justify-between">
                                                <span className="font-medium text-gray-700">Student</span>
                                                <span>{job.student.name}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="font-medium text-gray-700">Parent</span>
                                                <span>{job.parent.name}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="font-medium text-gray-700">Area</span>
                                                <span>{job.preferred_area}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="font-medium text-gray-700">Budget</span>
                                                <span>{formatBudget(job.budget_min, job.budget_max)}</span>
                                            </div>
                                            {job.preferred_schedule && (
                                                <div className="flex justify-between">
                                                    <span className="font-medium text-gray-700">Schedule</span>
                                                    <span>{job.preferred_schedule}</span>
                                                </div>
                                            )}
                                            {job.notes && (
                                                <div className="mt-2 border-t pt-2">
                                                    <p className="text-gray-500 line-clamp-2">{job.notes}</p>
                                                </div>
                                            )}
                                        </div>

                                        <div className="mt-4">
                                            <Link
                                                href={route('tutor.jobs.show', job.id)}
                                                className="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                            >
                                                View Details
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                )}
            </>
        </AuthenticatedLayout>
    );
}
