import { useState } from 'react';

type Props = {
    latitude: string | number | null;
    longitude: string | number | null;
    onChange: (latitude: string, longitude: string) => void;
    /** Who this position belongs to, for the explanatory copy. */
    subject?: string;
    error?: string;
};

/**
 * Sets a position, either from the browser or by typing it.
 *
 * Distance matching needs a point, and an address alone does not give one
 * unless a geocoding service is configured. Letting people place themselves
 * removes that dependency entirely.
 */
export default function LocationPicker({ latitude, longitude, onChange, subject = 'this address', error }: Props) {
    const [state, setState] = useState<'idle' | 'locating' | 'denied' | 'unavailable' | 'done'>('idle');

    const placed = latitude !== null && latitude !== '' && longitude !== null && longitude !== '';

    const detect = () => {
        if (!navigator.geolocation) {
            setState('unavailable');
            return;
        }

        setState('locating');

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                // Seven decimals is roughly a centimetre — far finer than needed,
                // but it costs nothing and avoids rounding a pin off a building.
                onChange(pos.coords.latitude.toFixed(7), pos.coords.longitude.toFixed(7));
                setState('done');
            },
            (err) => setState(err.code === err.PERMISSION_DENIED ? 'denied' : 'unavailable'),
            { timeout: 10000, enableHighAccuracy: true },
        );
    };

    return (
        <div className="rounded-lg bg-gray-50 p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p className="text-sm font-medium text-gray-700">Map position</p>
                    <p className="text-xs text-gray-500">
                        {placed
                            ? `We can measure distances to ${subject}.`
                            : `Without this, ${subject} will not appear in distance searches.`}
                    </p>
                </div>
                <button
                    type="button"
                    onClick={detect}
                    disabled={state === 'locating'}
                    className="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50"
                >
                    {state === 'locating' ? 'Locating…' : 'Use my current location'}
                </button>
            </div>

            {state === 'denied' && (
                <p className="mt-2 text-xs text-amber-700">
                    Your browser refused to share the location. Allow it in the address bar, or type the
                    coordinates below.
                </p>
            )}
            {state === 'unavailable' && (
                <p className="mt-2 text-xs text-amber-700">
                    The location could not be read. Note that browsers only share it over a secure
                    connection — you can type the coordinates below instead.
                </p>
            )}
            {state === 'done' && <p className="mt-2 text-xs text-green-600">Position set from your device.</p>}

            <div className="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <label className="block text-xs font-medium text-gray-600">Latitude</label>
                    <input
                        type="number"
                        step="any"
                        value={latitude ?? ''}
                        onChange={(e) => onChange(e.target.value, String(longitude ?? ''))}
                        placeholder="3.1390"
                        className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-600">Longitude</label>
                    <input
                        type="number"
                        step="any"
                        value={longitude ?? ''}
                        onChange={(e) => onChange(String(latitude ?? ''), e.target.value)}
                        placeholder="101.6869"
                        className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
            </div>

            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}

            {placed && (
                <button
                    type="button"
                    onClick={() => { onChange('', ''); setState('idle'); }}
                    className="mt-2 text-xs text-gray-500 underline hover:text-gray-700"
                >
                    Clear position
                </button>
            )}
        </div>
    );
}
