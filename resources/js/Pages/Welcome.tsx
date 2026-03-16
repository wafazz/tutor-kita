import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

const t = {
    ms: {
        title: 'Platform Tutor Rumah',
        login: 'Log Masuk',
        getStarted: 'Mula Sekarang',
        dashboard: 'Dashboard',
        badge: 'Platform Tutor Rumah Dipercayai di Malaysia',
        heroTitle1: 'Cari',
        heroHighlight: ' tutor rumah ',
        heroTitle2: 'terbaik untuk anak anda',
        heroDesc: 'Hubungi tutor yang disahkan dan berpengalaman. Urus tempahan, jejak sesi dengan GPS check-in, dan bayar dengan selamat — semua dalam satu platform.',
        registerParent: 'Daftar Sebagai Ibu Bapa',
        browseTutors: 'Lihat Tutor',
        stats: [
            { value: '100+', label: 'Tutor Disahkan' },
            { value: '20+', label: 'Subjek' },
            { value: '500+', label: 'Sesi Selesai' },
            { value: '4.8', label: 'Penilaian Purata' },
        ],
        howItWorks: 'Cara ia berfungsi',
        howTitle: 'Langkah mudah untuk bermula',
        howDesc: 'Dari pendaftaran hingga sesi pertama anak anda — hanya ambil beberapa minit.',
        steps: [
            { step: '01', title: 'Daftar', desc: 'Buat akaun ibu bapa dan tambah maklumat anak anda.' },
            { step: '02', title: 'Minta Tutor', desc: 'Pilih subjek, pakej, dan jadual yang anda mahu.' },
            { step: '03', title: 'Dipadankan', desc: 'Kami padankan anda dengan tutor disahkan terbaik.' },
            { step: '04', title: 'Mula Belajar', desc: 'Sesi dijejak dengan GPS check-in dan foto bukti.' },
        ],
        featuresLabel: 'Ciri-ciri',
        featuresTitle: 'Semua yang anda perlukan dalam satu platform',
        features: [
            { title: 'Tutor Disahkan', desc: 'Setiap tutor melalui proses pengesahan dengan semakan dokumen sebelum boleh mengajar.' },
            { title: 'GPS Check-In', desc: 'Tutor check-in dan check-out dengan penjejakan lokasi GPS dan kod QR untuk ketelusan penuh.' },
            { title: 'Foto Bukti', desc: 'Tutor memuat naik foto bukti sesi supaya ibu bapa dapat melihat pembelajaran berlaku.' },
            { title: 'Bayaran Selamat', desc: 'Bayar melalui FPX via BayarCash — selamat, pantas, dan tanpa kerumitan.' },
            { title: 'Pakej Fleksibel', desc: 'Pilih daripada pelbagai pakej sesi yang sesuai dengan jadual dan bajet anda.' },
            { title: 'Penilaian & Ulasan', desc: 'Nilai dan ulas tutor selepas setiap tempahan untuk membantu ibu bapa lain membuat pilihan.' },
        ],
        forTutors: 'Untuk Tutor',
        forTutorsTitle: 'Kembangkan kerjaya tutor anda bersama kami',
        forTutorsDesc: 'Sertai platform kami dan dipadankan dengan pelajar di kawasan anda. Urus jadual, jejak pendapatan, dan bina reputasi anda.',
        forTutorsList: [
            'Dipadankan dengan pelajar berdekatan secara automatik',
            'Jadual fleksibel — anda tentukan bila mahu mengajar',
            'Pendapatan telus dengan bayaran bulanan',
            'Bina profil anda dengan penilaian dan ulasan',
        ],
        registerTutor: 'Daftar Sebagai Tutor',
        tutorStats: [
            { value: 'RM 2,000+', label: 'Pendapatan Purata Bulanan' },
            { value: '50+', label: 'Pelajar Aktif' },
            { value: '4.9', label: 'Penilaian Platform' },
            { value: '24/7', label: 'Sokongan Platform' },
        ],
        ctaTitle: 'Bersedia mencari tutor terbaik?',
        ctaDesc: 'Sertai ratusan ibu bapa yang mempercayai TutorHUB untuk pendidikan anak mereka. Mula dalam beberapa minit.',
        ctaButton: 'Daftar Sekarang — Percuma',
        footerBrowse: 'Lihat Tutor',
        footerLogin: 'Log Masuk',
        footerRegister: 'Daftar',
    },
    en: {
        title: 'Home Tutor Platform',
        login: 'Log in',
        getStarted: 'Get Started',
        dashboard: 'Dashboard',
        badge: 'Trusted Home Tutor Platform in Malaysia',
        heroTitle1: 'Find the perfect',
        heroHighlight: ' home tutor ',
        heroTitle2: 'for your child',
        heroDesc: 'Connect with verified, experienced tutors. Manage bookings, track sessions with GPS check-in, and pay securely — all in one platform.',
        registerParent: 'Register as Parent',
        browseTutors: 'Browse Tutors',
        stats: [
            { value: '100+', label: 'Verified Tutors' },
            { value: '20+', label: 'Subjects' },
            { value: '500+', label: 'Sessions Done' },
            { value: '4.8', label: 'Avg Rating' },
        ],
        howItWorks: 'How it works',
        howTitle: 'Simple steps to get started',
        howDesc: "From registration to your child's first session — it only takes a few minutes.",
        steps: [
            { step: '01', title: 'Register', desc: "Create your parent account and add your child's details." },
            { step: '02', title: 'Request a Tutor', desc: 'Choose a subject, package, and your preferred schedule.' },
            { step: '03', title: 'Get Matched', desc: 'We match you with the best verified tutor for your needs.' },
            { step: '04', title: 'Start Learning', desc: 'Sessions tracked with GPS check-in and proof photos.' },
        ],
        featuresLabel: 'Features',
        featuresTitle: 'Everything you need in one platform',
        features: [
            { title: 'Verified Tutors', desc: 'Every tutor goes through a verification process with document checks before they can teach.' },
            { title: 'GPS Check-In', desc: 'Tutors check in and out with GPS location tracking and QR codes for full transparency.' },
            { title: 'Proof Photos', desc: 'Tutors upload session proof photos so parents can see learning in action.' },
            { title: 'Secure Payments', desc: 'Pay via FPX through BayarCash — safe, fast, and hassle-free online banking.' },
            { title: 'Flexible Packages', desc: 'Choose from multiple session packages that fit your schedule and budget.' },
            { title: 'Ratings & Reviews', desc: 'Rate and review tutors after each booking to help other parents make better choices.' },
        ],
        forTutors: 'For Tutors',
        forTutorsTitle: 'Grow your tutoring career with us',
        forTutorsDesc: 'Join our platform and get matched with students in your area. Manage your schedule, track your earnings, and build your reputation.',
        forTutorsList: [
            'Get matched with students near you automatically',
            'Flexible scheduling — you decide when to teach',
            'Transparent earnings with monthly payouts',
            'Build your profile with ratings and reviews',
        ],
        registerTutor: 'Register as Tutor',
        tutorStats: [
            { value: 'RM 2,000+', label: 'Avg Monthly Earnings' },
            { value: '50+', label: 'Active Students' },
            { value: '4.9', label: 'Platform Rating' },
            { value: '24/7', label: 'Platform Support' },
        ],
        ctaTitle: 'Ready to find the perfect tutor?',
        ctaDesc: "Join hundreds of parents who trust TutorHUB for their children's education. Get started in minutes.",
        ctaButton: "Register Now — It's Free",
        footerBrowse: 'Browse Tutors',
        footerLogin: 'Log In',
        footerRegister: 'Register',
    },
};

