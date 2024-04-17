/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './../**/*.phtml',
        './../**/*.js',
        './../**/*.html'
    ],

    theme: {
        extend: {

            colors: {
                'purple': '#4600CC',
                'purple-100': '#EFE8FF',
                'purple-200': '#3800A3',
                'purple-300': '#2A007A',
                'grey-300': '#DBD7E3',
                'grey-600': '#493371',
                'grey-700': '#1B004E',
                'grey-800': '#0D0025',
                'red-500': '#FBECEF',
                'red-600' : '#CF0C35'
            },
            fontFamily: {
                "inter": ['Inter', 'sans-serif']
            }
        },
    },
    plugins: [],
}
