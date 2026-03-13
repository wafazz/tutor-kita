import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function TutorDashboard() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Tutor Dashboard
                </h2>
            }
        >
            <Head title="Tutor Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                            <div className="text-sm font-medium text-gray-500">Active Bookings</div>
                            <div className="mt-1 text-3xl font-semibold text-gray-900">0</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                            <div className="text-sm font-medium text-gray-500">This Month Sessions</div>
                            <div className="mt-1 text-3xl font-semibold text-gray-900">0</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                            <div className="text-sm font-medium text-gray-500">Earnings (RM)</div>
                            <div className="mt-1 text-3xl font-semibold text-gray-900">0.00</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                            <div className="text-sm font-medium text-gray-500">Rating</div>
                            <div className="mt-1 text-3xl font-semibold text-gray-900">-</div>
                        </div>
                    </div>

                    <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6 text-gray-900">
                                <h3 className="text-lg font-medium">Upcoming Sessions</h3>
                                <p className="mt-2 text-gray-500">No upcoming sessions.</p>
                            </div>
                        </div>
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6 text-gray-900">
                                <h3 className="text-lg font-medium">Pending Job Offers</h3>
                                <p className="mt-2 text-gray-500">No pending offers.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