const stepIcons = [
    'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
    'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    'M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.745 3.745 0 011.043 3.296A3.745 3.745 0 0121 12z',
    'M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342',
];

const featureIcons = [
    'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
    'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z',
    'M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z',
    'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
    'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
    'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
];

const featureColors = [
    'bg-green-50 text-green-600',
    'bg-blue-50 text-blue-600',
    'bg-purple-50 text-purple-600',
    'bg-amber-50 text-amber-600',
    'bg-rose-50 text-rose-600',
    'bg-yellow-50 text-yellow-600',
];

const tutorStatIcons = [
    'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
    'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
    'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
];

type Lang = 'ms' | 'en';

export default function Welcome({ auth }: PageProps) {
    const [lang, setLang] = useState<Lang>('ms');
    const l = t[lang];

    return (
        <>
            <Head title={l.title} />

            <div className="min-h-screen bg-white">
                {/* Navbar */}
                <nav className="sticky top-0 z-50 border-b border-gray-100 bg-white/80 backdrop-blur-lg">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                        <Link href="/" className="flex items-center gap-2.5">
                            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600">
                                <svg className="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                                </svg>
                            </div>
                            <span className="text-xl font-bold text-gray-900">TutorHUB</span>
                        </Link>

                        <div className="flex items-center gap-3">
                            {/* Language Toggle */}
                            <button
                                onClick={() => setLang(lang === 'ms' ? 'en' : 'ms')}
                                className="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-indigo-300 hover:text-indigo-600"
                            >
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                                {lang === 'ms' ? 'EN' : 'BM'}
                            </button>

                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                                >
                                    {l.dashboard}
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 transition hover:text-indigo-600"
                                    >
                                        {l.login}
                                    </Link>
                                    <Link
                                        href={route('register.parent')}
                                        className="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                                    >
                                        {l.getStarted}
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero */}
                <section className="relative overflow-hidden">
                    <div className="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-purple-50" />
                    <div className="absolute -top-40 right-0 h-[500px] w-[500px] rounded-full bg-indigo-100/50 blur-3xl" />
                    <div className="absolute -bottom-40 left-0 h-[400px] w-[400px] rounded-full bg-purple-100/50 blur-3xl" />

                    <div className="relative mx-auto max-w-7xl px-6 pb-24 pt-20 lg:pb-32 lg:pt-28">
                        <div className="mx-auto max-w-3xl text-center">
                            <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm font-medium text-indigo-700">
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                                </svg>
                                {l.badge}
                            </div>

                            <h1 className="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                                {l.heroTitle1}
                                <span className="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">{l.heroHighlight}</span>
                                {l.heroTitle2}
                            </h1>

                            <p className="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-gray-600">
                                {l.heroDesc}
                            </p>

                            <div className="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                                <Link
                                    href={route('register.parent')}
                                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-500 sm:w-auto"
                                >
                                    {l.registerParent}
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                                <Link
                                    href={route('tutors.browse')}
                                    className="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-8 py-3.5 text-sm font-semibold text-gray-700 transition hover:border-indigo-300 hover:text-indigo-600 sm:w-auto"
                                >
                                    {l.browseTutors}
                                </Link>
                            </div>
                        </div>

                        {/* Stats */}
                        <div className="mx-auto mt-20 grid max-w-3xl grid-cols-2 gap-6 sm:grid-cols-4">
                            {l.stats.map((stat) => (
                                <div key={stat.value} className="rounded-2xl bg-white p-5 text-center shadow-sm ring-1 ring-gray-100">
                                    <p className="text-2xl font-bold text-indigo-600">{stat.value}</p>
                                    <p className="mt-1 text-xs font-medium text-gray-500">{stat.label}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* How it works */}
                <section className="bg-gray-50 py-20 lg:py-28">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mx-auto max-w-2xl text-center">
                            <p className="text-sm font-semibold uppercase tracking-wider text-indigo-600">{l.howItWorks}</p>
                            <h2 className="mt-3 text-3xl font-bold text-gray-900 sm:text-4xl">{l.howTitle}</h2>
                            <p className="mt-4 text-gray-600">{l.howDesc}</p>
                        </div>

                        <div className="mx-auto mt-16 grid max-w-5xl gap-8 sm:grid-cols-2 lg:grid-cols-4">
                            {l.steps.map((item, i) => (
                                <div key={item.step} className="relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 transition hover:shadow-md">
                                    <span className="text-xs font-bold text-indigo-600">{item.step}</span>
                                    <div className="mt-3 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50">
                                        <svg className="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d={stepIcons[i]} />
                                        </svg>
                                    </div>
                                    <h3 className="mt-4 text-base font-semibold text-gray-900">{item.title}</h3>
                                    <p className="mt-2 text-sm leading-relaxed text-gray-500">{item.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section className="py-20 lg:py-28">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mx-auto max-w-2xl text-center">
                            <p className="text-sm font-semibold uppercase tracking-wider text-indigo-600">{l.featuresLabel}</p>
                            <h2 className="mt-3 text-3xl font-bold text-gray-900 sm:text-4xl">{l.featuresTitle}</h2>
                        </div>

                        <div className="mx-auto mt-16 grid max-w-5xl gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {l.features.map((feature, i) => (
                                <div key={feature.title} className="rounded-2xl border border-gray-100 bg-white p-6 transition hover:border-indigo-100 hover:shadow-md">
                                    <div className={`flex h-11 w-11 items-center justify-center rounded-xl ${featureColors[i]}`}>
                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d={featureIcons[i]} />
                                        </svg>
                                    </div>
                                    <h3 className="mt-4 text-base font-semibold text-gray-900">{feature.title}</h3>
                                    <p className="mt-2 text-sm leading-relaxed text-gray-500">{feature.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* For Tutors */}
                <section className="bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 py-20 lg:py-28">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="grid items-center gap-12 lg:grid-cols-2">
                            <div>
                                <p className="text-sm font-semibold uppercase tracking-wider text-indigo-200">{l.forTutors}</p>
                                <h2 className="mt-3 text-3xl font-bold text-white sm:text-4xl">{l.forTutorsTitle}</h2>
                                <p className="mt-4 text-lg leading-relaxed text-indigo-200">
                                    {l.forTutorsDesc}
                                </p>

                                <div className="mt-8 space-y-4">
                                    {l.forTutorsList.map((item) => (
                                        <div key={item} className="flex items-center gap-3">
                                            <svg className="h-5 w-5 shrink-0 text-indigo-300" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            <span className="text-sm text-indigo-100">{item}</span>
                                        </div>
                                    ))}
                                </div>

                                <Link
                                    href={route('register.tutor')}
                                    className="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50"
                                >
                                    {l.registerTutor}
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                {l.tutorStats.map((card, i) => (
                                    <div key={card.value} className="rounded-2xl bg-white/10 p-6 backdrop-blur-sm">
                                        <svg className="h-7 w-7 text-indigo-300" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d={tutorStatIcons[i]} />
                                        </svg>
                                        <p className="mt-3 text-2xl font-bold text-white">{card.value}</p>
                                        <p className="mt-1 text-xs text-indigo-200">{card.label}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section className="py-20 lg:py-28">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mx-auto max-w-3xl rounded-3xl bg-gradient-to-r from-indigo-600 to-purple-600 p-10 text-center shadow-2xl shadow-indigo-200 sm:p-16">
                            <h2 className="text-3xl font-bold text-white sm:text-4xl">{l.ctaTitle}</h2>
                            <p className="mx-auto mt-4 max-w-lg text-indigo-100">
                                {l.ctaDesc}
                            </p>
                            <div className="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                                <Link
                                    href={route('register.parent')}
                                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-white px-8 py-3.5 text-sm font-semibold text-indigo-700 shadow-lg transition hover:bg-indigo-50 sm:w-auto"
                                >
                                    {l.ctaButton}
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                                <Link
                                    href={route('tutors.browse')}
                                    className="flex w-full items-center justify-center rounded-xl border border-white/30 px-8 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10 sm:w-auto"
                                >
                                    {l.browseTutors}
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-gray-100 bg-gray-50 py-12">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="flex flex-col items-center justify-between gap-6 sm:flex-row">
                            <div className="flex items-center gap-2.5">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600">
                                    <svg className="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                                    </svg>
                                </div>
                                <span className="text-sm font-bold text-gray-900">TutorHUB</span>
                            </div>

                            <div className="flex gap-6 text-sm text-gray-500">
                                <Link href={route('tutors.browse')} className="transition hover:text-indigo-600">{l.footerBrowse}</Link>
                                <Link href={route('login')} className="transition hover:text-indigo-600">{l.footerLogin}</Link>
                                <Link href={route('register.parent')} className="transition hover:text-indigo-600">{l.footerRegister}</Link>
                            </div>

                            <p className="text-sm text-gray-400">
                                &copy; {new Date().getFullYear()} TutorHUB. All rights reserved.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
