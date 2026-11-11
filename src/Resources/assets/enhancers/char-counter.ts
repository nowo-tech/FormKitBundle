/**
 * Form Kit character counter (2.22): keeps the counter printed by `nowo_char_counter_widget`
 * (`@NowoFormKitBundle/form/field_widgets_theme.html.twig`) up to date while the user types.
 *
 * The server markup already works without this script: the limit is printed as text and linked to the input with
 * `aria-describedby`. The script adds a visible "42 / 160" count (hidden from screen readers) and announces, through
 * a polite live region, only how many characters are left once the remaining count reaches the threshold, and how
 * many are over the limit. Announcements are debounced, so a fast typist does not hear every key.
 *
 * It counts like the server's `Length` constraint (`codepoints`, `graphemes` or `bytes`), and counts a textarea line
 * break as two characters because the browser submits it as CRLF. It never truncates or blocks input.
 *
 * Inputs are found by `[data-nowo-form-kit-counter]` (the counter element id); listeners are delegated on the document,
 * and a MutationObserver picks up fields inserted later (Turbo frames, Live Components, dialogs). No inline script or
 * style is used: CSP-safe with `script-src 'self'`.
 *
 * @packageDocumentation
 */

/** Data attribute on the input; its value is the counter element id. */
export const INPUT_SELECTOR = '[data-nowo-form-kit-counter]';

/** Debounce of live-region announcements, in milliseconds. */
export const ANNOUNCE_DELAY = 500;

export type CountUnit = 'codepoints' | 'graphemes' | 'bytes';

type Segmenter = { segment(input: string): Iterable<unknown> };
type SegmenterConstructor = new (locale?: string, options?: { granularity: 'grapheme' }) => Segmenter;

let segmenter: Segmenter | null | undefined;

function graphemeSegmenter(): Segmenter | null {
  if (segmenter === undefined) {
    const ctor = (Intl as unknown as { Segmenter?: SegmenterConstructor }).Segmenter;
    segmenter = ctor ? new ctor(undefined, { granularity: 'grapheme' }) : null;
  }

  return segmenter;
}

/** Test hook: forget the cached segmenter (e.g. after stubbing `Intl.Segmenter`). */
export function resetSegmenter(): void {
  segmenter = undefined;
}

/**
 * Length of `value` in `unit`; `multiline` counts each line break as CRLF (two code points, one grapheme), as the
 * browser submits a textarea.
 */
export function countText(value: string, unit: CountUnit, multiline = false): number {
  const text = multiline ? value.replace(/\r?\n/g, '\r\n') : value;
  if (unit === 'bytes') {
    return new TextEncoder().encode(text).length;
  }
  if (unit === 'graphemes') {
    const seg = graphemeSegmenter();
    if (seg) {
      let n = 0;
      for (const _ of seg.segment(text)) {
        n++;
      }

      return n;
    }
  }

  return Array.from(text).length;
}

/** Replaces `%count%`, `%max%` and `%min%` in a translated template. */
export function format(template: string, values: Record<string, number>): string {
  return template.replace(/%(count|max|min)%/g, (match, name: string) => (name in values ? String(values[name]) : match));
}

function intAttr(el: HTMLElement, name: string): number | null {
  const raw = el.getAttribute(name);
  if (raw === null || !/^\d+$/.test(raw)) {
    return null;
  }

  return parseInt(raw, 10);
}

export type CounterState = {
  count: number;
  max: number | null;
  min: number | null;
  /** Visible text ("42 / 160", or the over-limit sentence). */
  visible: string;
  /** Text for the live region ('' = nothing to announce). */
  announcement: string;
  over: boolean;
};

