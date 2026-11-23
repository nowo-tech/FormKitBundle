/**
 * IIFE entry of `char-counter.js` (built by Vite into `src/Resources/public/`): installs the counter once per page
 * and exposes `window.NowoFormKitCharCounter.refresh(root?)` for fields whose value is changed by code.
 *
 * @packageDocumentation
 */

import { install, scan } from './char-counter';
import { createBundleLogger } from '../src/logger';

declare const __FORM_KIT_HELP_MODAL_BUILD_TIME__: string;

createBundleLogger('form-kit/char-counter', {
  buildTime: typeof __FORM_KIT_HELP_MODAL_BUILD_TIME__ !== 'undefined' ? __FORM_KIT_HELP_MODAL_BUILD_TIME__ : undefined,
}).scriptLoaded();

type CharCounterApi = { refresh(root?: ParentNode): void };

const globals = globalThis as typeof globalThis & { NowoFormKitCharCounter?: CharCounterApi };

if (!globals.NowoFormKitCharCounter) {
  install(document);
  globals.NowoFormKitCharCounter = { refresh: (root?: ParentNode) => scan(root ?? document) };
}
