/**
 * Vitest setup file for frontend tests.
 *
 * This file runs before each test file and sets up the test environment.
 */

// Suppress jsdom HTMLBaseElement errors that occur with Stimulus
// These are benign errors from jsdom's DOM implementation when util.inspect
// tries to format DOM elements that have incompatible property accessors
const originalConsoleError = console.error;
console.error = (...args: unknown[]) => {
    const message = String(args[0] ?? "");
    if (message.includes("HTMLBaseElement") || message.includes("HTMLAnchorElement")) {
        return; // Suppress
    }
    originalConsoleError.apply(console, args);
};

// Catch unhandled errors from jsdom/Stimulus incompatibility
if (typeof process !== "undefined") {
    process.on("uncaughtException", (error: Error) => {
        if (error.message?.includes("HTMLBaseElement") || error.message?.includes("HTMLAnchorElement")) {
            // Suppress jsdom errors
            return;
        }
        throw error;
    });
}

function clearAllCookies(): void {
    const cookies = document.cookie
        .split(";")
        .map((c) => c.trim())
        .filter(Boolean);
    for (const cookie of cookies) {
        const eqIdx = cookie.indexOf("=");
        const name = eqIdx === -1 ? cookie : cookie.slice(0, eqIdx);
        document.cookie = `${name}=; path=/; max-age=0`;
    }
}

// Reset DOM and state between tests
beforeEach(() => {
    document.body.innerHTML = "";
    clearAllCookies();

    if (!window.crypto || typeof window.crypto.getRandomValues !== "function") {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        (window as any).crypto = {
            getRandomValues: <T extends ArrayBufferView>(array: T): T => array,
        };
    }

    vi.restoreAllMocks();
});

afterEach(() => {
    // Clean up any lingering elements
    document.body.innerHTML = "";
    document.head.innerHTML = "";
});
