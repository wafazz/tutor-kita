import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

type ReviewItem = {
    id: number;
    rating: number;
    comment: string | null;
    created_at: string;
    parent: { name: string };
    booking: { subject: { name: string }; student: { name: string } };
};

type Stats = {
    totalReviews: number;
    averageRating: number;
    fiveStars: number;
    fourStars: number;
    threeStars: number;
    twoStars: number;
    oneStar: number;
};

type Props = {
    reviews: { data: ReviewItem[]; links: any[]; current_page: number; last_page: number };
    stats: Stats;
};

function StarDisplay({ rating, size = 'h-5 w-5' }: { rating: number; size?: string }) {
    return (
        <div className="flex gap-0.5">
            {[1, 2, 3, 4, 5].map((star) => (
                <svg
                    key={star}
                    className={`${size} ${star <= rating ? 'text-yellow-400' : 'text-gray-300'}`}
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
            ))}
        </div>
    );
}

function RatingBar({ label, count, total }: { label: string; count: number; total: number }) {
    const pct = total > 0 ? (count / total) * 100 : 0;
    return (
        <div className="flex items-center gap-2 text-sm">
            <span className="w-12 text-right text-gray-600">{label}</span>
            <div className="h-2 flex-1 overflow-hidden rounded-full bg-gray-200">
                <div className="h-full rounded-full bg-yellow-400" style={{ width: `${pct}%` }} />
            </div>
            <span className="w-8 text-gray-500">{count}</span>
        </div>
    );
}

export default function TutorReviews({ reviews, stats }: Props) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">My Reviews</h2>}
        >
            <Head title="My Reviews" />

            <div className="mx-auto max-w-4xl space-y-6">
                {/* Stats */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div className="flex items-center gap-6 overflow-hidden rounded-xl bg-white p-6 shadow-sm">
                        <div className="text-center">
                            <p className="text-4xl font-bold text-gray-900">
                                {Number(stats.averageRating).toFixed(1)}
                            </p>
                            <StarDisplay rating={Math.round(Number(stats.averageRating))} />
                            <p className="mt-1 text-sm text-gray-500">{stats.totalReviews} review{stats.totalReviews !== 1 ? 's' : ''}</p>
                        </div>
                    </div>
                    <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm">
                        <div className="space-y-2">
                            <RatingBar label="5 star" count={stats.fiveStars} total={stats.totalReviews} />
                            <RatingBar label="4 star" count={stats.fourStars} total={stats.totalReviews} />
                            <RatingBar label="3 star" count={stats.threeStars} total={stats.totalReviews} />
                            <RatingBar label="2 star" count={stats.twoStars} total={stats.totalReviews} />
                            <RatingBar label="1 star" count={stats.oneStar} total={stats.totalReviews} />
                        </div>
                    </div>
                </div>

                {/* Reviews list */}
                <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                    <div className="border-b px-6 py-4">
                        <h3 className="font-semibold text-gray-900">All Reviews</h3>
                    </div>
                    <div className="divide-y">
                        {reviews.data.length === 0 && (
                            <p className="p-6 text-center text-sm text-gray-400">No reviews yet.</p>
                        )}
                        {reviews.data.map((review) => (
                            <div key={review.id} className="p-6">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="font-medium text-gray-900">{review.parent.name}</p>
                                        <p className="text-sm text-gray-500">
                                            {review.booking.subject.name} for {review.booking.student.name}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <StarDisplay rating={review.rating} size="h-4 w-4" />
                                        <p className="mt-1 text-xs text-gray-400">
                                            {new Date(review.created_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                </div>
                                {review.comment && (
                                    <p className="mt-3 text-sm text-gray-700">{review.comment}</p>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
