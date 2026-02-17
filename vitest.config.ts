import { defineConfig } from "vitest/config";

export default defineConfig({
    test: {
        environment: "jsdom",
        include: ["tests/frontend/**/*.test.ts"],
        globals: true,
        setupFiles: ["tests/frontend/setup.ts"],
        // Silence unhandled errors from jsdom (can happen with DOM APIs Stimulus touches)
        dangerouslyIgnoreUnhandledErrors: true,
    },
});
