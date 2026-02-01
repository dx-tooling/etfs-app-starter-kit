import { describe, expect, it, vi } from "vitest";

import {
    generateCsrfHeaders,
    generateCsrfToken,
    removeCsrfToken,
} from "../../assets/controllers/csrf_protection_controller.js";

function createFormWithCsrfField(initialValue: string): { form: HTMLFormElement; field: HTMLInputElement } {
    const form = document.createElement("form");
    const field = document.createElement("input");
    field.name = "_csrf_token";
    field.value = initialValue;

    form.appendChild(field);
    document.body.appendChild(form);

    return { form, field };
}

describe("csrf_protection_controller", () => {
    it("does nothing when form has no csrf field", () => {
        const form = document.createElement("form");
        document.body.appendChild(form);

        generateCsrfToken(form);

        expect(document.cookie).toBe("");
    });

    it("generates a token, stores cookie name, and sets cookie", () => {
        const { form, field } = createFormWithCsrfField("csrfCookieName");

        const changeSpy = vi.fn();
        field.addEventListener("change", changeSpy);

        vi.spyOn(window.crypto, "getRandomValues").mockImplementation((arr: Uint8Array): Uint8Array => {
            for (let i = 0; i < arr.length; i += 1) {
                arr[i] = (i + 1) % 256;
            }
            return arr;
        });

        generateCsrfToken(form);

        const cookieName = field.getAttribute("data-csrf-protection-cookie-value");
        expect(cookieName).toBe("csrfCookieName");
        expect(field.defaultValue).not.toBe("csrfCookieName");
        expect(field.defaultValue.length).toBeGreaterThanOrEqual(24);
        expect(changeSpy).toHaveBeenCalled();

        expect(document.cookie).toContain(`${cookieName}_${field.defaultValue}=${cookieName}`);
        expect(document.cookie).not.toContain("__Host-");
    });

    it("generates csrf headers when cookie name and token look valid", () => {
        const { form, field } = createFormWithCsrfField("csrfCookieName");

        vi.spyOn(window.crypto, "getRandomValues").mockImplementation((arr: Uint8Array): Uint8Array => {
            for (let i = 0; i < arr.length; i += 1) {
                arr[i] = (i + 1) % 256;
            }
            return arr;
        });

        generateCsrfToken(form);

        const cookieName = field.getAttribute("data-csrf-protection-cookie-value");
        expect(cookieName).toBe("csrfCookieName");

        // The implementation writes the generated token to defaultValue.
        // In real browsers this becomes the submitted value; in jsdom we mirror that here.
        field.value = field.defaultValue;

        const headers = generateCsrfHeaders(form);
        expect(headers).toEqual({ [cookieName as string]: field.value });
    });

    it("removes csrf cookie after submission", () => {
        const { form, field } = createFormWithCsrfField("csrfCookieName");

        vi.spyOn(window.crypto, "getRandomValues").mockImplementation((arr: Uint8Array): Uint8Array => {
            for (let i = 0; i < arr.length; i += 1) {
                arr[i] = (i + 1) % 256;
            }
            return arr;
        });

        generateCsrfToken(form);
        expect(document.cookie).not.toBe("");

        // See note above: jsdom doesn't automatically sync defaultValue -> value.
        field.value = field.defaultValue;

        removeCsrfToken(form);
        expect(document.cookie).toBe("");
    });
});
