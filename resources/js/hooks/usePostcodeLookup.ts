import { useCallback, useRef, useState } from 'react';

type Result = { found: boolean; city?: string; state?: string; cities?: string[] };

/**
 * Fills city and state from a Malaysian postcode.
 *
 * Only asks once the postcode is a complete five digits, and never overwrites
 * something already typed unless the user asks for it — a lookup should save
 * work, not undo it.
 */
export function usePostcodeLookup() {
    const [status, setStatus] = useState<'idle' | 'looking' | 'found' | 'unknown'>('idle');
    const [cities, setCities] = useState<string[]>([]);
    const lastAsked = useRef<string | null>(null);

    const lookup = useCallback(
        async (postcode: string, apply: (found: { city: string; state: string }) => void) => {
            const clean = (postcode ?? '').trim();

            if (!/^\d{5}$/.test(clean)) {
                setStatus('idle');
                setCities([]);
                return;
            }

            // The same postcode is not asked about twice in a row.
            if (lastAsked.current === clean) return;
            lastAsked.current = clean;

            setStatus('looking');

            try {
                const response = await fetch(`/postcode-lookup?postcode=${encodeURIComponent(clean)}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    setStatus('idle');
                    return;
                }

                const result: Result = await response.json();

                if (!result.found || !result.city || !result.state) {
                    setStatus('unknown');
                    setCities([]);
                    return;
                }

                setStatus('found');
                setCities(result.cities ?? []);
                apply({ city: result.city, state: result.state });
            } catch {
                // A failed lookup simply leaves the fields alone.
                setStatus('idle');
            }
        },
        [],
    );

    return { lookup, status, cities };
}