/** Computes what the counter shows for the current value of `input`. */
export function computeState(input: HTMLInputElement | HTMLTextAreaElement, counter: HTMLElement): CounterState {
  const unitAttr = counter.getAttribute('data-nowo-form-kit-counter-unit');
  const unit: CountUnit = unitAttr === 'codepoints' || unitAttr === 'bytes' ? unitAttr : 'graphemes';
  const max = intAttr(counter, 'data-nowo-form-kit-counter-max');
  const min = intAttr(counter, 'data-nowo-form-kit-counter-min');
  const threshold = intAttr(counter, 'data-nowo-form-kit-counter-threshold') ?? 0;
  const text = (name: string): string => counter.getAttribute('data-nowo-form-kit-counter-text-' + name) ?? '';
  const count = countText(input.value, unit, input.tagName === 'TEXTAREA');

  let visible: string;
  let announcement = '';
  let over = false;
  if (max !== null) {
    const remaining = max - count;
    if (remaining < 0) {
      over = true;
      visible = format(text('over'), { count: -remaining });
      announcement = visible;
    } else {
      visible = format(text('max'), { count, max });
      if (remaining <= threshold) {
        announcement = format(text('remaining'), { count: remaining });
      }
    }
  } else if (min !== null) {
    visible = format(text('min'), { count, min });
  } else {
    visible = format(text('count'), { count });
  }

  return { count, max, min, visible, announcement, over };
}

const timers = new WeakMap<HTMLElement, ReturnType<typeof setTimeout>>();
const announced = new WeakMap<HTMLElement, string>();

function isCountable(el: unknown): el is HTMLInputElement | HTMLTextAreaElement {
  return (
    el instanceof HTMLElement &&
    (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') &&
    el.hasAttribute('data-nowo-form-kit-counter')
  );
}

/**
 * Refreshes the counter of one input. `announce` schedules the live-region update (typing); a plain refresh (page
 * load, reset) only updates the visible count.
 */
export function update(input: HTMLInputElement | HTMLTextAreaElement, announce = false): void {
  const doc = input.ownerDocument;
  const counterId = input.getAttribute('data-nowo-form-kit-counter') ?? '';
  const counter = counterId === '' ? null : doc.getElementById(counterId);
  if (!counter) {
    return;
  }

  const state = computeState(input, counter);
  counter.setAttribute('data-nowo-form-kit-counter-ready', '');
  counter.classList.toggle('nowo-form-kit-counter--over', state.over);
  const count = counter.querySelector<HTMLElement>('.nowo-form-kit-counter__count');
  if (count) {
    count.hidden = false;
    count.textContent = state.visible;
  }

  const live = doc.getElementById(counterId + '_live');
  if (!announce || !live) {
    return;
  }
  const pending = timers.get(input);
  if (pending !== undefined) {
    clearTimeout(pending);
  }
  timers.set(
    input,
    setTimeout(() => {
      timers.delete(input);
      const message = computeState(input, counter).announcement;
      if (announced.get(input) !== message) {
        announced.set(input, message);
        live.textContent = message;
      }
    }, ANNOUNCE_DELAY),
  );
}

/** Refreshes every counter input inside `root` (and `root` itself). */
export function scan(root: ParentNode | Element): void {
  if (isCountable(root)) {
    update(root);
  }
  root.querySelectorAll(INPUT_SELECTOR).forEach((el) => {
    if (isCountable(el)) {
      update(el);
    }
  });
}

/**
 * Binds the delegated listeners on `doc` and refreshes the counters already on the page. Returns a function that
 * removes them (tests, or a host tearing the page down).
 */
export function install(doc: Document): () => void {
  const onInput = (event: Event): void => {
    if (isCountable(event.target)) {
      update(event.target, true);
    }
  };
  const onFocus = (event: Event): void => {
    if (isCountable(event.target)) {
      update(event.target);
    }
  };
  const onReset = (event: Event): void => {
    const form = event.target;
    if (form instanceof HTMLFormElement) {
      // The reset event fires before the fields get their default values back.
      setTimeout(() => scan(form), 0);
    }
  };
  doc.addEventListener('input', onInput);
  doc.addEventListener('focusin', onFocus);
  doc.addEventListener('reset', onReset);

  const observer =
    typeof MutationObserver === 'undefined'
      ? null
      : new MutationObserver((records) => {
          for (const record of records) {
            record.addedNodes.forEach((node) => {
              if (node.nodeType === 1) {
                scan(node as Element);
              }
            });
          }
        });
  observer?.observe(doc.documentElement, { childList: true, subtree: true });

  const start = (): void => scan(doc);
  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }

  return () => {
    doc.removeEventListener('input', onInput);
    doc.removeEventListener('focusin', onFocus);
    doc.removeEventListener('reset', onReset);
    doc.removeEventListener('DOMContentLoaded', start);
    observer?.disconnect();
  };
}
