import { Head, Link } from '@inertiajs/react';

export default function PendingApproval({ status }: { status: string }) {
    const isRejected = status === 'rejected';

    return (
        <>
            <Head title="Pending Approval" />

            <div className="flex min-h-screen">
                {/* Left — Branding */}
                <div className="hidden w-1/2 flex-col justify-between bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 p-12 lg:flex">
                    <div>
                        <Link href="/" className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                                <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                                </svg>
                            </div>
                            <span className="text-2xl font-bold text-white">TutorHUB</span>
                        </Link>
                    </div>

                    <div>
                        <h1 className="text-4xl font-bold leading-tight text-white">
                            {isRejected ? (
                                <>
                                    Application
                                    <br />
                                    not approved
                                </>
                            ) : (
                                <>
                                    Your application
                                    <br />
                                    is being reviewed
                                </>
                            )}
                        </h1>
                        <p className="mt-4 max-w-md text-lg text-emerald-200">
                            {isRejected
                                ? 'Unfortunately, your application was not approved at this time. Please contact our support team for more information.'
                                : 'Our HQ team is reviewing your registration. You will be notified once your account has been verified.'}
                        </p>

                        <div className="mt-8 space-y-4">
                            <div className="flex items-center gap-4 rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/20">
                                    <svg className="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-white">Account created</p>
                                    <p className="text-xs text-emerald-200">Your account has been registered</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/20">
                                    <svg className="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-white">Email verified</p>
                                    <p className="text-xs text-emerald-200">Your email has been confirmed</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-400/30">
                                    {isRejected ? (
                                        <svg className="h-5 w-5 text-red-300" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    ) : (
                                        <svg className="h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                    )}
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-white">
                                        {isRejected ? 'HQ rejected' : 'HQ verification pending'}
                                    </p>
                                    <p className="text-xs text-emerald-200">
                                        {isRejected ? 'Contact support for details' : 'Awaiting HQ team approval'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p className="text-sm text-emerald-300">
                        TutorHUB &copy; {new Date().getFullYear()}. All rights reserved.
                    </p>
                </div>

                {/* Right — Content */}
                <div className="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
                    {/* Mobile logo */}
                    <div className="mb-8 flex items-center gap-3 lg:hidden">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-600">
                            <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                            </svg>
                        </div>
                        <span className="text-xl font-bold text-gray-900">TutorHUB</span>
                    </div>

                    <div className="mx-auto w-full max-w-sm">
                        {/* Status icon */}
                        <div className={`mx-auto flex h-20 w-20 items-center justify-center rounded-full ${isRejected ? 'bg-red-100' : 'bg-amber-100'}`}>
                            {isRejected ? (
                                <svg className="h-10 w-10 text-red-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            ) : (
                                <svg className="h-10 w-10 text-amber-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            )}
                        </div>

                        <h2 className="mt-6 text-center text-2xl font-bold text-gray-900">
                            {isRejected ? 'Application Not Approved' : 'Pending HQ Approval'}
                        </h2>
                        <p className="mt-2 text-center text-sm text-gray-500">
                            {isRejected
                                ? 'Your tutor application has been reviewed and was not approved at this time.'
                                : 'Your account is verified! Our HQ team needs to review and approve your application before you can start receiving jobs.'}
                        </p>

                        <div className="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-5">
                            <h3 className="text-sm font-semibold text-gray-900">What happens next?</h3>
                            {isRejected ? (
                                <ul className="mt-3 space-y-2 text-sm text-gray-600">
                                    <li className="flex items-start gap-2">
                                        <span className="mt-0.5 text-red-500">1.</span>
                                        Contact our support team for more details
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="mt-0.5 text-red-500">2.</span>
                                        You may reapply after addressing any issues
                                    </li>
                                </ul>
                            ) : (
                                <ul className="mt-3 space-y-2 text-sm text-gray-600">
                                    <li className="flex items-start gap-2">
                                        <span className="mt-0.5 text-emerald-600">1.</span>
                                        HQ team will review your registration
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="mt-0.5 text-emerald-600">2.</span>
                                        Once approved, you can access your dashboard
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="mt-0.5 text-emerald-600">3.</span>
                                        Start receiving job assignments from HQ
                                    </li>
                                </ul>
                            )}
                        </div>

                        <div className="mt-6 text-center">
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="text-sm text-gray-500 transition-colors hover:text-gray-700"
                            >
                                Sign out
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
