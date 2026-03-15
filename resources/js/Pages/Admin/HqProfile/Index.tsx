import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type HqData = {
    company_name: string;
    ssm_number: string;
    ssm_details: string;
    address: string;
    phone: string;
    contact_email: string;
};

function InputField({ label, hint, error, ...props }: { label: string; hint?: string; error?: string } & React.InputHTMLAttributes<HTMLInputElement>) {
    return (
        <div>
            <label className="block text-sm font-medium text-gray-700">{label}</label>
            <input
                {...props}
                className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
            {hint && <p className="mt-1 text-xs text-gray-400">{hint}</p>}
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}

export default function HqProfileIndex({ hq }: { hq: HqData }) {
    const companyForm = useForm({
        company_name: hq.company_name,
        ssm_number: hq.ssm_number,
        ssm_details: hq.ssm_details,
        address: hq.address,
        phone: hq.phone,
        contact_email: hq.contact_email,
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submitCompany = (e: FormEvent) => {
        e.preventDefault();
        companyForm.post(route('admin.hq-profile.update-company'));
    };

    const submitPassword = (e: FormEvent) => {
        e.preventDefault();
        passwordForm.post(route('admin.hq-profile.update-password'), {
            onSuccess: () => passwordForm.reset(),
        });
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">HQ Profile</h2>}
        >
            <Head title="HQ Profile" />

            <div className="mx-auto max-w-4xl space-y-6">
                {/* Company Info */}
                <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                    <div className="border-b px-6 py-4">
                        <h3 className="text-lg font-semibold text-gray-900">Company Information</h3>
                        <p className="mt-0.5 text-sm text-gray-500">Your business details for invoicing and legal purposes.</p>
                    </div>
                    <form onSubmit={submitCompany} className="p-6">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <InputField
                                    label="Company Name"
                                    value={companyForm.data.company_name}
                                    onChange={e => companyForm.setData('company_name', e.target.value)}
                                    error={companyForm.errors.company_name}
                                    placeholder="TutorHUB Sdn Bhd"
                                />
                            </div>
                            <InputField
                                label="SSM Registration No."
                                value={companyForm.data.ssm_number}
                                onChange={e => companyForm.setData('ssm_number', e.target.value)}
                                error={companyForm.errors.ssm_number}
                                placeholder="202401012345 (1234567-X)"
                            />
                            <InputField
                                label="SSM Details"
                                value={companyForm.data.ssm_details}
                                onChange={e => companyForm.setData('ssm_details', e.target.value)}
                                error={companyForm.errors.ssm_details}
                                placeholder="e.g. Enterprise / Sdn Bhd"
                                hint="Business type or additional SSM info"
                            />
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-medium text-gray-700">Address</label>
                                <textarea
                                    value={companyForm.data.address}
                                    onChange={e => companyForm.setData('address', e.target.value)}
                                    rows={3}
                                    placeholder="Full business address"
                                    className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                                {companyForm.errors.address && <p className="mt-1 text-sm text-red-600">{companyForm.errors.address}</p>}
                            </div>
                            <InputField
                                label="Phone"
                                value={companyForm.data.phone}
                                onChange={e => companyForm.setData('phone', e.target.value)}
                                error={companyForm.errors.phone}
                                placeholder="03-12345678"
                            />
                            <InputField
                                label="Contact Email"
                                type="email"
                                value={companyForm.data.contact_email}
                                onChange={e => companyForm.setData('contact_email', e.target.value)}
                                error={companyForm.errors.contact_email}
                                placeholder="info@tutorhub.my"
                                hint="Public contact email, not the login email"
                            />
                        </div>

                        <div className="mt-6 flex justify-end">
                            <button
                                type="submit"
                                disabled={companyForm.processing}
                                className="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {companyForm.processing ? 'Saving...' : 'Save Company Info'}
                            </button>
                        </div>
                    </form>
                </div>

                {/* Change Password */}
                <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                    <div className="border-b px-6 py-4">
                        <h3 className="text-lg font-semibold text-gray-900">Change Password</h3>
                        <p className="mt-0.5 text-sm text-gray-500">Ensure your account uses a strong password.</p>
                    </div>
                    <form onSubmit={submitPassword} className="p-6">
                        <div className="max-w-md space-y-4">
                            <InputField
                                label="Current Password"
                                type="password"
                                value={passwordForm.data.current_password}
                                onChange={e => passwordForm.setData('current_password', e.target.value)}
                                error={passwordForm.errors.current_password}
                                autoComplete="current-password"
                            />
                            <InputField
                                label="New Password"
                                type="password"
                                value={passwordForm.data.password}
                                onChange={e => passwordForm.setData('password', e.target.value)}
                                error={passwordForm.errors.password}
                                autoComplete="new-password"
                                hint="Minimum 6 characters"
                            />
                            <InputField
                                label="Confirm New Password"
                                type="password"
                                value={passwordForm.data.password_confirmation}
                                onChange={e => passwordForm.setData('password_confirmation', e.target.value)}
                                error={passwordForm.errors.password_confirmation}
                                autoComplete="new-password"
                            />
                        </div>

                        <div className="mt-6 flex justify-end">
                            <button
                                type="submit"
                                disabled={passwordForm.processing}
                                className="rounded-lg bg-gray-800 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-gray-900 disabled:opacity-50"
                            >
                                {passwordForm.processing ? 'Updating...' : 'Update Password'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
