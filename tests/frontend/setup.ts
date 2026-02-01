import { beforeEach, vi } from "vitest";

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

beforeEach(() => {
    clearAllCookies();

    if (!window.crypto || typeof window.crypto.getRandomValues !== "function") {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        (window as any).crypto = {
            getRandomValues: <T extends ArrayBufferView>(array: T): T => array,
        };
    }

    vi.restoreAllMocks();
});
