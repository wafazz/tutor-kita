import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Booking = {
    id: number;
    tutor: { name: string };
    student: { name: string };
    subject: { name: string };
    schedule_day: string;
    schedule_time: string;
};

export default function ReviewCreate({ booking }: { booking: Booking }) {
    const { data, setData, post, processing, errors } = useForm({
        rating: 0,
        comment: '',
    });
    const [hoverRating, setHoverRating] = useState(0);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('parent.reviews.store', booking.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Leave a Review</h2>
                    <Link href={route('parent.bookings.show', booking.id)} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title="Leave Review" />

            <div className="mx-auto max-w-2xl">
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <div className="mb-6 rounded-lg bg-gray-50 p-4">
                        <p className="text-sm text-gray-600">Reviewing booking with</p>
                        <p className="mt-1 text-lg font-semibold text-gray-900">{booking.tutor.name}</p>
                        <p className="text-sm text-gray-500">
                            {booking.subject.name} for {booking.student.name} &middot; {booking.schedule_day} at {booking.schedule_time}
                        </p>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Rating</label>
                            <div className="mt-2 flex gap-1">
                                {[1, 2, 3, 4, 5].map((star) => (
                                    <button
                                        key={star}
                                        type="button"
                                        onMouseEnter={() => setHoverRating(star)}
                                        onMouseLeave={() => setHoverRating(0)}
                                        onClick={() => setData('rating', star)}
                                        className="focus:outline-none"
                                    >
                                        <svg
                                            className={`h-10 w-10 transition-colors ${
                                                star <= (hoverRating || data.rating)
                                                    ? 'text-yellow-400'
                                                    : 'text-gray-300'
                                            }`}
                                            fill="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </button>
                                ))}
                            </div>
                            {data.rating > 0 && (
                                <p className="mt-1 text-sm text-gray-500">
                                    {['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'][data.rating]}
                                </p>
                            )}
                            {errors.rating && <p className="mt-1 text-sm text-red-600">{errors.rating}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Comment (Optional)</label>
                            <textarea
                                value={data.comment}
                                onChange={(e) => setData('comment', e.target.value)}
                                rows={4}
                                maxLength={1000}
                                placeholder="Share your experience with this tutor..."
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                            />
                            <p className="mt-1 text-xs text-gray-400">{data.comment.length}/1000</p>
                            {errors.comment && <p className="mt-1 text-sm text-red-600">{errors.comment}</p>}
                        </div>

                        <div className="flex justify-end gap-3">
                            <Link
                                href={route('parent.bookings.show', booking.id)}
                                className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing || data.rating === 0}
                                className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {processing ? 'Submitting...' : 'Submit Review'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
