import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';

type Settings = {
    commission_rate: string;
    site_name: string;
    bayarcash_api_key: string;
    bayarcash_secret_key: string;
    bayarcash_portal_key: string;
    bayarcash_sandbox: string;
    resend_api_key: string;
    resend_from_email: string;
    resend_from_name: string;
    onsend_api_key: string;
    onsend_sender_id: string;
};

function SectionCard({ title, description, children }: { title: string; description: string; children: React.ReactNode }) {
    return (
        <div className="overflow-hidden rounded-xl bg-white shadow-sm">
            <div className="border-b px-6 py-4">
                <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
                <p className="mt-0.5 text-sm text-gray-500">{description}</p>
            </div>
            <div className="p-6">
                {children}
            </div>
        </div>
    );
}

function InputField({ label, hint, ...props }: { label: string; hint?: string } & React.InputHTMLAttributes<HTMLInputElement>) {
    return (
        <div>
            <label className="block text-sm font-medium text-gray-700">{label}</label>
            <input
                {...props}
                className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
            {hint && <p className="mt-1 text-xs text-gray-400">{hint}</p>}
        </div>
    );
}

export default function SettingsIndex({ settings }: { settings: Settings }) {
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm<Settings>({ ...settings });
    const flash = usePage().props.flash as { success?: string } | undefined;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('admin.settings.update'));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Settings</h2>}
        >
            <Head title="Settings" />

            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-6">
                {recentlySuccessful && (
                    <div className="rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                        Settings saved successfully.
                    </div>
                )}
                {Object.keys(errors).length > 0 && (
                    <div className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                        <p className="font-medium">Please fix the following errors:</p>
                        <ul className="mt-1 list-disc pl-5">
                            {Object.values(errors).map((err, i) => <li key={i}>{err}</li>)}
                        </ul>
                    </div>
                )}
                {/* General */}
                <SectionCard title="General" description="Basic platform configuration.">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <InputField
                            label="Site Name"
                            value={data.site_name}
                            onChange={e => setData('site_name', e.target.value)}
                        />
                        <InputField
                            label="Default Commission Rate (%)"
                            type="number"
                            min={0}
                            max={100}
                            step={0.5}
                            value={data.commission_rate}
                            onChange={e => setData('commission_rate', e.target.value)}
                            hint="Applied to new tutors by default"
                        />
                    </div>
                </SectionCard>

                {/* Bayarcash */}
                <SectionCard title="Bayarcash FPX" description="Payment gateway credentials for online FPX payments.">
                    <div className="space-y-4">
                        <InputField
                            label="API Key"
                            type="password"
                            value={data.bayarcash_api_key}
                            onChange={e => setData('bayarcash_api_key', e.target.value)}
                            placeholder="bc_xxxxxxxxxxxxxxxx"
                            autoComplete="off"
                        />
                        <InputField
                            label="Secret Key"
                            type="password"
                            value={data.bayarcash_secret_key}
                            onChange={e => setData('bayarcash_secret_key', e.target.value)}
                            placeholder="bc_secret_xxxxxxxx"
                            autoComplete="off"
                        />
                        <InputField
                            label="Portal Key"
                            value={data.bayarcash_portal_key}
                            onChange={e => setData('bayarcash_portal_key', e.target.value)}
                            placeholder="Portal/Channel token"
                            hint="From Bayarcash dashboard"
                        />
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Environment</label>
                            <select
                                value={data.bayarcash_sandbox}
                                onChange={e => setData('bayarcash_sandbox', e.target.value)}
                                className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="1">Sandbox (Testing)</option>
                                <option value="0">Production (Live)</option>
                            </select>
                            <p className="mt-1 text-xs text-gray-400">Use sandbox for testing before going live</p>
                        </div>
                    </div>
                    <div className="mt-4 rounded-lg bg-amber-50 px-4 py-3">
                        <div className="flex items-start gap-2">
                            <svg className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <p className="text-xs text-amber-700">Ensure you switch to Production only when ready to accept real payments.</p>
                        </div>
                    </div>
                </SectionCard>

                {/* Resend Email */}
                <SectionCard title="Resend.com Email" description="Email delivery service for notifications and receipts.">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <InputField
                            label="API Key"
                            type="password"
                            value={data.resend_api_key}
                            onChange={e => setData('resend_api_key', e.target.value)}
                            placeholder="re_xxxxxxxxxxxxxxxx"
                            autoComplete="off"
                        />
                        <InputField
                            label="From Email"
                            type="email"
                            value={data.resend_from_email}
                            onChange={e => setData('resend_from_email', e.target.value)}
                            placeholder="noreply@tutorhub.my"
                            hint="Must be verified in Resend"
                        />
                        <InputField
                            label="From Name"
                            value={data.resend_from_name}
                            onChange={e => setData('resend_from_name', e.target.value)}
                            placeholder="TutorHUB"
                        />
                    </div>
                </SectionCard>

                {/* Onsend WhatsApp */}
                <SectionCard title="Onsend WhatsApp" description="WhatsApp messaging API for session reminders and notifications.">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <InputField
                            label="API Key"
                            type="password"
                            value={data.onsend_api_key}
                            onChange={e => setData('onsend_api_key', e.target.value)}
                            placeholder="onsend_xxxxxxxxxxxxxxxx"
                            autoComplete="off"
                        />
                        <InputField
                            label="Sender ID"
                            value={data.onsend_sender_id}
                            onChange={e => setData('onsend_sender_id', e.target.value)}
                            placeholder="60123456789"
                            hint="WhatsApp number registered with Onsend"
                        />
                    </div>
                </SectionCard>

                {/* Submit */}
                <div className="flex justify-end">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {processing ? 'Saving...' : 'Save Settings'}
                    </button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
