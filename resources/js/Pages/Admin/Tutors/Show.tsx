import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

type TutorWithProfile = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    tutor_profile: {
        ic_number: string | null;
        education_level: string | null;
        experience_years: number | null;
        bio: string | null;
        subjects: string[];
        hourly_rate: string;
        commission_rate: string;
        location_area: string | null;
        location_state: string | null;
        availability: string[] | null;
        verification_status: string;
    } | null;
};

type ReviewItem = {
    id: number;
    rating: number;
    comment: string | null;
    created_at: string;
    parent: { name: string };
    booking: { subject: { name: string } };
};

type Props = {
    tutor: TutorWithProfile;
    reviews: ReviewItem[];
};

function statusBadge(status: string) {
    switch (status) {
        case 'verified':
            return <span className="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Verified</span>;
        case 'rejected':
            return <span className="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">Rejected</span>;
        default:
            return <span className="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-800">Pending</span>;
    }
}

export default function TutorsShow({ tutor, reviews }: Props) {
    const profile = tutor.tutor_profile;

    const commissionForm = useForm({
        commission_rate: profile?.commission_rate ? Number(profile.commission_rate) : 20,
    });

    const handleApprove = () => {
        router.post(route('admin.tutors.verify', tutor.id), { status: 'verified' });
    };

    const handleReject = () => {
        if (confirm('Are you sure you want to reject this tutor?')) {
            router.post(route('admin.tutors.verify', tutor.id), { status: 'rejected' });
        }
    };

    const submitCommission = (e: React.FormEvent) => {
        e.preventDefault();
        commissionForm.post(route('admin.tutors.commission', tutor.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Tutor Details
                    </h2>
                    <Link
                        href={route('admin.tutors.index')}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title={`Tutor - ${tutor.name}`} />

            <div className="mx-auto max-w-4xl">
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Name</dt>
                                <dd className="mt-1 text-sm text-gray-900">{tutor.name}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Email</dt>
                                <dd className="mt-1 text-sm text-gray-900">{tutor.email}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Phone</dt>
                                <dd className="mt-1 flex items-center gap-2 text-sm text-gray-900">
                                    {tutor.phone ?? '-'}
                                    {tutor.phone && (
                                        <a
                                            href={`https://wa.me/6${tutor.phone.replace(/^0/, '')}`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 hover:bg-green-200"
                                        >
                                            <svg className="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.612.616l4.529-1.471A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.37 0-4.567-.818-6.295-2.187l-.44-.362-2.893.939.964-2.856-.378-.453A9.954 9.954 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
                                            WhatsApp
                                        </a>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">IC Number</dt>
                                <dd className="mt-1 text-sm text-gray-900">{profile?.ic_number ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Education</dt>
                                <dd className="mt-1 text-sm text-gray-900">{profile?.education_level ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Experience (Years)</dt>
                                <dd className="mt-1 text-sm text-gray-900">{profile?.experience_years ?? '-'}</dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Bio</dt>
                                <dd className="mt-1 text-sm text-gray-900">{profile?.bio ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Subjects</dt>
                                <dd className="mt-1 text-sm text-gray-900">{profile?.subjects?.join(', ') ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Hourly Rate (RM)</dt>
                                <dd className="mt-1 text-sm text-gray-900">{profile?.hourly_rate ? Number(profile.hourly_rate).toFixed(2) : '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Location</dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {[profile?.location_area, profile?.location_state].filter(Boolean).join(', ') || '-'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Availability</dt>
                                <dd className="mt-1 text-sm text-gray-900 capitalize">{profile?.availability?.join(', ') ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Verification Status</dt>
                                <dd className="mt-1">{statusBadge(profile?.verification_status ?? 'pending')}</dd>
                            </div>
                        </dl>
                    </div>

                    {/* Commission Rate */}
                    <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900">Commission Rate</h3>
                        <p className="mt-1 text-sm text-gray-600">Set the platform commission percentage for this tutor.</p>
                        <form onSubmit={submitCommission} className="mt-4 flex items-end gap-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Rate (%)</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.5"
                                    value={commissionForm.data.commission_rate}
                                    onChange={e => commissionForm.setData('commission_rate', parseFloat(e.target.value) || 0)}
                                    className="mt-1 block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={commissionForm.processing}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {commissionForm.processing ? 'Saving...' : 'Update'}
                            </button>
                        </form>
                    </div>

                        {/* Reviews */}
                    <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900">Reviews ({reviews.length})</h3>
                        {reviews.length === 0 ? (
                            <p className="mt-2 text-sm text-gray-500">No reviews yet.</p>
                        ) : (
                            <div className="mt-4 divide-y">
                                {reviews.map((review) => (
                                    <div key={review.id} className="py-4">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">{review.parent.name}</p>
                                                <p className="text-xs text-gray-500">{review.booking.subject.name}</p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <div className="flex gap-0.5">
                                                    {[1, 2, 3, 4, 5].map((star) => (
                                                        <svg key={star} className={`h-4 w-4 ${star <= review.rating ? 'text-yellow-400' : 'text-gray-300'}`} fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                        </svg>
                                                    ))}
                                                </div>
                                                <span className="text-xs text-gray-400">{new Date(review.created_at).toLocaleDateString()}</span>
                                            </div>
                                        </div>
                                        {review.comment && <p className="mt-2 text-sm text-gray-600">{review.comment}</p>}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                {profile?.verification_status === 'pending' && (
                        <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="text-lg font-medium text-gray-900">Verification</h3>
                            <p className="mt-1 text-sm text-gray-600">Review and approve or reject this tutor's profile.</p>
                            <div className="mt-4 flex gap-3">
                                <button
                                    onClick={handleApprove}
                                    className="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                >
                                    Approve
                                </button>
                                <button
                                    onClick={handleReject}
                                    className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                >
                                    Reject
                                </button>
                            </div>
                        </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
