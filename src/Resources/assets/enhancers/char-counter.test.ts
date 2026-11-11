import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ANNOUNCE_DELAY, computeState, countText, format, install, resetSegmenter, scan, update } from './char-counter';

/** Server markup of `nowo_char_counter_widget` (trimmed to what the script reads). */
function markup(opts: { tag?: 'input' | 'textarea'; max?: number; min?: number; threshold?: number; unit?: string; value?: string } = {}): string {
  const tag = opts.tag ?? 'input';
  const value = opts.value ?? '';
  const field =
    tag === 'textarea'
      ? `<textarea id="f" aria-describedby="f_counter" data-nowo-form-kit-counter="f_counter">${value}</textarea>`
      : `<input id="f" type="text" aria-describedby="f_counter" data-nowo-form-kit-counter="f_counter" value="${value}">`;
  const max = opts.max === undefined ? '' : ` data-nowo-form-kit-counter-max="${opts.max}" data-nowo-form-kit-counter-threshold="${opts.threshold ?? 2}"`;
  const min = opts.min === undefined ? '' : ` data-nowo-form-kit-counter-min="${opts.min}"`;

  return `<form id="form">${field}<span id="f_counter" class="nowo-form-kit-counter" data-nowo-form-kit-counter-unit="${opts.unit ?? 'codepoints'}"${max}${min}
    data-nowo-form-kit-counter-text-max="%count% / %max%"
    data-nowo-form-kit-counter-text-min="%count% (min. %min%)"
    data-nowo-form-kit-counter-text-count="Characters: %count%"
    data-nowo-form-kit-counter-text-remaining="Characters left: %count%"
    data-nowo-form-kit-counter-text-over="Characters over the limit: %count%"><span class="nowo-form-kit-counter__limit">Maximum</span><span class="nowo-form-kit-counter__count" aria-hidden="true" hidden></span></span><span id="f_counter_live" aria-live="polite"></span></form>`;
}

const field = (): HTMLInputElement => document.getElementById('f') as HTMLInputElement;
const counter = (): HTMLElement => document.getElementById('f_counter') as HTMLElement;
const visible = (): HTMLElement => counter().querySelector('.nowo-form-kit-counter__count') as HTMLElement;
const live = (): HTMLElement => document.getElementById('f_counter_live') as HTMLElement;

function type(value: string): void {
  field().value = value;
  field().dispatchEvent(new Event('input', { bubbles: true }));
}

describe('countText', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    resetSegmenter();
  });

  it('counts code points, graphemes and UTF-8 bytes like the Length constraint', () => {
    const family = '\u{1F468}‍\u{1F469}‍\u{1F467}';
    expect(countText('héllo', 'codepoints')).toBe(5);
    expect(countText('héllo', 'bytes')).toBe(6);
    expect(countText(family, 'codepoints')).toBe(5);
    expect(countText(family + 'a', 'graphemes')).toBe(2);
    expect(countText('e\u0301', 'graphemes')).toBe(1);
  });

  it('counts a textarea line break as CRLF', () => {
    expect(countText('a\nb', 'codepoints', true)).toBe(4);
    expect(countText('a\r\nb', 'codepoints', true)).toBe(4);
    expect(countText('a\nb', 'codepoints')).toBe(3);
    expect(countText('a\nb', 'graphemes', true)).toBe(3);
  });

  it('falls back to code points without Intl.Segmenter', () => {
    vi.stubGlobal('Intl', { ...Intl, Segmenter: undefined });
    resetSegmenter();
    expect(countText('é', 'graphemes')).toBe(2);
  });
});

describe('format', () => {
  it('replaces known placeholders and keeps unknown ones', () => {
    expect(format('%count% / %max%', { count: 3, max: 10 })).toBe('3 / 10');
    expect(format('%count% of %min%', { count: 3 })).toBe('3 of %min%');
  });
});

describe('computeState', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('shows count / max and announces only near the limit', () => {
    document.body.innerHTML = markup({ max: 10, threshold: 2, value: 'abcdefg' });
    expect(computeState(field(), counter())).toMatchObject({ count: 7, visible: '7 / 10', announcement: '', over: false });
    field().value = 'abcdefgh';
    expect(computeState(field(), counter())).toMatchObject({ visible: '8 / 10', announcement: 'Characters left: 2' });
    field().value = 'abcdefghijkl';
    expect(computeState(field(), counter())).toMatchObject({ visible: 'Characters over the limit: 2', announcement: 'Characters over the limit: 2', over: true });
  });

  it('handles min only, no limit, a missing threshold and an unknown unit', () => {
    document.body.innerHTML = markup({ min: 5, value: 'abc', unit: 'parsecs' });
    expect(computeState(field(), counter())).toMatchObject({ visible: '3 (min. 5)', max: null, min: 5 });
    document.body.innerHTML = markup({ value: 'abc', unit: 'bytes' });
    expect(computeState(field(), counter())).toMatchObject({ visible: 'Characters: 3' });
    document.body.innerHTML = markup({ max: 3, value: 'abc' });
    counter().removeAttribute('data-nowo-form-kit-counter-threshold');
    counter().setAttribute('data-nowo-form-kit-counter-text-remaining', 'left %count%');
    expect(computeState(field(), counter()).announcement).toBe('left 0');
    counter().setAttribute('data-nowo-form-kit-counter-max', 'x');
    counter().removeAttribute('data-nowo-form-kit-counter-text-count');
    expect(computeState(field(), counter()).visible).toBe('');
  });
});

