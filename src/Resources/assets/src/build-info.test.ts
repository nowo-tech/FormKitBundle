import { describe, expect, it } from 'vitest';
import info from '../../../../build-info.json';
import charCounterJs from '../../public/char-counter.js?raw';
import helpModalJs from '../../public/help-modal.js?raw';
import helpModalSource from './help-modal.ts?raw';

describe('build-info.json injection', () => {
  const label = info.builtAt.replace('T', ' ').replace('Z', ' UTC');

  it('built help-modal.js contains the committed builtAt literal', () => {
    expect(helpModalJs).toContain(label);
  });

  it('built char-counter.js contains the committed builtAt literal', () => {
    expect(charCounterJs).toContain(label);
  });

  it('help-modal.ts reads the define through a bare identifier', () => {
    expect(helpModalSource).not.toMatch(/globalThis[^\n]*__FORM_KIT_HELP_MODAL_BUILD_TIME__/);
  });
});
