const eslintJs = require('@eslint/js');
const pluginImport = require('eslint-plugin-import');
const pluginPrettier = require('eslint-plugin-prettier');

module.exports = [
  {
    ignores: ['node_modules/**', 'dist/**', 'coverage/**', 'logs/**', 'tmp/**'],
  },
  eslintJs.configs.recommended,
  {
    files: ['**/*.js'],
    languageOptions: {
      sourceType: 'commonjs',
      ecmaVersion: 2021,
      globals: {
        process: 'readonly',
        console: 'readonly',
        module: 'readonly',
        require: 'readonly',
        __dirname: 'readonly',
      },
    },
    plugins: {
      import: pluginImport,
      prettier: pluginPrettier,
    },
    rules: {
      'no-console': 'off',
      'import/no-unresolved': 'off',
      'prettier/prettier': 'warn',
    },
  },
];

