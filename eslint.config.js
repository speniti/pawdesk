import eslint from '@eslint/js';
import globals from 'globals';
import prettierConfig from 'eslint-config-prettier';
import { defineConfig } from 'eslint/config';
import tslint from 'typescript-eslint';

export default defineConfig([
  { ignores: ['**/vendor/'] },
  {
    files: ['resources/**/*.ts', '*.config.ts'],
    extends: [
      eslint.configs.recommended,
      tslint.configs.strictTypeChecked,
      tslint.configs.stylisticTypeChecked,
      prettierConfig,
      {
        languageOptions: {
          globals: { ...globals.node },
          parserOptions: {
            projectService: true,
            tsconfigRootDir: import.meta.dirname,
          },
        },
      },
    ],
  },
  { files: ['*.config.js'], extends: [tslint.configs.disableTypeChecked] },
]);
