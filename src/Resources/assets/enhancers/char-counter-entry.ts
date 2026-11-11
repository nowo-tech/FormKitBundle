/**
 * IIFE entry of `char-counter.js` (built by Vite into `src/Resources/public/`): installs the counter once per page
 * and exposes `window.NowoFormKitCharCounter.refresh(root?)` for fields whose value is changed by code.
 *
 * @packageDocumentation
 */

import { install, scan } from './char-counter';

type CharCounterApi = { refresh(root?: ParentNode): void };

const globals = globalThis as typeof globalThis & { NowoFormKitCharCounter?: CharCounterApi };

if (!globals.NowoFormKitCharCounter) {
  install(document);
  globals.NowoFormKitCharCounter = { refresh: (root?: ParentNode) => scan(root ?? document) };
}
