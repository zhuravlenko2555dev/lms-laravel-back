import eslint from '@eslint/js'
import eslintPluginVue from 'eslint-plugin-vue'
import globals from 'globals'
import typescriptEslint from 'typescript-eslint'
import stylistic from '@stylistic/eslint-plugin'

export default typescriptEslint.config(
    {
        ignores: ['**/storage/**', '**/vendor/**'],
        files: ['resources/ts/**/*.{ts,vue}'],
        extends: [
            eslint.configs.recommended,
            ...typescriptEslint.configs.recommended,
            ...eslintPluginVue.configs['flat/recommended'],
            stylistic.configs.recommended,
        ],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: globals.browser,
            parserOptions: {
                parser: typescriptEslint.parser,
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/no-reserved-component-names': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/html-indent': ['error', 4],
            'vue/singleline-html-element-content-newline': 'off',
            'vue/prop-name-casing': ['error', 'camelCase', { ignoreProps: ['/^_[a-zA-Z]+/u'] }],

            '@stylistic/indent': ['error', 4],
            '@stylistic/array-bracket-newline': ['error', 'consistent'],
            '@stylistic/array-element-newline': ['error', 'consistent'],
            '@stylistic/brace-style': ['error', '1tbs'],
            '@stylistic/comma-dangle': ['error', 'always-multiline'],
            '@stylistic/function-call-argument-newline': ['error', 'consistent'],
            '@stylistic/multiline-ternary': ['error', 'always-multiline'],
            '@stylistic/object-curly-spacing': ['error', 'always'],
            '@stylistic/padded-blocks': 'off',
            '@stylistic/padding-line-between-statements': 'off',
            '@stylistic/quotes': ['error', 'single'],
            '@stylistic/semi': ['error', 'never'],
        },
    },
)