describe('install', () => {
  let uninstall: (() => void) | null = null;

  beforeEach(() => {
    vi.useFakeTimers();
    document.body.innerHTML = '';
  });

  afterEach(() => {
    uninstall?.();
    uninstall = null;
    vi.useRealTimers();
    vi.unstubAllGlobals();
  });

  it('shows the live count on load and announces after a debounce', () => {
    document.body.innerHTML = markup({ max: 10, threshold: 2, value: 'abc' });
    uninstall = install(document);

    expect(counter().hasAttribute('data-nowo-form-kit-counter-ready')).toBe(true);
    expect(visible().hidden).toBe(false);
    expect(visible().textContent).toBe('3 / 10');

    type('abcdefgh');
    expect(visible().textContent).toBe('8 / 10');
    expect(live().textContent).toBe('');
    type('abcdefghi');
    vi.advanceTimersByTime(ANNOUNCE_DELAY);
    expect(live().textContent).toBe('Characters left: 1');

    type('abcdefghijk');
    vi.advanceTimersByTime(ANNOUNCE_DELAY);
    expect(live().textContent).toBe('Characters over the limit: 1');
    expect(counter().classList.contains('nowo-form-kit-counter--over')).toBe(true);

    live().textContent = 'kept';
    type('abcdefghijk');
    vi.advanceTimersByTime(ANNOUNCE_DELAY);
    expect(live().textContent).toBe('kept');

    type('a');
    vi.advanceTimersByTime(ANNOUNCE_DELAY);
    expect(live().textContent).toBe('');
    expect(counter().classList.contains('nowo-form-kit-counter--over')).toBe(false);
  });

  it('ignores other inputs, inputs without a counter and refreshes on focus', () => {
    document.body.innerHTML = markup({ max: 10 }) + '<input id="other"><input id="lost" data-nowo-form-kit-counter="nope"><input id="empty" data-nowo-form-kit-counter="">';
    uninstall = install(document);
    (document.getElementById('other') as HTMLInputElement).dispatchEvent(new Event('input', { bubbles: true }));
    (document.getElementById('lost') as HTMLInputElement).dispatchEvent(new Event('input', { bubbles: true }));
    (document.getElementById('empty') as HTMLInputElement).dispatchEvent(new Event('focusin', { bubbles: true }));
    document.body.dispatchEvent(new Event('focusin', { bubbles: true }));
    field().value = 'abcd';
    field().dispatchEvent(new Event('focusin', { bubbles: true }));
    expect(visible().textContent).toBe('4 / 10');
  });

  it('counts the textarea default value after a form reset', () => {
    document.body.innerHTML = markup({ tag: 'textarea', max: 10, value: 'a\nb' });
    uninstall = install(document);
    expect(visible().textContent).toBe('4 / 10');
    type('abcdef');
    expect(visible().textContent).toBe('6 / 10');
    (document.getElementById('form') as HTMLFormElement).reset();
    vi.advanceTimersByTime(0);
    expect(visible().textContent).toBe('4 / 10');
    document.body.dispatchEvent(new Event('reset', { bubbles: true }));
  });

  it('works without a live region and without a count element', () => {
    document.body.innerHTML = markup({ max: 10 });
    live().remove();
    visible().remove();
    uninstall = install(document);
    type('abc');
    vi.advanceTimersByTime(ANNOUNCE_DELAY);
    expect(counter().hasAttribute('data-nowo-form-kit-counter-ready')).toBe(true);
  });

  it('picks up fields inserted later', async () => {
    uninstall = install(document);
    const wrapper = document.createElement('div');
    wrapper.innerHTML = markup({ max: 5, value: 'ab' });
    document.body.appendChild(wrapper);
    document.body.appendChild(document.createTextNode('text'));
    await vi.waitFor(() => expect(visible().textContent).toBe('2 / 5'));
  });

  it('refreshes an input passed directly to scan', () => {
    document.body.innerHTML = markup({ max: 5, value: 'abc' }) + '<div data-nowo-form-kit-counter="f_counter"></div><input id="bare">';
    update(document.getElementById('bare') as HTMLInputElement, true);
    scan(document.body);
    scan(field());
    expect(visible().textContent).toBe('3 / 5');
    update(field());
    expect(visible().textContent).toBe('3 / 5');
  });

  it('waits for DOMContentLoaded while loading and works without MutationObserver', () => {
    document.body.innerHTML = markup({ max: 5, value: 'abc' });
    vi.stubGlobal('MutationObserver', undefined);
    const state = vi.spyOn(document, 'readyState', 'get').mockReturnValue('loading');
    uninstall = install(document);
    expect(visible().hidden).toBe(true);
    document.dispatchEvent(new Event('DOMContentLoaded'));
    expect(visible().textContent).toBe('3 / 5');
    state.mockRestore();
  });
});

describe('entry', () => {
  afterEach(() => {
    delete (globalThis as Record<string, unknown>).NowoFormKitCharCounter;
    vi.resetModules();
  });

  it('installs once and exposes refresh()', async () => {
    document.body.innerHTML = markup({ max: 5, value: 'abc' });
    await import('./char-counter-entry');
    const api = (globalThis as Record<string, unknown>).NowoFormKitCharCounter as { refresh(root?: ParentNode): void };
    expect(visible().textContent).toBe('3 / 5');
    field().value = 'abcd';
    api.refresh();
    expect(visible().textContent).toBe('4 / 5');
    field().value = 'a';
    api.refresh(document.body);
    expect(visible().textContent).toBe('1 / 5');

    vi.resetModules();
    await import('./char-counter-entry');
    expect((globalThis as Record<string, unknown>).NowoFormKitCharCounter).toBe(api);
  });
});
