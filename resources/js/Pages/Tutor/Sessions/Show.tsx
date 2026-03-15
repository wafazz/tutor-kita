import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { QRCodeSVG } from 'qrcode.react';
import { useState, useRef } from 'react';

type SessionFull = {
    id: number;
    session_date: string;
    start_time: string | null;
    end_time: string | null;
    check_in_token: string;
    checked_in_at: string | null;
    checked_out_at: string | null;
    check_in_lat: string | null;
    check_in_lng: string | null;
    check_out_lat: string | null;
    check_out_lng: string | null;
    check_in_method: string | null;
    duration_minutes: number | null;
    status: string;
    tutor_notes: string | null;
    proof_photos: string[] | null;
    parent_confirmed: boolean;
    booking: {
        id: number;
        parent: { name: string; phone: string | null };
        student: { name: string };
        subject: { name: string };
        location_type: string;
        location_address: string | null;
        hourly_rate: string;
        duration_hours: string;
    };
};

type Props = {
    session: SessionFull;
};

const statusColors: Record<string, string> = {
    scheduled: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    missed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
};

export default function SessionShow({ session }: Props) {
    const [notes, setNotes] = useState('');
    const [gettingLocation, setGettingLocation] = useState(false);
    const [uploading, setUploading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const photos = session.proof_photos ?? [];
    const hasPhotos = photos.length > 0;

    const getLocationAndSubmit = (action: 'check-in' | 'check-out') => {
        if (action === 'check-out' && !hasPhotos) {
            alert('Please upload at least one proof photo before checking out.');
            return;
        }

        setGettingLocation(true);

        if (!navigator.geolocation) {
            submitAction(action, null, null);
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                submitAction(action, pos.coords.latitude, pos.coords.longitude);
            },
            () => {
                submitAction(action, null, null);
            },
            { timeout: 10000, enableHighAccuracy: true }
        );
    };

    const submitAction = (action: 'check-in' | 'check-out', lat: number | null, lng: number | null) => {
        const routeName = action === 'check-in' ? 'tutor.sessions.check-in' : 'tutor.sessions.check-out';
        const data: any = { lat, lng };
        if (action === 'check-out') {
            data.tutor_notes = notes;
        }
        router.post(route(routeName, session.id), data, {
            onFinish: () => setGettingLocation(false),
        });
    };

    const handlePhotoUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setUploading(true);
        const formData = new FormData();
        formData.append('photo', file);

        router.post(route('tutor.sessions.upload-proof', session.id), formData, {
            forceFormData: true,
            onFinish: () => {
                setUploading(false);
                if (fileInputRef.current) fileInputRef.current.value = '';
            },
        });
    };

    const handleRemovePhoto = (index: number) => {
        router.post(route('tutor.sessions.remove-proof', session.id), { index });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Session #{session.id}
                    </h2>
                    <Link href={route('tutor.sessions.index')} className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Back
                    </Link>
                </div>
            }
        >
            <Head title={`Session #${session.id}`} />

            <div className="mx-auto max-w-4xl">
                {/* Session Details */}
                <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Date</dt>
                            <dd className="mt-1 text-sm text-gray-900">{session.session_date}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Time</dt>
                            <dd className="mt-1 text-sm text-gray-900">{session.start_time ?? '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Student</dt>
                            <dd className="mt-1 text-sm text-gray-900">{session.booking.student.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Subject</dt>
                            <dd className="mt-1 text-sm text-gray-900">{session.booking.subject.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Parent</dt>
                            <dd className="mt-1 text-sm text-gray-900">{session.booking.parent.name}</dd>
                            {session.booking.parent.phone && <dd className="text-sm text-gray-500">{session.booking.parent.phone}</dd>}
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Location</dt>
                            <dd className="mt-1 text-sm text-gray-900 capitalize">{session.booking.location_type}</dd>
                            {session.booking.location_address && <dd className="text-sm text-gray-500">{session.booking.location_address}</dd>}
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Rate</dt>
                            <dd className="mt-1 text-sm text-gray-900">RM {Number(session.booking.hourly_rate).toFixed(2)} / hr</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Status</dt>
                            <dd className="mt-1">
                                <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize ${statusColors[session.status] ?? 'bg-gray-100 text-gray-800'}`}>
                                    {session.status.replace('_', ' ')}
                                </span>
                                {session.parent_confirmed && (
                                    <span className="ml-2 inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                        Parent Confirmed
                                    </span>
                                )}
                            </dd>
                        </div>
                        {session.checked_in_at && (
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Checked In</dt>
                                <dd className="mt-1 text-sm text-gray-900">{new Date(session.checked_in_at).toLocaleString()}</dd>
                                {session.check_in_lat && <dd className="text-xs text-gray-400">GPS: {session.check_in_lat}, {session.check_in_lng}</dd>}
                            </div>
                        )}
                        {session.checked_out_at && (
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Checked Out</dt>
                                <dd className="mt-1 text-sm text-gray-900">{new Date(session.checked_out_at).toLocaleString()}</dd>
                                {session.check_out_lat && <dd className="text-xs text-gray-400">GPS: {session.check_out_lat}, {session.check_out_lng}</dd>}
                            </div>
                        )}
                        {session.duration_minutes != null && (
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Duration</dt>
                                <dd className="mt-1 text-sm text-gray-900">{session.duration_minutes} minutes</dd>
                            </div>
                        )}
                        {session.tutor_notes && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Session Notes</dt>
                                <dd className="mt-1 text-sm text-gray-900">{session.tutor_notes}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                {/* Proof Photos */}
                {(session.status === 'checked_in' || session.status === 'completed') && (
                    <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-medium text-gray-900">Proof Photos</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    {session.status === 'checked_in'
                                        ? 'Upload at least one photo as proof before checking out.'
                                        : `${photos.length} photo${photos.length !== 1 ? 's' : ''} uploaded.`}
                                </p>
                            </div>
                            {!hasPhotos && session.status === 'checked_in' && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    Required
                                </span>
                            )}
                        </div>

                        {/* Photo Grid */}
                        {photos.length > 0 && (
                            <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                {photos.map((photo, index) => (
                                    <div key={index} className="group relative aspect-square overflow-hidden rounded-lg border border-gray-200">
                                        <img src={photo} alt={`Proof ${index + 1}`} className="h-full w-full object-cover" />
                                        {session.status === 'checked_in' && (
                                            <button
                                                onClick={() => handleRemovePhoto(index)}
                                                className="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-red-600 text-white opacity-0 transition-opacity group-hover:opacity-100"
                                            >
                                                <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Upload Button */}
                        {session.status === 'checked_in' && (
                            <div className="mt-4">
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/*"
                                    capture="environment"
                                    onChange={handlePhotoUpload}
                                    className="hidden"
                                    id="proof-upload"
                                />
                                <label
                                    htmlFor="proof-upload"
                                    className={`inline-flex cursor-pointer items-center gap-2 rounded-lg border-2 border-dashed px-4 py-3 text-sm font-medium transition-colors ${
                                        uploading
                                            ? 'border-gray-200 bg-gray-50 text-gray-400'
                                            : 'border-indigo-300 bg-indigo-50 text-indigo-600 hover:border-indigo-400 hover:bg-indigo-100'
                                    }`}
                                >
                                    {uploading ? (
                                        <>
                                            <svg className="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                            </svg>
                                            Uploading...
                                        </>
                                    ) : (
                                        <>
                                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                            </svg>
                                            Take / Upload Photo
                                        </>
                                    )}
                                </label>
                                <p className="mt-1.5 text-xs text-gray-400">Max 5MB per photo. JPG, PNG supported.</p>
                            </div>
                        )}
                    </div>
                )}

                {/* QR Code */}
                {session.status === 'scheduled' && (
                    <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900">Session QR Code</h3>
                        <p className="mt-1 text-sm text-gray-600">Show this QR code to the parent to verify your attendance.</p>
                        <div className="mt-4 flex justify-center">
                            <div className="rounded-lg border-2 border-gray-200 p-4">
                                <QRCodeSVG value={session.check_in_token} size={200} />
                            </div>
                        </div>
                        <p className="mt-2 text-center text-xs text-gray-400">Token: {session.check_in_token}</p>
                    </div>
                )}

                {/* Check-In Button */}
                {session.status === 'scheduled' && (
                    <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900">Check In</h3>
                        <p className="mt-1 text-sm text-gray-600">Click to check in. Your GPS location will be recorded.</p>
                        <div className="mt-4">
                            <button
                                onClick={() => getLocationAndSubmit('check-in')}
                                disabled={gettingLocation}
                                className="rounded-md bg-green-600 px-6 py-3 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                            >
                                {gettingLocation ? 'Getting location...' : 'Check In Now'}
                            </button>
                        </div>
                    </div>
                )}

                {/* Check-Out Button */}
                {session.status === 'checked_in' && (
                    <div className="mt-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900">Check Out</h3>
                        <p className="mt-1 text-sm text-gray-600">End the session and record your GPS location.</p>
                        <div className="mt-4 space-y-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Session Notes (optional)</label>
                                <textarea
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    rows={3}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    placeholder="What was covered in this session..."
                                />
                            </div>

                            {!hasPhotos && (
                                <div className="rounded-lg border border-amber-200 bg-amber-50 p-3">
                                    <div className="flex items-center gap-2">
                                        <svg className="h-5 w-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                        <p className="text-sm text-amber-800">You must upload at least one proof photo before checking out.</p>
                                    </div>
                                </div>
                            )}

                            <button
                                onClick={() => getLocationAndSubmit('check-out')}
                                disabled={gettingLocation || !hasPhotos}
                                className="rounded-md bg-red-600 px-6 py-3 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {gettingLocation ? 'Getting location...' : 'Check Out Now'}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
