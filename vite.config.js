import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
// import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        // tailwindcss({
        //     darkMode: "class", // Activa el modo oscuro cuando hay una clase 'dark' en el HTML
        // }),
    ],
});
