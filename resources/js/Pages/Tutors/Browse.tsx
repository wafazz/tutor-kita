import { Head, Link } from '@inertiajs/react';

export default function Browse() {
    return (
        <>
            <Head title="Browse Tutors" />

            <div className="min-h-screen bg-gray-50">
                <nav className="border-b border-gray-100 bg-white">
                    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                        <Link href="/" className="text-xl font-bold text-indigo-600">
                            TutorHUB
                        </Link>
                        <div className="flex gap-4">
                            <Link
                                href={route('login')}
                                className="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Log In
                            </Link>
                            <Link
                                href={route('register')}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                            >
                                Register
                            </Link>
                        </div>
                    </div>
                </nav>

                <div className="py-12">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <h1 className="mb-8 text-3xl font-bold text-gray-900">Browse Tutors</h1>
                        <p className="text-gray-500">Tutor listings coming soon.</p>
                    </div>
                </div>
            </div>
        </>
    );
}
