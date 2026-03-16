import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});
    const { auth } = usePage().props as { auth: { user: { email: string } } };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <>
            <Head title="Verify Email" />

            <div className="flex min-h-screen">
                {/* Left — Branding */}
                <div className="hidden w-1/2 flex-col justify-between bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 p-12 lg:flex">
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
                            Almost there!
                            <br />
                            Just one more
                            <br />
                            step
                        </h1>
                        <p className="mt-4 max-w-md text-lg text-indigo-200">
                            We need to verify your email address to keep your account secure and ensure you receive important updates.
                        </p>

                        <div className="mt-8 space-y-4">
                            <div className="flex items-center gap-4 rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/20">
                                    <svg className="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-white">Check your inbox</p>
                                    <p className="text-xs text-indigo-200">We sent a verification link to your email</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/20">
                                    <svg className="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-white">Click the link</p>
                                    <p className="text-xs text-indigo-200">Open the email and click the verification button</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/20">
                                    <svg className="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-white">You're all set!</p>
                                    <p className="text-xs text-indigo-200">Start browsing tutors and booking sessions</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p className="text-sm text-indigo-300">
                        TutorHUB &copy; {new Date().getFullYear()}. All rights reserved.
                    </p>
                </div>

                {/* Right — Verification Content */}
                <div className="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
                    {/* Mobile logo */}
                    <div className="mb-8 flex items-center gap-3 lg:hidden">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600">
                            <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                            </svg>
                        </div>
                        <span className="text-xl font-bold text-gray-900">TutorHUB</span>
                    </div>

                    <div className="mx-auto w-full max-w-sm">
                        {/* Email icon */}
                        <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-indigo-100">
                            <svg className="h-10 w-10 text-indigo-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>

                        <h2 className="mt-6 text-center text-2xl font-bold text-gray-900">Check your email</h2>
                        <p className="mt-2 text-center text-sm text-gray-500">
                            We've sent a verification link to
                        </p>
                        <p className="mt-1 text-center text-sm font-semibold text-indigo-600">
                            {auth.user.email}
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-6 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4">
                                <svg className="h-5 w-5 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p className="text-sm font-medium text-green-800">
                                    A new verification link has been sent to your email address.
                                </p>
                            </div>
                        )}

                        <div className="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-5">
                            <p className="text-sm text-gray-600 leading-relaxed">
                                Click the verification link in the email we sent you. If you don't see it, check your spam folder.
                            </p>
                        </div>

                        <form onSubmit={submit} className="mt-6">
                            <button
                                type="submit"
                                disabled={processing}
                                className="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                {processing ? (
                                    <svg className="h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                ) : (
                                    <>
                                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                                        </svg>
                                        Resend Verification Email
                                    </>
                                )}
                            </button>
                        </form>

                        <div className="mt-6 text-center">
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="text-sm text-gray-500 transition-colors hover:text-gray-700"
                            >
                                Sign out and use a different account
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
