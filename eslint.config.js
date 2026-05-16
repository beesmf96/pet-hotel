import pluginVue from 'eslint-plugin-vue'
import prettierConfig from 'eslint-config-prettier'

export default [
    ...pluginVue.configs['flat/recommended'],
    prettierConfig,
    {
        rules: {
            // Inertia page components are intentionally single-word (Dashboard, Pets, etc.)
            'vue/multi-word-component-names': 'off',
        },
    },
]
