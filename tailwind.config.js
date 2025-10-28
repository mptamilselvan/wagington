const defaultTheme = require("tailwindcss/defaultTheme");

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
    ],

    theme: {
        extend: {
            // fontFamily: {
            //     sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            // },
            colors: {
                primary: {
                    blue: "#00A5FF",
                    beige: "#FBF1E5",
                    olive: "#A8B7AB",
                    navy: "#1B2A41",
                    ora: "#FB9300",
                },

                secondry: {
                    yellow: "#FCE762",
                    mint: "#2EBFA5",
                    orange: "#FB8B24",
                    pink: "#FECEE9",
                    ora: "#FB93001F",
                },

                state: {
                    green: "#38C976",
                    blue: "#2AB0FC",
                    yellow: "#FCC132",
                    orange: "#FF8206",
                    red: "#F23D20",
                },

                // Background colors
                bg: {
                    primary: "#FFFFFF",
                    secondary: "#F9FAFB",
                    tertiary: "#F3F4F6",
                    accent: "#FBF1E5",
                    surface: "#F7F8FA",
                    card: "#FFFFFF",
                    overlay: "rgba(0, 0, 0, 0.5)",
                },

                // text color
                gray: {
                    icons: "#8E8E8E",
                    place: "#6B7280",
                    input: "#171717",
                    menu: "#B1B1B1",
                    backdrop: "#F9F9F9",
                    light: "#F9FAFB",
                    text: "#595959",
                    101: "#F8F9FA",
                    102: "#F1F3F5",
                    103: "#E9ECEF",
                    104: "#DEE2E6",
                    105: "#D1D5DB",
                    106: "#ACB5BD",
                    107: "#858E96",
                    108: "#495057",
                    109: "#212529",
                    110: "#19191B",
                    111: "#e0dfe0",
                },

                red: {
                    error: "#FCA5A5",
                    text: "#EF4444",
                },
                blue: {
                    hover: "#0097EA",
                    focus: "#0089D3",
                    disabled: "#8FD7FF",
                    label: "#374151",
                },
            },
            fontFamily: {
                montserrat: "Montserrat",
                notable: "Notable",
                inherit: "inherit",
                inika: "Inika",
                "im-fell-french-canon": "'IM FELL French Canon'",
            },
        },
    },

    plugins: [require("@tailwindcss/forms")],
};
