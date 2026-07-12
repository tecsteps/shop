import { router } from '@inertiajs/react';

export function HardcodedRouteButton() {
    return <button onClick={() => router.visit('/app/checks')}>Open</button>;
}
