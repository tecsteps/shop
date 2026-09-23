(() => {
    const root = document.querySelector('[data-storefront-analytics]');

    if (!root) {
        return;
    }

    const endpoint = root.dataset.analyticsEndpoint;
    const storageKey = `storefront-analytics-session:${window.location.hostname}`;
    const createId = () => {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    };

    let sessionId;

    try {
        sessionId = window.sessionStorage.getItem(storageKey);

        if (!sessionId) {
            sessionId = createId();
            window.sessionStorage.setItem(storageKey, sessionId);
        }
    } catch {
        sessionId = createId();
    }

    const device = /ipad|tablet/i.test(navigator.userAgent)
        ? 'tablet'
        : /mobile|iphone|android/i.test(navigator.userAgent)
            ? 'mobile'
            : 'desktop';

    const send = (type, properties = {}) => {
        const event = {
            type,
            session_id: sessionId,
            client_event_id: createId(),
            occurred_at: new Date().toISOString(),
            properties: {
                ...properties,
                channel: 'storefront',
                device,
            },
        };

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ events: [event] }),
        }).catch(() => {});
    };

    let lastPageUrl = window.location.href;
    let livewireNavigationStarted = false;

    const trackPage = () => {
        send('page_view', {
            url: `${window.location.pathname}${window.location.search}`,
            referrer: document.referrer,
            user_agent: navigator.userAgent,
        });

        const productMatch = window.location.pathname.match(/^\/products\/([^/]+)/);

        if (productMatch) {
            send('product_view', {
                handle: decodeURIComponent(productMatch[1]),
                url: `${window.location.pathname}${window.location.search}`,
            });
        }

        const checkoutMatch = window.location.pathname.match(/^\/checkout(?:\/([^/]+))?/);

        if (checkoutMatch) {
            const checkoutKey = `storefront-analytics-checkout:${sessionId}:${checkoutMatch[1] ?? 'new'}`;

            try {
                if (!window.sessionStorage.getItem(checkoutKey)) {
                    window.sessionStorage.setItem(checkoutKey, 'tracked');
                    send('checkout_started', { checkout_id: checkoutMatch[1] ?? null });
                }
            } catch {
                send('checkout_started', { checkout_id: checkoutMatch[1] ?? null });
            }
        }
    };

    trackPage();

    document.addEventListener('livewire:navigated', () => {
        const nextUrl = window.location.href;

        if (!livewireNavigationStarted && nextUrl === lastPageUrl) {
            livewireNavigationStarted = true;

            return;
        }

        livewireNavigationStarted = true;
        lastPageUrl = nextUrl;
        trackPage();
    });

    window.addEventListener('storefront-analytics', (event) => {
        const { type, properties } = event.detail ?? {};

        if (typeof type === 'string') {
            send(type, properties ?? {});
        }
    });
})();
