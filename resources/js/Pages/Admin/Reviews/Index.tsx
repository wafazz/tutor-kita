import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

type ReviewItem = {
    id: number;
    rating: number;
    comment: string | null;
    created_at: string;
    parent: { name: string };
    tutor: { name: string };
    booking: { subject: { name: string } };
};

type Props = {
    reviews: { data: ReviewItem[]; links: any[]; current_page: number; last_page: number };
};

function StarDisplay({ rating }: { rating: number }) {
    return (
        <div className="flex gap-0.5">
            {[1, 2, 3, 4, 5].map((star) => (
                <svg
                    key={star}
                    className={`h-4 w-4 ${star <= rating ? 'text-yellow-400' : 'text-gray-300'}`}
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
            ))}
        </div>
    );
}

export default function AdminReviews({ reviews }: Props) {
    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this review?')) {
            router.delete(route('admin.reviews.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Reviews</h2>}
        >
            <Head title="Reviews" />

            <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tutor</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Parent</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rating</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Comment</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {reviews.data.length === 0 && (
                            <tr>
                                <td colSpan={7} className="px-6 py-8 text-center text-sm text-gray-400">
                                    No reviews yet.
                                </td>
                            </tr>
                        )}
                        {reviews.data.map((review) => (
                            <tr key={review.id} className="hover:bg-gray-50">
                                <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{review.tutor.name}</td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{review.parent.name}</td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{review.booking.subject.name}</td>
                                <td className="whitespace-nowrap px-6 py-4">
                                    <StarDisplay rating={review.rating} />
                                </td>
                                <td className="max-w-xs truncate px-6 py-4 text-sm text-gray-500">{review.comment ?? '-'}</td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {new Date(review.created_at).toLocaleDateString()}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                    <button
                                        onClick={() => handleDelete(review.id)}
                                        className="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AuthenticatedLayout>
    );
}
