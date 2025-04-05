import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/views/components/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './resources/views/vendor/**/*.blade.php',
        './vendor/awcodes/filament-table-repeater/resources/**/*.blade.php',
        './vendor/diogogpinto/filament-auth-ui-enhancer/resources/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                white: '#F3F4F6',
                platinum: '#E8E9EB',
            },
            transitionTimingFunction: {
                'ease-smooth': 'cubic-bezier(0.08, 0.52, 0.52, 1)',
            }
        }
    }
}
