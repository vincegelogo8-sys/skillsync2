// FILE LOCATION: resources/js/proposal-container.js  (NEW FILE)
// "View proposal" buttons open in one shared container (a native <dialog>) on the same page.
//
// Any <a data-proposal-view href="..."> is fetched from its own URL, so the existing Laravel
// routes keep deciding who may see what (watermark, no-copy and no-store headers included).
// The X-Proposal-Container header asks the layout for the page body only.
// Without JavaScript, or with ctrl/cmd/shift/middle-click, the link still opens the full page.

const container = document.getElementById('proposal-container');
const body = container?.querySelector('[data-proposal-body]');
let pending = null;

function showMessage(text, fullPageUrl = null) {
    body.replaceChildren();
    const message = document.createElement('p');
    message.className = 'proposal-container-message';
    message.setAttribute('role', 'status');
    message.textContent = text;
    body.append(message);

    if (fullPageUrl) {
        const link = document.createElement('a');
        link.href = fullPageUrl;
        link.className = 'text-indigo-600 underline';
        link.textContent = 'Open in a full page';
        message.append(' ', link);
    }
}

async function openProposal(link) {
    pending?.abort();
    pending = new AbortController();
    const { signal } = pending;

    showMessage('Loading proposal…');
    body.setAttribute('aria-busy', 'true');
    if (!container.open) container.showModal();

    try {
        const response = await fetch(link.href, {
            signal,
            credentials: 'same-origin',
            // The same URL also serves the full page; never let the cache mix the two up.
            cache: 'no-store',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Proposal-Container': '1',
            },
        });

        // Signed out or session expired: the redirect lands on the login page.
        // Let the browser navigate normally instead of showing a login form in the container.
        if (response.redirected || response.status === 401 || response.status === 419) {
            window.location.assign(link.href);
            return;
        }

        if (!response.ok) {
            showMessage(
                response.status === 403 || response.status === 404
                    ? 'This proposal is not available to you right now.'
                    : 'The proposal could not be loaded.',
                response.status >= 500 ? link.href : null,
            );
            return;
        }

        body.innerHTML = await response.text();
        body.scrollTop = 0;
        body.focus({ preventScroll: true });
    } catch (error) {
        if (error.name === 'AbortError') return;
        showMessage('The proposal could not be loaded. Check your connection and try again.', link.href);
    } finally {
        if (pending?.signal === signal) body.removeAttribute('aria-busy');
    }
}

if (container && body) {
    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;
        const link = event.target.closest('a[data-proposal-view]');
        if (!link || event.defaultPrevented) return;
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        openProposal(link);
    });

    // Clicking the dimmed backdrop closes the container, like the dashboard pop-ups.
    container.addEventListener('click', (event) => {
        if (event.target === container) container.close();
    });

    // Drop the proposal text from the page as soon as the container closes.
    container.addEventListener('close', () => {
        pending?.abort();
        pending = null;
        body.replaceChildren();
        body.removeAttribute('aria-busy');
    });
}