import pluginVue from 'eslint-plugin-vue';
import eslintConfigPrettier from 'eslint-config-prettier';
import globals from 'globals';

export default [
    {
        ignores: ['dist/**', 'dist_old/**', 'node_modules/**', 'coverage/**']
    },

    ...pluginVue.configs['flat/essential'],

    {
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node
            }
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/no-reserved-component-names': 'off',
            'vue/block-order': [
                'error',
                {
                    order: ['script', 'template', 'style']
                }
            ]
        }
    },

    eslintConfigPrettier
];
